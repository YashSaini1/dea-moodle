<?php

namespace qtype_pythonrunner;

class python_database_result extends \qtype_sqlrunner\database_result {
    /**
     * @param \mysqli $mysqli
     */
    public function parse_data($mysqli){
        $this->data = [];
        $this->fields = [];
        $result = $mysqli->store_result();

        if (empty($result)) return;

        $this->data = $result->fetch_all(MYSQLI_ASSOC);
        $this->data = !empty($this->data) ? $this->data : \qtype_sqlrunner\constants::EMPTY_SET;

        $fields = $result->fetch_fields();
        foreach ($fields as $field){
            $result = $field->name;
            $this->fields[] = $result;
        }
    }
}