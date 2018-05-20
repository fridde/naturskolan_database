<?php

namespace Fridde;

use Carbon\Carbon;
use function foo\func;
use Fridde\Entities\Change;
use Fridde\Entities\Group;
use Fridde\Entities\Hash;
use Fridde\Entities\HashRepository;
use Fridde\Entities\Message;
use Fridde\Entities\School;
use Fridde\Entities\SchoolRepository;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Messenger\AbstractMessageController;
use Fridde\Messenger\Mail;
use Fridde\Messenger\SMS;
use Fridde\Utility as U;
use Fridde\Timing as T;


/**
 * This class deals with tasks that are executed on a regular basis. The interval
 * of each task is defined in cronjobs.intervals in the settings and the actual
 * execution is called from Fridde\Controller\CronController.
 * @package naturskolan_database
 */
class Task
{
    /** @var \Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    private $N;
    /** @var string Type of task to perform. */
    private $type;

    /**
     * The constructor.
     *
     * @param string $task_type Type of task to perform. Corresponds to index in $task_to_method_map
     */
    public function __construct(string $task_type = null)
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $this->type = $task_type;
    }

    /**
     * The main method that executes a certain task by calling its corresponding method
     * defined in TASK_TO_METHOD_MAP, but only if its corresponding entry in
     * $task_activation is not set to a falsy value
     *
     */
    public function execute()
    {
        $function_name = preg_replace('/[^[:alnum:][:space:]]/u', '', $this->type);
        try {
            call_user_func([$this, $function_name]);
        } catch (\Exception $e) {
            $msg = 'Failed task: '.$this->type;
            $msg .= '. Error message: '.$e->getMessage();
            $this->N->log($msg, 'Task->execute()');

            return false;
        }
        $this->N->log('Executed task: '.$this->type, 'Task->execute()');

        return true;

    }

    /**
     * Checks if the ical-calendar has been changed since its last build or if it's older
     * than the time defined by cron_jobs.max_calendar_age in SETTINGS and builds a new
     * calendar file from scratch if necessary.
     *
     * @return void
     */
    private function rebuildCalendar()
    {
        $last_rebuild = $this->N->getLastRun('rebuild_calendar');
        if (empty($last_rebuild)) {
            $too_old = true;
        } else {
            $max_age = SETTINGS['cronjobs']['max_calendar_age'];
            $too_old = T::longerThanSince($max_age, $last_rebuild);
        }

        $is_dirty = $this->N->calendarIsDirty();
        if ($is_dirty || $too_old) {
            $cal = new Calendar();
            $cal->save();
            $this->N->log('Actually recalculated calendar', 'Task->rebuildCalendar()');
        } else {
            $this->N->log('No calendar recalculation needed', 'Task->rebuildCalendar()');
        }
    }

    /**
     * Creates a new backup of the database and cleans older versions that are not
     * protected.
     *
     * @return void
     */
    private function backupDatabase()
    {
        $DBM = new DatabaseMaintainer();
        $DBM->backup();
        $DBM->cleanOldBackups();
    }

    /**
     * Gets the time value for either immunity time or annoyance interval from the settings
     * and subtracts this value from today.
     *
     * @param  string $type Defines whether the method should use SETTINGS.user_message.immunity_time
     *                      or SETTINGS.user_message.annoyance_interval
     *
     * @return \Carbon\Carbon The date calculated by the subtraction.
     */
    public static function getStartDate(string $type)
    {
        $translator = ['immunity' => 'immunity_time', 'annoyance' => 'annoyance_interval'];
        $setting = $translator[strtolower($type)];
        $t = SETTINGS['user_message'][$setting];

        return T::subDurationFromNow($t);
    }

    /**
     * Sends an email or sms to a user leading a group that will soon visit. The message
     * urges the user to follow an included link that confirms the visit. The method also logs the message.
     *
     * @return void
     */
    private function sendVisitConfirmationMessage()
    {
        $subject = Message::SUBJECT_CONFIRMATION;
        $annoyance_start = self::getStartDate('annoyance');
        $search_props['Status'] = ['sent', 'received'];
        $search_props['Subject'] = $subject;

        $time = Naturskolan::getSetting('user_message', 'visit_confirmation_time');
        $deadline = T::addDurationToNow($time);

        /* @var \Fridde\Entities\Visit[] $unconfirmed_visits */
        $unconfirmed_visits = $this->N->getRepo('Visit')->findUnconfirmedVisitsUntil($deadline);

        foreach ($unconfirmed_visits as $v) {
            if ($v->hasGroup()) {
                $user = $v->getGroup()->getUser();
                $last_msg = $user->getLastMessage($search_props);

                if (empty($last_msg)) {
                    if ($user->hasMail()) {
                        $response = $this->sendVisitConfirmationMail($v);
                    } elseif ($user->hasMobil()) {
                        $response = $this->sendVisitConfirmationSMS($v);
                    } else {
                        $e = 'User '.$user->getId().' has neither mail nor mobile number ';
                        $e .= 'and couldn\'t be contacted to confirm visit. Check this!';
                        $this->N->log($e, 'Task->sendVisitConfirmationMessage()');
                        continue;
                    }
                } elseif (!$last_msg->wasSentAfter($annoyance_start)) {
                    $response = $this->sendVisitConfirmationSMS($v);
                } else {
                    continue;
                }
                $msg_carrier = $response->getType();
                $this->logMessage($response, $msg_carrier, $user, $subject)->flush();
            }
        }
    }


    /**
     * Logs a sent mail or sms to the database for later retrieval.
     *
     * @param  AbstractMessageController $response The response array returned by the Messenger
     * @param  string $msg_carrier How was the message sent? 'sms' or 'mail' are currently implemented
     * @param  \Fridde\Entities\User $user The user (as object) the message was sent to
     * @return \Fridde\Update The result of the Update-operation as defined in Fridde\Update
     */
    private function logMessage($response, $msg_carrier, $user, $subject)
    {
        $success = $response->getStatus() === 'success';

        if ($success) {
            $msg_props['User'] = $user;
            $msg_props['Subject'] = $subject;
            $msg_props['Carrier'] = $msg_carrier;
            $msg_props['Status'] = Message::STATUS_SENT;
            if ($msg_carrier === 'sms') {
                $return = $response->getResponse()['result']['success'][0];
                $msg_props['ExtId'] = $return['id'];
                $msg_props['Status'] = $return['status']; // TODO: Convert this to an int
                $msg_props['Content'] = $return['message'];
            }

            return (new Update)->createNewEntity('Message', $msg_props);
        } else {
            //TODO: log this failed trial to error table or equivalent
            return (new Update);
        }
    }

    private function logMessageArray(array $msg_array, bool $flush = true)
    {
        array_walk(
            $msg_array,
            function ($msg) {
                $this->logMessage(...$msg);
            }
        );
        if ($flush) {
            $this->N->ORM->EM->flush();
        }
    }

    /**
     * Will send an **email** about a certain visit to the leader of the group that visits.
     * Notice that no check about the validity of the visit is performed here.
     *
     * @param  \Fridde\Entities\Visit $visit The visit that the mail is about
     * @return array The response object returned by the request
     */
    private function sendVisitConfirmationMail(Visit $visit)
    {
        $v = $visit;
        $params = ['purpose' => 'confirm_visit'];
        $params['receiver'] = $v->getGroup()->getUser()->getMail();

        $data['fname'] = $v->getGroup()->getUser()->getFirstName();
        $school_id = $v->getGroup()->getSchoolId();
        $absolute = !empty(DEBUG);
        $data['school_url'] = $this->N->generateUrl('school', ['school' => $school_id], $absolute);
        $visit_info['confirmation_url'] = $this->N->createConfirmationUrl($v->getId(), 'check_hash', $absolute);
        $visit_info['date_string'] = $v->getDate()->formatLocalized('%e %B');
        $visit_info['group_name'] = $v->getGroup()->getName();
        $visit_info['topic_label'] = $v->getTopic()->getLongestName();
        $visit_info['topic_url'] = $v->getTopic()->getUrl();

        $data['visit'] = $visit_info;
        $params['data'] = $data;

        $mail = new Mail($params);

        return $mail->buildAndSend();
    }

    /**
     * Will send a **sms message** about a certain visit to the leader of the group that visits.
     * Notice that no check about the validity of the visit is performed here.
     *
     * @param  \Fridde\Entities\Visit $visit The visit that the sms message is about
     * @return array The response object returned by the request
     */
    private function sendVisitConfirmationSMS(Visit $visit)
    {
        $params = ['purpose' => 'confirm_visit'];
        $params['receiver'] = $visit->getGroup()->getUser()->getStandardizedMobil();
        $rep['date_string'] = $visit->getDate()->formatLocalized('%e %B');
        $rep['fname'] = $visit->getGroup()->getUser()->getFirstName();
        $msg = $this->N->getReplacedText(['sms', 'confirm_visit'], $rep);
        $params['message'] = $msg;

        $sms = new SMS($params);

        return $sms->buildAndSend();
    }

    /**
     * Calls compileAdminSummaryMail() and sends the content to the current admin mail address
     *
     * @return AbstractMessageController The Controller object returned by the request
     */
    private function sendAdminSummaryMail()
    {
        $admin_summary = new AdminSummary();

        return $admin_summary->send();
    }

    /**
     * Informs group leaders via email that they have gained or lost one or more groups.
     *
     * @return void
     */
    private function sendChangedGroupLeaderMail()
    {
        $subject = Message::SUBJECT_CHANGED_GROUPS;
        $crit = [['EntityClass', 'Group'], ['Property', 'User']];
        $user_changes = $this->N->getRepo('Change')->findNewChanges($crit);
        $user_array = [];
        foreach ($user_changes as $change) {
            /* @var Change $change */
            /** @var Group $group */
            $group = $this->N->getRepo('Group')->find($change->getEntityId());
            $new_value = $group->getUserId();
            $old_value = (int)$change->getOldValue();
            if ($new_value !== $old_value) {
                if (!empty($old_value)) {
                    $user_array[$old_value]['removed'][] = $group->getId();
                }
                $user_array[$new_value]['new'][] = $group->getId();
            }
        }
        // ensure that 'new', 'removed', 'rest' exist
        array_walk(
            $user_array,
            function (&$u) {
                $u += array_fill_keys(['removed', 'new', 'rest'], []);
            }
        );
        $messages = [];
        /** @var User $user */
        foreach ($user_array as $user_id => $g_changes) {
            $user = $this->N->ORM->find('User', $user_id);
            $params = ['purpose' => 'changed_groups_for_user'];
            if ($user->hasMail()) {
                $params['receiver'] = $user->getMail();

                $all = $user->getGroupIdArray();
                $g_changes['rest'] = array_diff($all, $g_changes['new'], $g_changes['removed']);

                $params['data']['groups'] = $g_changes;
                $params['data']['fname'] = $user->getFirstName();

                $school_url = $this->N->generateUrl('school', ['school' => $user->getSchoolId()], true);
                $params['data']['school_url'] = $school_url;

                $mail = new Mail($params);
                $response = $mail->buildAndSend();

                $messages[] = [$response, 'mail', $user, $subject];
            } else {
                $msg = 'User '.$user->getFullName().' has no mailadress. Check this!';
                $this->N->log($msg, 'Task->sendChangedGroupLeaderMail()');
            }
        }
        array_walk(
            $user_changes,
            function ($change) {
                self::processChange($change);
            }
        );
        $this->logMessageArray($messages);
        $this->N->ORM->EM->flush();
    }

    private function sendNewUserMail()
    {
        $purpose = 'welcome_new_user';
        $subject = Message::SUBJECT_WELCOME_NEW_USER;
        $sent_welcome_messages = $this->N->getRepo('Message')->getSentWelcomeMessages();
        $welcomed_user_ids = array_map(
            function (Message $m) {
                return $m->getUser()->getId();
            },
            $sent_welcome_messages
        );
        $users_without_welcome = array_filter(
            $this->N->getRepo('User')->findActiveUsers(),
            function (User $u) use ($welcomed_user_ids) {
                return !in_array($u->getId(), $welcomed_user_ids, false);
            }
        );
        $messages = [];
        foreach ($users_without_welcome as $user) {
            /* @var User $user */
            $params = ['purpose' => $purpose];
            if ($user->hasMail()) {
                $data['fname'] = $user->getFirstName();
                $data['user']['has_mobil'] = $user->hasMobil();
                $data['groups'] = $user->getGroups()->toArray();
                $data['school_url'] = $this->N->generateUrl('school', ['school' => $user->getSchoolId()], true);
                $data['user_login_url'] = $this->N->createLoginUrl($user);

                $params['data'] = $data;
                $params['receiver'] = $user->getMail();
                $mail = new Mail($params);

                $response = $mail->buildAndSend();

                $messages[] = [$response, 'mail', $user, $subject];
            } else {
                echo '';
                // TODO: log this somewhere and inform admin
            }
        }
        $this->logMessageArray($messages);
        $this->N->ORM->EM->flush();

    }

    private function changedVisitDateMail()
    {
        // TODO: implement this function
    }


    /**
     * Checks if sufficient time since last reminder has gone and sends an email (or sms
     * if no mail-address available) to all Users with incomplete profile.
     *
     * @return array $returns;
     */
    private function sendUpdateProfileReminder()
    {
        $subject = Message::SUBJECT_PROFILE_UPDATE;
        $annoyance_start = self::getStartDate('annoyance');
        $imm_start = self::getStartDate('immunity');
        /* @var UserRepository $user_repo */
        $user_repo = $this->N->getRepo('User');
        $incomplete_users = $user_repo->findIncompleteUsers($imm_start);

        $msg_props['Status'] = [Message::STATUS_SENT, Message::STATUS_RECEIVED];
        $msg_props['Subject'] = $subject;
        $incomplete_users = array_filter(
            $incomplete_users,
            function ($u) use ($annoyance_start, $msg_props) {
                /* @var User $u */
                // We don't need to remind users without groups or users that have recently gotten a message.
                return $u->hasActiveGroups() && !$u->lastMessageWasAfter($annoyance_start, $msg_props);
            }
        );
        $messages = [];
        /* @var User $user */
        foreach ($incomplete_users as $user) {
            $params = ['purpose' => 'update_profile_reminder'];
            $data = ['fname' => $user->getFirstName()];
            $data['school_staff_url'] = $this->N->createLoginUrl($user);
            $carrier = $user->hasMobil() ? Message::CARRIER_SMS : null;
            $carrier = $user->hasMail() ? Message::CARRIER_MAIL : $carrier;

            if ($carrier === Message::CARRIER_SMS) {
                $params['receiver'] = $user->getMobil();
                $long_url = $this->N->createLoginUrl($user);
                $rep['login_url'] = $this->N->shortenUrl($long_url);
                $msg = $this->N->getReplacedText('sms/update_profile', $rep);
                $params['message'] = $msg;
                $sms = new SMS($params);
                $return = $sms->buildAndSend();
            } elseif ($carrier === Message::CARRIER_MAIL) {
                $p = ['school', ['school' => $user->getSchoolId()], true];
                $data['school_url'] = $this->N->generateUrl(...$p);
                $params['receiver'] = $user->getMail();
                $params['data'] = $data;
                $mail = new Mail($params);
                $return = $mail->buildAndSend();
            } else {
                $e_text = 'User with id <'.$user->getId().'> has no email or';
                $e_text .= ' mobile phone number. Check up on that immediately.';
                $this->N->log($e_text, 'Task->sendUpdateProfileReminder()');
                $return = null;
            }
            $messages[] = [$return, $carrier, $user, $subject];
        }

        $this->logMessageArray($messages);

        return $messages;
    }

    /**
     * Will clean unused or very old records from the database
     *
     * @return [type] [description]
     */
    private function cleanSQLDatabase()
    {
        $DBM = new DatabaseMaintainer();
        $DBM->cleanOldGroupNumbers();

        // TODO: add more functionality to this function
    }

    private function createNewPasswords()
    {
        /* @var HashRepository $hash_repo */
        $hash_repo = $this->N->ORM->getRepository('Hash');
        /* @var SchoolRepository $school_repo */
        $school_repo = $this->N->ORM->getRepository('School');
        $schools = $school_repo->findAll();
        $max_distance = Timing::multiplyDurationBy(SETTINGS['values']['validity']['school_pw'], 0.5);
        $max_expiry_date = Timing::addDurationToNow($max_distance);
        $new_expiration_date = Timing::addDurationToNow(SETTINGS['values']['validity']['school_pw']);

        $version = $this->N->Auth->getPWH()->getLatestWordFileVersion().'_'.random_int(0, 999);
        $school_passwords = [];
        foreach ($schools as $school) {
            /* @var School $school */
            if (empty($hash_repo->findHashesThatExpireAfter($max_expiry_date))) {
                $pw = $this->N->Auth->calculatePasswordForSchool($school, $version);
                $school_passwords[$school->getId()] = $pw;

                $hash = new Hash();
                $hash->setValue(password_hash($pw, PASSWORD_DEFAULT));
                $hash->setCategory(Hash::CATEGORY_SCHOOL_PW);
                $hash->setVersion($version);
                $hash->setOwnerId($school->getId());
                $rights = $this->N::isAdminSchool($school) ? Hash::RIGHTS_ALL_SCHOOLS : Hash::RIGHTS_SCHOOL_ONLY;
                $hash->setRights($rights);
                $hash->setExpiresAt($new_expiration_date);
                $this->N->ORM->EM->persist($hash);
            }
        }


        $pw_string = implode(
            PHP_EOL,
            array_map(
                function ($pw, $id) {
                    return $id.': '.$pw;
                },
                $school_passwords,
                array_keys($school_passwords)
            )
        );

        if(false === file_put_contents(BASE_DIR.'/temp/new_'.date('U'), $pw_string)){
            throw new \Exception('The password file could not be written.');
        }
        $this->N->ORM->EM->flush();

    }


    /**
     * A quick function to mark a certain Change as processed
     *
     * @param  \Fridde\Entities\Change $change The Change object
     * @return mixed The result of the Update
     */
    public static function processChange(Change $change)
    {
        $e_id = $change->getId();
        $val = Carbon::now()->toIso8601String();

        return (new Update)->updateProperty('Change', $e_id, 'Processed', $val);
    }

}
