<?php

namespace Fridde\Controller;

use Fridde\HTML;
use Fridde\Security\Authorizer;

class TableController extends BaseController
{

    protected $Security_Levels = [
        'view' => Authorizer::ACCESS_ADMIN_ONLY,
    ];

    private $entity_class;
    private $t_settings;
    private $entity_table;
    private $rows = [];

    private $col_order = ['School' => ['first' => ['VisitOrder']]];

    public function view()
    {

        $this->entity_class = ucfirst($this->params['entity']);

        $repo = $this->N->ORM->getRepository($this->entity_class);
        $this->entity_table = $repo->findAll();
        $full_class_name = $repo->getClassName();
        if (empty($this->entity_table)) {
            $this->entity_table[] = new $full_class_name();
        }
        $this->getTableSettings();
        $this->fillInDefaultSettings();
        $this->retrieveOptions();
        $this->reorderColumns();
        $this->buildRows();

        // $this->addToDATA('', );
        $this->addToDATA('headers', array_keys($this->t_settings['columns']));
        $this->addToDATA('rows', $this->rows);
        $this->addToDATA('entity_class', $this->entity_class);
        $this->addToDATA('sortable', $this->t_settings['sortable'] ?? false);
        $this->addToDATA('school_id', $this->Authorizer->getVisitor()->getSchool()->getId());

        $this->setTemplate('table');


        //$this->setJs(['js.bs.date', 'js.bs.date.sv', 'js.DT', 'js.DT.config']);
        $this->addJs('DT', HTML::INC_ABBREVIATION);
        $this->addJs('js/DT_config', HTML::INC_ADDRESS);

        //$this->setCss(['css.bs.date', 'css.DT']);
        $this->setCss(['DT']);
    }

    private function buildRows()
    {
        foreach ($this->entity_table as $entity) {
            $row = [];
            foreach ($this->t_settings['columns'] as $property => $settings) {
                $value_function = explode('#', $settings['value']);
                $callback = [$entity, $value_function[0]];
                $param = $value_function[1] ?? null;
                $settings['value'] = call_user_func($callback, $param);
                $row[$property] = $settings;
            }
            $this->rows[] = $row;
        }
    }

    private function getTableSettings()
    {
        $cols = $this->t_settings['columns'] ?? [];
        $cols['id']['value'] = 'getId';
        $cols['id']['type'] = 'ignored';

        if ($this->isOneOf('User')) {
            array_push($cols, 'FirstName', 'LastName');
            $cols['Mobil']['type'] = 'tel';
            $cols['Mail']['type'] = 'email';
            $cols['Role']['options'] = 'getRoleLabels';
            $cols[] = 'Acronym';
        }

        if ($this->isOneOf('Group', 'Location', 'School')) {
            $cols[] = 'Name';
        }

        if ($this->isOneOf('Group')) {
            $cols['User']['value'] = 'getUserId';
            $cols['User']['options'] = ['User', 'findAllUsersWithSchools'];
            $cols['StartYear']['type'] = 'integer';
            $cols['NumberStudents']['type'] = 'integer';
            $cols['Food']['type'] = 'textarea';
            $cols['Info']['type'] = 'textarea';
            $cols['Notes']['type'] = 'textarea';
        }
        if ($this->isOneOf('User', 'Group')) {
            $cols['Status']['options'] = 'getStatusOptions';
            $cols['LastChange']['type'] = 'readonly';
            $cols['CreatedAt']['type'] = 'readonly';
        }

        if ($this->isOneOf('User', 'Group', 'Cookie')) {
            $cols['School']['value'] = 'getSchoolId';
            $cols['School']['options'] = ['School', 'findAllSchoolLabels'];
        }

        if ($this->isOneOf('Topic', 'Group')) {
            $cols['Grade']['options'] = 'getGradeLabels';
        }

        if ($this->isOneOf('Topic', 'School')) {
            $cols['VisitOrder']['type'] = 'readonly';
            $this->t_settings['sortable'] = true;
        }

        if ($this->isOneOf('Topic')) {
            array_push($cols, 'ShortName', 'LongName');
            $cols['Location']['value'] = 'getLocationId';
            $cols['Location']['options'] = ['Location', 'findAllLocationLabels'];
            array_push($cols, 'Food', 'Url');
            $cols['IsLektion']['type'] = 'radio';
            $cols['IsLektion']['options'] = 'getIsLektionOptions';
        }

        if ($this->isOneOf('Location', 'School')) {
            $cols[] = 'Coordinates';
        }

        if ($this->isOneOf('Location')) {
            $cols['Description']['type'] = 'textarea';
            $cols['BusId']['type'] = 'readonly';
        }

        if ($this->isOneOf('Cookie')) {
            array_push($cols, 'Value', 'Name');
            $cols['Rights']['options'] = 'getRightsOptions';
        }

        if ($this->isOneOf('School')) {
            $cols['BusRule']['type'] = 'integer';
            $cols['GroupNumbers']['value'] = 'getGroupNumbersAsString';
            $cols['GroupNumbers']['type'] = 'readonly';
        }

        if ($this->isOneOf('Visit')) {
            $cols['Group']['value'] = 'getGroupId';
            $cols['Group']['options'] = ['Group', 'findAllGroupsWithNameAndSchool'];
            $cols['Date']['value'] = 'getDateString';
            $cols['Date']['type'] = 'date';
            $cols['Topic']['value'] = 'getTopicId';
            $cols['Topic']['options'] = ['Topic', 'findLabelsForTopics'];
            $cols['Colleagues']['value'] = 'getColleaguesAsAcronymString';
            $cols['Colleagues']['type'] = 'readonly';
            $cols['Confirmed']['type'] = 'checkbox';
            $cols['Confirmed']['options'] = 'getConfirmedOptions';
            //$cols['Time']['type'] = 'time';
            // TODO: implement time-picker
            $cols[] = 'Time';
        }

        if ($this->isOneOf('Event')) {
            $cols[] = 'Title';
            $cols['StartDate']['type'] = 'date';
            $cols['StartDate']['value'] = 'getStartDateString';
            $cols[] = 'StartTime';
            $cols['EndDate']['type'] = 'date';
            $cols['EndDate']['value'] = 'getEndDateString';
            array_push($cols, 'EndTime', 'Description', 'Location');
        }

        $this->t_settings['columns'] = $cols;
    }

    private function isOneOf(...$entities)
    {
        return in_array($this->entity_class, $entities, true);
    }

    private function reorderColumns()
    {
        $original_columns = array_keys($this->t_settings['columns']);
        $first = $this->col_order[$this->entity_class]['first'] ?? [];
        $last = $this->col_order[$this->entity_class]['last'] ?? [];
        $rest = array_diff($original_columns, $first, $last);

        $new_order = array_unique(array_merge($first, $rest, $last));
        $new_settings = [];
        foreach ($new_order as $property) {
            $new_settings[$property] = $this->t_settings['columns'][$property];
        }
        $this->t_settings['columns'] = $new_settings;
    }

    private function fillInDefaultSettings()
    {
        $cols = $this->t_settings['columns'];
        $return = [];
        foreach ($cols as $key => $value) {
            if (is_int($key)) {
                $key = $value;
            }
            $value = $cols[$key]['value'] ?? 'get'.$key;
            $options = $cols[$key]['options'] ?? null;
            $type = $cols[$key]['type'] ?? null;

            if (empty($type)) {
                $type = empty($options) ? 'text' : 'select';
            }
            $return[$key] = compact('value', 'options', 'type');
        }
        $this->t_settings['columns'] = $return;
    }

    private function retrieveOptions()
    {
        $cols = $this->t_settings['columns'];

        foreach ($cols as $name => $val) {
            if (!empty($val['options'])) {
                if (is_array($val['options'])) {
                    $repo = $this->N->ORM->getRepository($val['options'][0]);
                    $cols[$name]['options'] = call_user_func([$repo, $val['options'][1]]);
                } elseif (is_string($val['options'])) {
                    $entity = $this->N->ORM->getRepository($this->entity_class)->findOneBy([]);
                    $cols[$name]['options'] = call_user_func([$entity, $val['options']]);
                } else {
                    throw new \Exception(
                        'Options could not be retrieved for parameter '.var_export($val['options'], true)
                    );
                }
            }
        }
        $this->t_settings['columns'] = $cols;
    }

}
