<?php

namespace qtype_sqlrunner;

require_once $CFG->dirroot . '/question/type/sqlrunner/classes/sandbox.php';

class database_result {

    const STATUS_OK = \qtype_sqlrunner_sandbox::OK;
    const STATUS_ERROR = \qtype_sqlrunner_sandbox::UNKNOWN_SERVER_ERROR;

    public $status = null;
    public $data = null;
    public $fields = null;
    public $error = null;

    public function __construct($status = null, $error = null){
        $this->status = $status;
        $this->error = $error;
    }

    public static function error_result($message = ''){
        return new static(static::STATUS_ERROR, $message);
    }

    /**
     * @param \mysqli $mysqli
     */
    public function parse_data($mysqli){
        $this->data = [];
        $this->fields = [];
        $result = $mysqli->store_result();

        if (empty($result)) return;

        if ($result->num_rows <= ROWS_LIMIT){
            $this->data = $result->fetch_all();
        } else {
            $i = 0;
            while (($row = $result->fetch_row()) && ($i++) < ROWS_LIMIT){
                $this->data[] = $row;
            }
        }

        $this->data = !empty($this->data) ? $this->data : \qtype_sqlrunner\constants::EMPTY_SET;

        $fields = $result->fetch_fields();
        foreach ($fields as $field){
            $result = $field->name;
            $this->fields[] = $result;
        }
    }

    public function is_error(){
        return $this->status == static::STATUS_ERROR;
    }

    public function encode_data(){
        return encode_sql_output([
            'fields' => $this->fields,
            'data'   => $this->data,
        ]);
    }
}