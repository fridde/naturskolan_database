<?php

namespace Fridde\TwigExtension;


use Fridde\Entities\SchoolRepository;
use Fridde\Entities\User;
use Fridde\ORM;
use Fridde\Router;
use Fridde\Security\Authorizer;
use Fridde\TwigBaseExtension;

class NavigationExtension extends TwigBaseExtension
{
    protected Authorizer $Auth;
    protected Router $Router;
    protected ORM $ORM;

    public const METHOD_DELIMITER = '->';
    public const ARG_DELIMITER = ',';

    public function __construct(Authorizer $auth, Router $router, ORM $orm)
    {
        parent::__construct();
        $this->Auth = $auth;
        $this->Router = $router;
        $this->ORM = $orm;
    }

    public function getName(): string
    {
        return 'navigation_extension';

    }

    public function defineFunctions(): array
    {
        $fnc_names = [
            'getNavItems',
            'getAllSchoolUrls',
            'getAllTableUrls',
            'getChildrenUsingMethod',
            'getRole',
            'getUrlUsingMethod',
        ];

        return $this->getDefinitionArray($fnc_names);
    }

    public function defineTests(): array
    {
        $test_names = [
            ['method', 'testIfMethod'],
        ];

        return $this->getDefinitionArray($test_names);
    }

    public function testIfMethod($element_to_test): bool
    {
        if (!is_string($element_to_test)) {
            return false;
        }

        return substr_count($element_to_test, self::METHOD_DELIMITER) >= 1;
    }

    public function getMinSecurityLevel(): int
    {
        return $this->Auth->getVisitorSecurityLevel();
    }


    public function getNavItems(): array
    {
        $min_security_level = $this->Auth->getVisitorSecurityLevel();

        $menu_items = self::getNavSettings($min_security_level);
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

        return $this->$method_name();
    }

    public function getUrlUsingMethod(array $item, array $data_array = null)
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


    public function getSchoolPageUrl(string $school_id = null): string
    {
        if(empty($school_id)){
            return '';
        }

        return $this->Router->generate('school', ['school' => $school_id]);
    }

    public function getAllSchoolUrls(array $ignored_schools = []): array
    {
        /* @var SchoolRepository $school_repo */
        $school_repo = $this->ORM->getRepository('School');

        $school_labels = $school_repo->findAllSchoolLabels();
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


    private static function getNavSettings(int $min_security_level = null): array
    {
        $nav_role = self::getNavRoleAsString($min_security_level);

        return SETTINGS['NAV_SETTINGS'][$nav_role] ?? SETTINGS['NAV_SETTINGS'];
    }

    private static function getNavRoleAsString(int $min_security_level = null): string
    {
        $role_mapping = [
            'guest' => [Authorizer::ROLE_GUEST],
            'user' => [User::ROLE_STAKEHOLDER, User::ROLE_TEACHER, User::ROLE_SCHOOL_MANAGER],
            'admin' => [User::ROLE_ADMIN, User::ROLE_SUPERUSER],
        ];

        foreach ($role_mapping as $string => $roles) {
            if (in_array($min_security_level, $roles, true)) {
                return $string;
            }
        }

        return 'guest';
    }

}
