<?php

namespace Fridde\TwigExtension;

use Fridde\Authorization;
use Fridde\ORM;
use Fridde\TwigBaseExtension;

class NavigationExtension extends TwigBaseExtension
{
    protected $Auth;
    protected $Router;
    protected $ORM;

    public const METHOD_DELIMITER = '->';
    public const ARG_DELIMITER = ',';

    public function __construct(Authorization $auth, \AltoRouter $router, ORM $orm)
    {
        parent::__construct();
        $this->Auth = $auth;
        $this->Router = $router;
        $this->ORM = $orm;
    }

    public function getName()
    {
        return 'navigation_extension';

    }

    public function defineFunctions()
    {
        $fnc_names = [
            'getNavItems',
            'getStaffPageUrl',
            'getAllSchoolUrls',
            'getAllTableUrls',
            'getGroupsPageUrl',
            'getChildrenUsingMethod',
            'getRole',
            'getUrlUsingMethod',
        ];

        return $this->getDefinitionArray($fnc_names);
    }

    public function defineFilters()
    {
        return [];
    }

    public function defineTests()
    {
        $test_names = [
            ['method', 'testIfMethod'],
        ];

        return $this->getDefinitionArray($test_names);
    }

    public function testIfMethod($element_to_test)
    {
        if (!is_string($element_to_test)) {
            return false;
        }

        return substr_count($element_to_test, self::METHOD_DELIMITER) >= 1;
    }

    public function getRole()
    {
        return $this->Auth->getUserRole();
    }


    public function getNavItems($role = 'guest')
    {
        $menu_items = self::getNavSettings($role);
        $default = ['children' => [], 'url' => '#'];

        return array_map(
            function ($item) use ($default) {
                return ($item + $default);
            },
            $menu_items
        );
    }

    public function getChildrenUsingMethod(array $item)
    {
        $method_name = explode(self::METHOD_DELIMITER, $item['children'])[1];

        return call_user_func([$this, $method_name]);
    }

    public function getUrlUsingMethod(array $item, $data_array = null)
    {
        $data_array = $data_array ?? [];
        $method_and_args = explode(self::METHOD_DELIMITER, $item['url'])[1];
        $m_and_a = $this->extractMethodAndArgs($method_and_args);
        $method = $m_and_a['method'];
        $args = [];
        foreach ($m_and_a['args'] as $arg_name) {
            $args[] = $data_array[$arg_name] ?? null;
        }

        return call_user_func_array([$this, $method], $args);
    }

    private function extractMethodAndArgs(string $method_and_args): array
    {
        $left_pos = strpos($method_and_args, '(');
        $right_pos = strrpos($method_and_args, ')');
        if ($left_pos === false || $right_pos === false) {
            $method = $method_and_args;
            $args = [];
        } else {
            $method = substr($method_and_args, 0, $left_pos);
            $arg_str = substr($method_and_args, $left_pos + 1, $right_pos - $left_pos - 1);
            $args = explode(self::ARG_DELIMITER, $arg_str);
        }

        return compact('method', 'args');
    }

    public function getAllTableUrls()
    {
        $configurable_tables = SETTINGS['admin']['table_menu_items'];

        return array_map(
            function ($table) {
                $r['label'] = $table;
                $r['url'] = $this->Router->generate('table', ['entity' => $table]);

                return $r;
            },
            $configurable_tables
        );
    }


    public function getGroupsPageUrl(string $school_id = null)
    {
        return $this->getSchoolPageUrl($school_id, 'groups');
    }

    public function getStaffPageUrl(string $school_id = null)
    {
        return $this->getSchoolPageUrl($school_id, 'staff');
    }

    public function getSchoolPageUrl(string $school_id = null, string $page = 'groups')
    {
        if(empty($school_id)){
            return null;
        }
        $params['school'] = $school_id;
        $params['page'] = $page;

        return $this->Router->generate('school', $params);
    }

    public function getAllSchoolUrls(array $ignored_schools = [])
    {
        /* @var \Fridde\Entities\School[] $school_labels */
        $school_labels = $this->ORM->getRepository('School')->findAllSchoolLabels();
        $school_labels = array_diff_key($school_labels, array_flip($ignored_schools));

        return array_map(
            function ($id, $label) {
                $r['label'] = $label;
                $r['url'] = $this->Router->generate('school', ['school' => $id]);

                return $r;
            },
            array_keys($school_labels),
            $school_labels
        );

    }


    private static function getNavSettings(string $key = null)
    {
        return SETTINGS['NAV_SETTINGS'][$key] ?? SETTINGS['NAV_SETTINGS'];
    }

}
