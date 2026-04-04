<?php

namespace local_sql\core\model;

/**
 * Moodle base class for all custom entities
 *
 * @package     local_sql
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_object {
    use config, database_config;

    public function __construct($config = []){
        $this->set_config($config);
    }

    /**
     * Process record from any object which contains necessary fields
     *
     * @param \stdClass $record record with necessary field values
     * @param array $aliases used when the field names in base_object class and record do not match
     *
     * @return static
     */
    public static function get_from_multirecord($record, $aliases = []){
        // TODO: move all fields into new method and contain fields values into inner array
        $fields = get_class_vars(static::class);
        unset($fields['table']);

        $object = new static();
        $object->_before_init();
        foreach ($fields as $field => $not_used){
            $fieldname = array_key_exists($field, $aliases) ? $aliases[$field] : $field;
            $object->$field = $record->$fieldname;
        }
        $object->_after_init();
        return $object;
    }

    public static function get_by_id($id){
        return static::get(['id' => $id]);
    }

    /**
     * Get single record (only first record will be returned)
     *
     * @param $conditions
     * @param $sort
     *
     * @return static|null
     */
    public static function get($conditions, $sort = ''){
        if (empty($conditions)){
            return null;
        }

        $records = static::get_records($conditions, $sort);
        $record = reset($records);
        return static::_create_from_record($record);
    }

    /**
     * @param $sql
     * @param $params
     * @param $sort
     *
     * @return static[]
     */
    public static function get_select($sql, $params = [], $sort = '', $indexed_by = ''){
        if (empty($sql)){
            return [];
        }

        $records = static::get_records_select($sql, $params, $sort);
        return static::_process_records($records, $indexed_by);
    }

    /**
     * @param array  $conditions
     * @param string $sort
     * @param string $indexed_by
     *
     * @return static[]
     */
    public static function get_all($conditions = [], $sort = '', $indexed_by = ''){
        $records = static::get_records($conditions, $sort);
        return static::_process_records($records, $indexed_by);
    }

    /**
     * Process database records to the {@see base_object} objects
     *
     * @param $records
     * @param $indexed_by
     *
     * @return array
     */
    protected static function _process_records($records, $indexed_by = ''){
        $result = [];
        if (!empty($indexed_by)){
            foreach ($records as $record){
                $result[$record->$indexed_by] = static::_create_from_record($record);
            }
        } else {
            foreach ($records as $record){
                $result[] = static::_create_from_record($record);
            }
        }

        return $result;
    }

    /**
     * @param object|false $record
     *
     * @return static|null
     */
    protected static function _create_from_record($record){
        if (!$record){
            return null;
        }

        return new static($record);
    }

    public static function create(array $config){
        $object = new static($config);
        $object->save();
        return $object;
    }

    public function compare(base_object $base_object): array{
        $updated = [];
        foreach ($base_object as $field => $value){
            if ($this->$field != $value){
                $updated[$field] = $value;
            }
        }
        return $updated;
    }
}