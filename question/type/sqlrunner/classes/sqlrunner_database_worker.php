<?php

namespace qtype_sqlrunner;

use \Exception;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;

class sqlrunner_database_worker {

    protected $_database_timeout;

    /**
     * @var sqlrunner_database_worker Singleton instance
     */
    protected static $_instance = null;

    protected $_config = null;   // Custom Config object

    protected $_inited = null;   // Flag is connection successful or not

    protected $_transaction = null;   // Flag is connection successful or not

    protected $_is_admin = false;   // Flag we need to run queries as admin or not

    protected $_result_class = database_result::class;   // Result data class

    protected function __construct($config = null, $is_admin = false){
        $this->_config = $config ?? get_config(SQL_RUNNER);
        $this->_database_timeout = $this->_config->wsmaxcputime;
        $this->_is_admin = $is_admin;
    }

    public function set_result_class($result_class){
        if (check_subclass($result_class, \qtype_sqlrunner\database_result::class)){
            $this->_result_class = $result_class;
        }
    }

    protected function _result_success($status = null){
        return new $this->_result_class($status);
    }

    protected function _result_error($error = null){
        return $this->_result_class::error_result($error);
    }

    /**
     * @param null|object $config
     *
     * @return sqlrunner_database_worker
     */
    public static function get_instance($config = null, $is_admin = false){
        if (is_null(static::$_instance)){
            static::$_instance = new static($config, $is_admin);
        } elseif (!empty($config)) {
            debugging('DB worker instance was already inited');
        }
        return static::$_instance;
    }

    public function init_connection(){
        if ($this->_inited === false){
            return false;
        }

        $dbhost = $this->_config->db_host;
        $dbport = $this->_config->db_port;
        $dbname = $this->_config->db_name;
        if (!$this->_is_admin){
            $dbuser = $this->_config->db_user;
            $dbpass = $this->_config->db_pass;
        } else {
            $dbuser = $this->_config->db_root_user;
            $dbpass = $this->_config->db_root_pass;
        }

        if (empty($dbname) || empty($dbuser) || empty($dbhost)){
            throw new \dml_connection_exception('error_wrong_database_connection_parameters');
        }

        $mysqli = $this->_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport);
        // Connection failed
        if (is_string($mysqli)){
            $this->_inited = false;
            throw new \dml_connection_exception($mysqli);
        }

        $this->_inited = true;

        return $mysqli;
    }

    /**
     * @param string  $dbhost
     * @param string  $dbuser
     * @param string  $dbpass
     * @param string  $dbname
     * @param numeric $dbport
     *
     * @return false|\mysqli|string
     */
    protected function _connect($dbhost, $dbuser, $dbpass, $dbname = null, $dbport = null){
        // The dbsocket option is used ONLY if host is null or 'localhost'.
        // You can not disable it because it is always tried if dbhost is 'localhost'.
        $dbsocket = ini_get('mysqli.default_socket');
        if (empty($dbport)){
            $dbport = 3306;
        }

        /** Do not set MYSQLI_REPORT_ALL because function mysqli_store_result() throws an Exception
         * @see database_result::parse_data()
         */
        mysqli_report(MYSQLI_REPORT_STRICT);

        $mysqli = mysqli_init();

        $flags = null;
        if (!$this->_is_admin){
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->_database_timeout);
            $mysqli->options(MYSQLI_OPT_READ_TIMEOUT,  $this->_database_timeout);
            $flags = MYSQLI_CLIENT_INTERACTIVE;
        }

        $conn = $dberr = null;
        try {
            // real_connect() is doing things we don't expext.
            $conn = $mysqli->real_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsocket, $flags);
        } catch (\Exception $e){
            $dberr = "$e";
        }

        if (!$conn){
            $dberr = $dberr ?: $mysqli->connect_error;
            $mysqli = null;
            return $dberr;
        }

        return $mysqli;
    }

    /**
     * @param string $dbname
     *
     * @return bool
     */
    public function create_database($dbname, $user_name, $user_pass){
        global $DB;
        $dboptions = [
            'dbport' => $this->_config->db_port,
        ];

        try {
            $DB->create_database($this->_config->db_host, $this->_config->db_root_user, $this->_config->db_root_pass, $dbname, $dboptions);
            $this->_create_db_readonly_user($dbname, $user_name, $user_pass);
            $this->_is_admin = true;
        } catch (Exception $e){
            // wrong connection or database with this name|this database user already exists
            debugging($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Create moodle execution user which can use only select queries
     */
    protected function _create_db_readonly_user($dbname, $username, $pass){
        $sql = "CREATE USER '$username'@'%' IDENTIFIED BY '$pass';
                GRANT SELECT, SHOW VIEW ON *.* TO '$username'@'%';
                FLUSH PRIVILEGES;";
        $this->_run_database_query($sql, $dbname);
    }

    /**
     * @param string $dbname
     *
     * @return database_result
     */
    public function check_database_exists($dbname){
        $query = "USE $dbname";
        return $this->_run_database_query($query, '');
    }

    /**
     * @param string $dbname
     *
     * @return database_result
     */
    public function delete_database($dbname){
        $query = "DROP DATABASE $dbname";
        return $this->_run_database_query($query, $dbname);
    }

    /**
     * Create a simple query with database manipulation
     *
     * @param string $query
     * @param string $dbname
     *
     * @return database_result
     */
    protected function _run_database_query($query, $dbname = null){
        if (is_null($dbname)){
            $dbname = $this->_config->db_name;
        }
        $dbhost = $this->_config->db_host;
        $dbport = $this->_config->db_port;
        if (!$this->_is_admin){
            $dbuser = $this->_config->db_user;
            $dbpass = $this->_config->db_pass;
        } else {
            $dbuser = $this->_config->db_root_user;
            $dbpass = $this->_config->db_root_pass;
        }
        $mysqli = $this->_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport);

        // Connection to DB failed or no database
        if (is_string($mysqli)){
            throw new \dml_connection_exception($mysqli);
        }

        return $this->_execute_query($mysqli, $query);
    }

    /**
     * Delete many tables
     *
     * @param string[]|string $tables
     *
     * @return database_result
     */
    public function delete_tables($tables){
        if (is_array($tables)){
            $tables = implode(',', $tables);
        }

        $mysqli = $this->init_connection();
        $query = "DROP TABLES IF EXISTS $tables";
        return $this->_execute_query($mysqli, $query);
    }

    /**
     * Delete single table
     *
     * @param string $table
     *
     * @return database_result
     */
    public function delete_table($table){
        return $this->delete_tables($table);
    }

    /**
     * Function used method @see static::init_connection() which requires dbname in config
     *
     * @param string $query SQL query
     *
     * @return database_result
     */
    public function run_query($query){
        $mysqli = $this->init_connection();
        return $this->_execute_query($mysqli, $query);
    }

    /**
     * @param \mysqli $mysqli
     * @param string  $query
     *
     * @return database_result
     */
    protected function _execute_query($mysqli, $query, $closemysqli = true){
        try {
            if (!$this->_is_admin){
                $query = $this->_preprocess_query($query);
            }

            $query_time = hrtime(1);
            $query_status = $mysqli->multi_query($query);
        } catch (Exception $e){
            $query_status = false;
        }

        $query_time = (hrtime(1) - $query_time) / 1000000000; // track query time execution to detect timeout message

        if (!$query_status){
            $error_message = $mysqli->error;
            if ($query_time >= $this->_database_timeout && !$this->_is_admin){
                $error_message = sqlrunner_str('error:database_timeout', ['timeout' => $this->_database_timeout]);
            }
            return $this->_result_error($error_message);
        }

        $result = $this->_result_success(database_result::STATUS_OK);
        $result->parse_data($mysqli);
        if ($closemysqli){
            $this->close_mysqli($mysqli);
        }
        return $result;
    }

    /**
     * Add Limit statement to user query
     *
     * @param string $query
     *
     * @return string
     */
    protected function _preprocess_query($query){
        \PhpMyAdmin\SqlParser\Context::load('\\PhpMyAdmin\\SqlParser\\Contexts\\ContextMySql80000');
        $parser = new Parser($query);

        if (empty($parser->statements)){
            return $query;
        }
        $statement = $parser->statements[0]; // use only first statement, because only first query runs

        if (!is_a($statement, SelectStatement::class)){
            return $query;
        }
        /** @var \PhpMyAdmin\SqlParser\Components\Limit|null $limit */
        $limit = &$statement->limit;
        if (!$limit){
            $limit = new \PhpMyAdmin\SqlParser\Components\Limit(ROWS_LIMIT);
        } elseif ($limit->rowCount > ROWS_LIMIT) {
            $limit->rowCount = ROWS_LIMIT;
        }

        return $statement->build();
    }

    /**
     * @param \stored_file[] $files
     *
     * @return \mysqli|bool or throw an Exception
     */
    public function process_uploaded_files($files, $mysqli = null, $tables = []){
        $mysqli = $mysqli ?? $this->init_connection();
        if (!$mysqli){
            return false;
        }
        $files = array_values($files);

        $delete_tables = [];
        foreach ($files as $key => $file){
            $table = $tables[$key] ?? null;
            if (!empty($table)){
                $delete_tables[] = implode(',', $table);
            }

            $sql_queries = explode(';', $file->get_content());
            foreach ($sql_queries as $code_query){
                if (empty(trim($code_query))){
                    continue;
                }

                $result = $this->_execute_query($mysqli, $code_query, false);
                if ($result->is_error()){
                    $this->delete_tables($delete_tables);
                    throw new \moodle_exception('exception_sql_file_run_failed', SQL_RUNNER, '',
                        ['filename' => $file->get_filename(), 'error' => $result->error]);
                }
                $this->_mysqli_free_results($mysqli);
            }
        }

        return $mysqli;
    }

    /**
     * Free mysqli output after table creation and filling
     *
     * @param \mysqli $mysqli
     */
    protected function _mysqli_free_results($mysqli){
        do {
            if ($result = $mysqli->store_result()){while ($row = $result->fetch_row()){}}
        } while ($mysqli->next_result());
    }

    public function use_db($mysqli){
        $this->_execute_query($mysqli, 'USE '.$this->_config->db_name, false);
    }

    /**
     * @param \mysqli $mysqli
     *
     * @return mixed|null
     * @throws \moodle_exception
     */
    public function start_transaction($mysqli){
        if (!is_null($this->_transaction)){
            throw new \moodle_exception('transaction already started');
        }
        $this->_transaction = $mysqli->begin_transaction();
        return $this->_transaction;
    }

    /**
     * @param \mysqli $mysqli
     *
     * @return bool
     */
    public function commit_transaction($mysqli){
        $result = $mysqli->commit();
        $this->_transaction = null;
        return $result;
    }

    /**
     * @param \mysqli $mysqli
     *
     * @return bool
     */
    public function rollback_transaction($mysqli){
        $result = $mysqli->rollback();
        $this->_transaction = null;
        return $result;
    }

    public function close_mysqli($mysqli){
        $mysqli->close();
        $this->_transaction = null;
    }
}