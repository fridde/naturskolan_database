<?php


namespace Fridde\Messenger;


use Fridde\Entities\Group;
use Fridde\Entities\Message;
use Fridde\Mailer;

class Mail extends AbstractMessageController
{
    /* @var \Fridde\Mailer $Mailer */
    protected $Mailer;
    // PREPARE=1; SEND=2; UPDATE=4;
    public $methods = [
        'admin_summary' => 3,
        'password_recover' => 3,
        'confirm_visit' => 3,
        'update_profile_reminder' => 3,
        'changed_groups_for_user' => 3,
        'welcome_new_user' => 3,
    ];

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setType(Message::CARRIER_MAIL);
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
        $this->Mailer->set('body', $body);

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
        $this->setTemplate('admin_summary');
        $receiver = SETTINGS['admin']['summary']['admin_adress'];
        $this->Mailer->set('receiver', $receiver);
        $this->Mailer->set('subject', 'Dagliga sammanfattningen av databasen');

        $this->addToDATA($this->getParameter('data'));
    }

    protected function preparePasswordRecover()
    {
        $this->setTemplate('password_recover');
        $this->Mailer->set('receiver', $this->getParameter('receiver'));
        $this->Mailer->set('subject', 'Naturskolan: Återställning av lösenord');
        $this->Mailer->set('SMTPDebug', 0);
        $this->addToDATA($this->getParameter('data'));
    }

    protected function prepareUpdateProfileReminder()
    {
        $this->setTemplate('incomplete_profile');
        $this->Mailer->set('receiver', $this->getParameter('receiver'));
        $this->Mailer->set('subject', 'Vi behöver mer information från dig!');
        $DATA = $this->getParameter('data');
        $this->moveFromDataToVar('school_url', 'fname');

        $this->addToDATA($DATA);
    }

    protected function prepareConfirmVisit()
    {
        $this->setTemplate('confirm_visit');
        $this->Mailer->set('receiver', $this->getParameter('receiver'));
        $this->Mailer->set('subject', 'Bekräfta ditt besök!');
        $this->addAsVar($this->getParameter('data'));
    }

    protected function prepareChangedGroupsForUser()
    {
        $DATA = $this->getParameter('data');
        array_walk_recursive(
            $DATA['groups'],
            function (&$g_id) {
                $group = $this->N->ORM->getRepository('Group')->find($g_id);
                $g = ['group_id' => $g_id];
                $g['name'] = $group->getName();
                $g['grade'] = $group->getGradeLabel();
                $g_id = $g;
            }
        );
        $groups = $DATA['groups'];
        $this->setTemplate('changed_groups');
        $this->Mailer->set('receiver', $this->getParameter('receiver'));
        $this->moveFromDataToVar('school_url', 'fname');
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
        $this->Mailer->set('subject', $subject);
        $this->addToDATA($DATA);
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
                $g['grade'] = $group->getGradeLabel();
                $group = $g;
            }
        );

        $this->moveFromDataToVar('school_url', 'fname');
        $this->setTemplate('new_user_welcome');
        $this->Mailer->set('receiver', $this->getParameter('receiver'));
        $this->Mailer->set('subject', 'Välkommen i Naturskolans databas');
        $this->addToDATA($DATA);
    }
}
