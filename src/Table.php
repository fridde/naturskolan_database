<?php


namespace Fridde;


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

    public function build()
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
        $entities = $this->ensureAtLeastOne($entities);

        return array_map(
            function ($e) {
                return $this->buildRow($e);
            },
            $entities
        );
    }

    public function getFullyQualifiedClassName()
    {
        return $this->ORM->qualifyEntityClassname($this->getEntityClass());
    }

    public function getColumnHeaders()
    {
        return array_keys($this->getColumnSettings());
    }

    private function ensureAtLeastOne(array $entities)
    {
        if (empty($entities)) {
            $class_name = $this->getClassName();
            $entities[] = new $class_name();
        }

        return $entities;
    }

    private function getSettings(...$path_args)
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

    private function setColumnsFromSettings()
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
                $msg = 'The value '.var_export($value, true).' was neither string nor array.';
                throw new \InvalidArgumentException($msg);
            },
            []

        );
    }

    private function getColumnSettings()
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


}
