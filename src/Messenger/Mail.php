<?php


namespace Fridde\Messenger;


use Fridde\Entities\Group;
use Fridde\Entities\Message;
use Fridde\Mailer;

class Mail extends AbstractMessageController
{
    /* @var \Fridde\Mailer $Mailer */
    protected $Mailer;

    public static $methods = [
        'admin_summary' => self::SEND | self::PREPARE,
        'password_recover' => self::SEND | self::PREPARE,
        'confirm_visit' => self::SEND | self::PREPARE,
        'update_profile_reminder' => self::SEND | self::PREPARE,
        'changed_groups_for_user' => self::SEND | self::PREPARE,
        'welcome_new_user' => self::SEND | self::PREPARE,
    ];



    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setCarrierType(Message::CARRIER_MAIL);
        $this->Mailer = new Mailer();
    }

    public function createMailBody()
    {
        $H = $this->H;
        $H->setTemplate($this->getTemplate());
        $this->addAllVariablesToTemplate();

        return $H->render(false);
    }


    public function send()
    {
        $body = $this->createMailBody();
        $this->Mailer->setValue('body', $body);

        $debug_mail = SETTINGS['debug']['mail'] ?? null;
        $result = $this->Mailer->sendAway($debug_mail );


        if ($result === false) {
            $this->setStatus('failure');
            $this->setErrors($this->Mailer->ErrorInfo);
        } else {
            $this->setStatus('success');
        }

        return $this;
    }

    protected function prepareAdminSummary()
    {
        $this->setTemplate('mail/admin_summary');
        $receiver = SETTINGS['admin']['summary']['admin_adress'];
        $this->Mailer->setValue('receiver', $receiver);
        $this->Mailer->setValue('subject', 'Dagliga sammanfattningen av databasen');

        $this->addToDATA($this->getParameter('data'));
    }

    protected function preparePasswordRecover()
    {
        $this->addToDATA($this->getParameter('data'));
        $this->moveFromDataToVar('school_url', 'fname');

        $this->setTemplate('mail/password_recover');
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', 'Naturskolan: Återställning av lösenord');
        $this->Mailer->setValue('SMTPDebug', 0);
    }

    protected function prepareUpdateProfileReminder()
    {
        $this->setTemplate('mail/incomplete_profile');
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', 'Vi behöver mer information från dig!');
        $this->addToDATA($this->getParameter('data'));
        $this->moveFromDataToVar('school_url', 'fname');
    }

    protected function prepareConfirmVisit()
    {
        $this->setTemplate('mail/confirm_visit');
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', 'Bekräfta ditt besök!');
        $this->addAsVar($this->getParameter('data'));
    }

    protected function prepareChangedGroupsForUser()
    {
        $DATA = $this->getParameter('data');

        array_walk_recursive(
            $DATA['groups'],
            function (&$g_id) {
                $group = $this->N->ORM->find('Group', $g_id);
                $g = ['group_id' => $g_id];
                $g['name'] = $group->getName();
                $g['segment'] = $group->getSegmentLabel();
                $g_id = $g;
            }
        );
        $groups = $DATA['groups'];
        $this->addToDATA($DATA);
        $this->moveFromDataToVar('school_url', 'fname');

        $this->setTemplate('mail/changed_groups');
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));

        $has_removed = !empty($groups['removed']);
        $has_new = !empty($groups['new']);
        if ($has_removed && $has_new) {
            $subject = 'Grupperna du förvaltar har ändrats';
        } elseif ($has_removed) {
            $subject = 'Antal grupper du förvaltar har minskat.';
        } elseif ($has_new) {
            $subject = 'Antal grupper du förvaltar har ökat.';
        } else {
            throw new \Exception('There were no groups given for this user.');
        }
        $this->Mailer->setValue('subject', $subject);
    }

    /**
     * Prepares and gathers the variables needed to send the Welcome-New-User mail
     *
     * @example welcome_mail_example.php
     * @return void
     */
    protected function prepareWelcomeNewUser()
    {
        $DATA = $this->getParameter('data');

        array_walk(
            $DATA['groups'],
            function (Group &$group) {
                $g = ['name' => $group->getName()];
                $g['segment'] = $group->getSegmentLabel();
                $group = $g;
            }
        );
        $this->addToDATA($DATA);
        $this->moveFromDataToVar('school_url', 'fname');
        $this->setTemplate('mail/new_user_welcome');
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', 'Välkommen i Naturskolans databas');
    }

    public function getMethods()
    {
        return self::$methods;
    }
}
