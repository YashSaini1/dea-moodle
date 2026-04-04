<?php

use qtype_sqlrunner\sqlrunner_database_worker;

class qtype_sqlrunner_sqlsandbox extends \qtype_sqlrunner_jobesandbox {

    const SQL_PARAM_RESULT = 'result_class';

    protected $_config = null;   // Custom Config object

    // Constructor gets languages from Jobe and stores them.
    // If $this->languages is left null, the Jobe server is down or
    // refusing requests or misconfigured. The actual HTTP returncode and response
    // are left in $httpcode and $response resp.
    public function __construct() {
        parent::__construct();

        $this->_config = get_config(static::get_plugin_name());
    }

    // List of supported languages.
    public function get_languages() {
        if ($this->languages === null) {
            $this->languages = array('sql');
//
//            list($this->httpcode, $this->response) = $this->http_request('languages', static::HTTP_GET);
//
//            if ($this->httpcode == 200 && is_array($this->response)) {
//                foreach ($this->response as $lang) {
//                    $this->languages[] = $lang[0];
//                }
//            }
        }
        return (object) array(
            'error'     => $this->get_error_code($this->httpcode),
            'languages' => $this->languages);
    }

    /** Execute the given source code in the given language with the given
     *  input and returns an object with fields error, result, signal, cmpinfo,
     *  stderr, output.
     * @param string $sourcecode The source file to compile and run
     * @param string $language  One of the languages recognised by the sandbox
     * @param string $input A string to use as standard input during execution
     * @param array $files either null or a map from filename to
     *         file contents, defining a file context at execution time
     * @param array $params Sandbox parameters, depends on
     *         particular sandbox but most sandboxes should recognise
     *         at least cputime (secs) and memorylimit (Megabytes).
     *         If the $params array is null, sandbox defaults are used.
     * @return object with at least the attribute 'error'.
     *         The error attribute is one of the
     *         values 0 through 8 (OK to UNKNOWN_SERVER_ERROR) as defined in the
     *         base class. If
     *         error is 0 (OK), the returned object has additional attributes
     *         result, output, stderr, signal and cmpinfo as follows:
     *             result: one of the result_* constants defined in the base class
     *             output: the stdout from the run
     *             stderr: the stderr output from the run (generally a non-empty
     *                     string is taken as a runtime error)
     *             signal: one of the standard Linux signal values (but often not
     *                     used)
     *             cmpinfo: the output from the compilation run (usually empty
     *                     unless the result code is for a compilation error).
     *         If error is anything other than OK, the attribute stderr will
     *         contain the text of the actual HTTP response header, e.g
     *         Bad Parameter if the response was 400 Bad Parameter.
     *         If the run was actually submitted to a jobe server, the returned
     *         object also has an attribute 'sandboxinfo', which
     *         is an associative array with the keys 'jobeserver' and 'jobeapikey'
     *         showing which jobeserver was used and what key was used (if any).
     */
    public function execute($sourcecode, $language, $input, $files=null, $params=null) {
        $language = strtolower($language);
        if($language != 'sql'){
            return parent::execute($sourcecode, $language, $input, $files, $params);
        }

        $result = $this->_run_sql_code($sourcecode);
        if ($result->status != static::OK) {
            $this->currentjobid = null;
            $runresult['error'] = static::UNKNOWN_SERVER_ERROR;
            $runresult['stderr'] = $result->error;
        }  else {
            $this->currentjobid = null;
            $runresult['error'] = static::OK;
            $runresult['stderr'] = '';
            $runresult['result'] = qtype_sqlrunner_sandbox::RESULT_SUCCESS;
            $runresult['signal'] = 0; // Jobe doesn't return signals.
            $runresult['cmpinfo'] = '';
            $runresult['output'] = $result->encode_data();
        }
        return (object) $runresult;
    }

    protected function _run_sql_code($sourcecode){
        $db = sqlrunner_database_worker::get_instance();
        $result_class = $this->_get_additional_param(static::SQL_PARAM_RESULT);
        if ($result_class){
            $db->set_result_class($result_class);
        }
        return $db->run_query($sourcecode);
    }

    // Return the sandbox error code corresponding to the given httpcode.
    protected function get_error_code($httpcode) {
        $codemap = array(
            '200' => static::OK,
            '202' => static::OK,
            '204' => static::OK,
            '400' => static::JOBE_400_ERROR,
            '401' => static::SUBMISSION_LIMIT_EXCEEDED,
            '403' => static::AUTH_ERROR
        );
        if (isset($codemap[$httpcode])) {
            return $codemap[$httpcode];
        } else {
            return static::UNKNOWN_SERVER_ERROR;
        }
    }
}