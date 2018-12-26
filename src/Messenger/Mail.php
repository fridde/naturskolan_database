<?php


namespace Fridde\Messenger;


use Fridde\Entities\Group;
use Fridde\Entities\Message;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Mailer;

class Mail extends AbstractMessageController
{
    /* @var \Fridde\Mailer $Mailer */
    protected $Mailer;

    public static $methods = [
        Message::SUBJECT_ADMIN_SUMMARY =>
            [self::SEND | self::PREPARE, 'AdminSummary', 'Dagliga sammanfattningen av databasen'],
        Message::SUBJECT_PASSWORD_RECOVERY =>
            [self::SEND | self::PREPARE, 'PasswordRecovery', 'Naturskolan: Återställning av lösenord'],
        Message::SUBJECT_VISIT_CONFIRMATION =>
            [self::SEND | self::PREPARE, 'VisitConfirmation', 'Bekräfta ditt besök!'],
        Message::SUBJECT_PROFILE_UPDATE =>
            [self::SEND | self::PREPARE, 'UpdateProfileReminder', 'Vi behöver mer information från dig!'],
        Message::SUBJECT_CHANGED_GROUPS =>
            [self::SEND | self::PREPARE, 'ChangedGroupsForUser', null],
        Message::SUBJECT_WELCOME_NEW_USER =>
            [self::SEND | self::PREPARE, 'WelcomeNewUser', 'Välkommen i Naturskolans besöksportal'],
        Message::SUBJECT_MANAGER_MOBILIZATION =>
            [self::SEND | self::PREPARE, 'ManagerMobilization', 'Hjälp oss att planera klassernas besök'],
        Message::SUBJECT_USER_REMOVAL_REQUEST =>
            [self::SEND | self::PREPARE, 'UserRemovalRequest', 'Borttagning av användare har begärts']
    ];

    protected $subject_int;


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setSubjectInt();
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
        $this->Mailer->isHTML(true);

        $debug_mail = SETTINGS['debug']['mail'] ?? null;
        $result = $this->Mailer->sendAway($debug_mail);


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
        $this->Mailer->setValue('receiver', $this->getAdminAddress());
        $this->Mailer->setValue('subject', $this->getSubjectString());

        $this->addToDATA($this->getParameter('data'));
    }

    protected function preparePasswordRecovery()
    {
        $template = 'mail/password_recover' . ($this->isHtml() ? '' : '_raw');

        $this->addToDATA($this->getParameter('data'));
        $this->moveFromDataToVar('school_url', 'fname');

        $this->setTemplate($template);
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', $this->getSubjectString());
        $this->Mailer->setValue('SMTPDebug', 0);
    }

    protected function prepareUpdateProfileReminder()
    {
        $template = 'mail/incomplete_profile' . ($this->isHtml() ? '' : '_raw');

        $this->setTemplate($template);
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', $this->getSubjectString());
        $this->addToDATA($this->getParameter('data'));
        $this->moveFromDataToVar('school_url', 'fname');
    }

    protected function prepareVisitConfirmation()
    {

        $template = 'mail/confirm_visit' . ($this->isHtml() ? '' : '_raw');

        $this->setTemplate($template);
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', $this->getSubjectString());
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

        $template = 'mail/changed_groups' . ($this->isHtml() ? '' : '_raw');
        $this->setTemplate($template);
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
            throw new NException(Error::LOGIC, ['no groups for user']);
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

        $template = 'mail/new_user_welcome' . ($this->isHtml() ? '' : '_raw');
        $this->setTemplate($template);
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', $this->getSubjectString());
    }

    protected function prepareManagerMobilization()
    {
        $DATA = $this->getParameter('data');

        $this->addToDATA($DATA);
        $this->moveFromDataToVar('school_url', 'fname');
        $template = 'mail/manager_mobilization' . ($this->isHtml() ? '' : '_raw');
        $this->setTemplate($template);
        $this->Mailer->setValue('receiver', $this->getParameter('receiver'));
        $this->Mailer->setValue('subject', $this->getSubjectString());
    }

    protected function prepareUserRemovalRequest()
    {
        $this->setTemplate('mail/user_removal_request');
        $this->addToDATA($this->getParameter('data'));
        $this->Mailer->setValue('receiver', $this->getAdminAddress());
        $this->Mailer->setValue('subject', $this->getSubjectString());
    }

    public function getMethods(): array
    {
        return self::$methods;
    }

    protected function getSubjectString(int $subject_int = null): string
    {
        $subject_int = $subject_int ?? $this->subject_int;
        return $this->getMethods()[$subject_int][2] ?? '';
    }

    protected function isHtml(): bool
    {
        return $this->getParameter('html') === true;
    }

    public function setSubjectInt(int $subject_int = null): void
    {
        if(null === $subject_int){
            $this->subject_int = (int) $this->getParameter('subject_int');
            return;
        }
        $this->subject_int = $subject_int;
    }

    private function getAdminAddress(): string
    {
        return SETTINGS['admin']['summary']['admin_address'];
    }
}
