<?php

namespace local_sql\core\model;

use dml_exception;

/**
 * Trait for database work
 *
 * TODO: Generate xml table definition from model fields
 */
trait database_config {

    static protected string $table;

    public static function table(): string{
        return static::$table;
    }

    public static function process_conditions($conditions = []){
        global $DB;

        $where = $params = [];
        /** check from {@see \moodle_database::where_clause()}*/
        foreach ($conditions as $key => $value){
            if (is_int($key)){
                throw new dml_exception('invalidnumkey');
            }

            if (is_null($value)){
                $where[] = "$key IS NULL";
            } elseif (is_array($value)) {
                $normkey = trim(preg_replace('/[^a-zA-Z0-9_-]/', '_', $key), '-_');
                [$sql, $local_params] = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED, 'f'.$normkey);
                $where[] = "$key $sql";
                $params = array_merge($params, $local_params);
            } elseif ($key == 'sql') {
                $where[] = $value;
            } else {
                // Need to verify key names because they can contain, originally,
                // spaces and other forbidden chars when using sql_xxx() functions and friends.
                $normkey = trim(preg_replace('/[^\.a-zA-Z0-9_-]/', '_', $key), '-_');
                if ($normkey !== $key){
                    debugging('Invalid key found in the conditions array.');
                }
                // allow to use 'u.name' as array key (parameter name)
                $normkey = str_replace('.', '_', $normkey);
                $where[] = "$key = :$normkey";
                $params[$normkey] = $value;
            }
        }

        return [
            implode(' AND ', $where),
            $params,
        ];
    }

    public static function get_records($conditions = [], $sort = ''){
        [$where, $params] = static::process_conditions($conditions);
        return static::get_records_select($where, $params, $sort);
    }

    public static function get_records_select($sql, $params = [], $sort = ''){
        global $DB;
        return $DB->get_records_select(static::table(), $sql, $params, $sort);
    }

    public function delete(){
        global $DB;
        if (!empty($this->id)){
            $DB->delete_records(static::table(), ['id' => $this->id]);
        }
    }

    public static function delete_records(array $conditions){
        if (empty($conditions)){
            return;
        }

        [$where, $params] = static::process_conditions($conditions);
        static::delete_records_select($where, $params);
    }

    public static function delete_records_select($sql, $params = []){
        global $DB;
        if (empty($sql)){
            return;
        }

        $DB->delete_records_select(static::table(), $sql, $params);
    }

    public function save(){
        global $DB;
        if (empty($this->id)){
            $this->id = $DB->insert_record_raw(static::table(), $this);
            return;
        }

        $DB->update_record(static::table(), $this);
    }
}