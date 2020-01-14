<?php


namespace Fridde;


use Fridde\Entities\Event;
use Fridde\Entities\Visit;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Symfony\Component\Yaml\Yaml;

class Table
{
    public $ORM;
    private $entity_class;
    private $settings;
    private $column_settings;

    public function __construct(string $entity_class, ORM $ORM)
    {
        $this->ORM = $ORM;
        $this->entity_class = ucfirst($entity_class);
    }

    public function build(array $parameters = [])
    {
        $this->fillInDefaultSettings();
        $this->insertOptionValues();

        return $this->buildTableValues();
    }

    private function getSettingsFromFile(): array
    {
        $file_path = BASE_DIR.'/config/table_settings.yml';

        return Yaml::parseFile($file_path);
    }

    private function fillInDefaultSettings()
    {
        $return = [];
        foreach ($this->getColumnSettings() as $col_name => $content) {
            $value = $content['value'] ?? 'get'.$col_name;
            $options = $content['options'] ?? null;
            $type = $content['type'] ?? null;

            if (empty($type)) {
                $type = empty($options) ? 'text' : 'select';
            }
            $return[$col_name] = compact('value', 'options', 'type');
        }

        $this->column_settings = $return;
    }

    private function insertOptionValues()
    {
        $column_settings = $this->getColumnSettings();

        foreach ($column_settings as $col_name => $content) {
            $options = $content['options'];
            if (empty($options)) {
                continue;
            }
            if (is_string($options)) {
                $class_name = $this->getFullyQualifiedClassName();
                $options = call_user_func([$class_name, $options]);
            } elseif (is_array($options)) {
                $repo = $this->ORM->getRepository($options[0]);
                $options = call_user_func([$repo, $options[1]]);
            }
            $column_settings[$col_name]['options'] = $options;
        }

        $this->column_settings = $column_settings;
    }

    public function isSortable(): bool
    {
        return in_array($this->entity_class, $this->getSettings('_sortable_tables'), true);
    }

    private function buildRow($entity): array
    {
        $row = [];
        foreach ($this->getColumnSettings() as $col_name => $col_settings) {
            $cell = $col_settings;
            $cell['value'] = call_user_func([$entity, $cell['value']]);
            $row[$col_name] = $cell;
        }

        return $row;
    }

    private function buildTableValues(): array
    {
        $entities = $this->ORM->getRepository($this->entity_class)->findAll();
        $entities = $this->sortEntities($entities);
        $entities = $this->ensureAtLeastOne($entities);

        $rows = array_map(
            function ($e) {
                return [$e->getId(), $this->buildRow($e)];
            },
            $entities
        );

        return array_column($rows, 1, 0);
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->ORM->qualifyEntityClassname($this->getEntityClass());
    }

    public function getColumnHeaders()
    {
        $columns = array_filter(
            $this->getColumnSettings(),
            function ($column) {
                return $column['type'] !== 'ignored';
            }
        );


        return array_keys($columns);
    }

    private function ensureAtLeastOne(array $entities): array
    {
        if (empty($entities)) {
            $class_name = $this->getFullyQualifiedClassName();
            $entities[] = new $class_name();
        }

        return $entities;
    }

    private function getSettings(...$path_args): ?array
    {
        if (empty($this->settings)) {
            $this->settings = $this->getSettingsFromFile();
        }
        $array = $this->settings;
        foreach ($path_args as $key) {
            $array = &$array[$key];
        }

        return ($array ?? null);

    }

    private function setColumnsFromSettings(): void
    {
        $this->column_settings = array_reduce(
            $this->getSettings($this->entity_class),
            function ($array, $value) {
                if (is_string($value)) {
                    $array[$value] = [];

                    return $array;
                }
                if (is_array($value)) {
                    reset($value);
                    $array[key($value)] = reset($value);

                    return $array;
                }
                throw new NException(Error::INVALID_OPTION, [var_export($value, true)]);
            },
            []

        );
    }

    private function getColumnSettings(): array
    {
        if (empty($this->column_settings)) {
            $this->setColumnsFromSettings();
        }

        return $this->column_settings;

    }

    public function getEntityClass()
    {
        return $this->entity_class;
    }

    private function sortEntities(array $entities): array
    {
        $sorting_function = function ($e1, $e2) {
            try {
                switch ($this->getFullyQualifiedClassName()) {
                    case Visit::class:
                        if(empty($e1->getGroup())){
                            return -1;
                        }
                        if(empty($e2->getGroup())){
                            return 1;
                        }

                        $school_diff = strcasecmp(
                            $e1->getGroup()->getSchool()->getId(),
                            $e2->getGroup()->getSchool()->getId()
                        );
                        if ($school_diff !== 0) {
                            return $school_diff;
                        }
                        $seg_diff = strcasecmp($e1->getGroup()->getSegment(), $e2->getGroup()->getSegment());
                        if ($seg_diff !== 0) {
                            return $seg_diff;
                        }
                        $name_diff = strcasecmp($e1->getGroup()->getName(), $e2->getGroup()->getName());
                        if ($name_diff !== 0) {
                            return $name_diff;
                        }

                        return $e1->getTopic()->getVisitOrder() - $e2->getTopic()->getVisitOrder();
                        break;
                    case Event::class:
                        return strcasecmp($e1->getStartDate(), $e2->getStartDate());
                        break;
                    default:
                        return 0;
                }
            } catch (\Exception $e) {
            }

            return 0;
        };

        usort($entities, $sorting_function);

        return $entities;

    }


}
