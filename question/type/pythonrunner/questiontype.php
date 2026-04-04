<?php

/**
 * @package     qtype
 * @subpackage  pythonrunner
 * @copyright   &copy; 2012, 2013, 2014 Richard Lobb
 * @author       Richard Lobb richard.lobb@canterbury.ac.nz
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/type/pythonrunner/lib.php');

/**
 * qtype_pythonrunner extends the base question_type to pythonrunner-specific functionality.
 * A pythonrunner question requires an additional DB table
 * that contains the definitions for the testcases associated with a programming code
 * question. There are an arbitrary number of these, so they can't be handled
 * by adding columns to the standard question table.
 */
class qtype_pythonrunner extends question_type {

    const BASE_SQL_QUERIES_COUNT = 5;

    /**
     * Whether this question type can perform a frequency analysis of student
     * responses.
     *
     * If this method returns true, you must implement the get_possible_responses
     * method, and the question_definition class must implement the
     * classify_response method.
     *
     * @return bool whether this report can analyse all the student reponses
     * for things like the quiz statistics report.
     */
    public function can_analyse_responses() {
        return false;
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array where the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_question_fields() {
        return array('question_python_options',
            'pythonrunnertype',
            'prototypetype',
            'pythontype',
            'sql_query',
            'penaltyregime',
            'hidecheck',
            'answerpreload',
            'useace',
            'resultcolumns',
            'template',
            'iscombinatortemplate',
            'allowmultiplestdins',
            'answer',
            'validateonsave',
            'testsplitterre',
            'language',
            'acelang',
            'sandbox',
            'grader',
            'templateparams',
            'cputimelimitsecs',
            'memlimitmb',
            'sandboxparams',
            'hoisttemplateparams',
            'uiplugin',
            'uiparameters',
            'displayfeedback',
            'giveupallowed',
            'videourl',
        );
    }

    /** A list of the extra question fields that are NOT inheritable from
     *  the prototype and so are not hidden in the usual authoring interface
     *  as 'customise' fields.
     * @return array of strings
     */
    public static function noninherited_fields() {
        return array(
            'pythonrunnertype',
            'prototypetype',
            'pythontype',
            'sql_query',
            'penaltyregime',
            'hidecheck',
            'answerpreload',
            'globalextra',
            'answer',
            'validateonsave',
            'hoisttemplateparams',
            'templateparams',
            'uiparameters',
            'maxfilesize',
            'filenamesregex',
            'filenamesexplain',
            'displayfeedback',
            'giveupallowed',
            'videourl',
        );
    }

    public function response_file_areas() {
        return array('attachments');
    }

    /**
     * If you use extra_question_fields, overload this function to return question id field name
     * in case you table use another name for this column.
     * [Don't really need this as we're returning the default value, but I
     * prefer to be explicit.]
     */
    public function questionid_column_name() {
        return 'questionid';
    }

    /**
     * Abstract function implemented by each question type. It runs all the code
     * required to set up and save a question of any type for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    public function generate_test($name, $courseid=null) {
        // Closer inspection shows that this method isn't actually implemented
        // by even the standard question types and wouldn't be called for any
        // non-standard ones even if implemented. I'm leaving the stub in, in
        // case it's ever needed, but have set it to throw an exception, and
        // I've removed the actual test code.
        throw new coding_exception('Unexpected call to generate_test. Read code for details.');
    }

    /**
     * Function to copy testcases from form fields into question->testcases.
     * If $validation true, we're just validating and need to add an extra
     * rownum attribute to the testcase to allow failed test case results
     * to be copied back to the form with a mouse click.
     */
    protected function copy_testcases_from_form(&$question, $validation) {
        $testcases = array();
        if (empty($question->answer)){
            $numtests = 0;  // Must be a combinator template grader with no tests.
        } else {
            $numtests = 1;//count($question->testcode);
            //assert(count($question->expected) == $numtests);
        }
        if (empty($question->options->testcases)){
            $test_data = $question;
        } else {
            $test_data = $question->options->testcases[0];
        }
        // save only one test case
        for ($i = 0; $i < $numtests; $i++){
            $testcode = qtype_pythonrunner_util::filter_crs($question->answer);
            $stdin = qtype_pythonrunner_util::filter_crs($test_data->stdin);
            // do not filter expected. Can be non-ASCII symbol here
            $expected = ($test_data->expected);
            $extra = qtype_pythonrunner_util::filter_crs($test_data->extra);
            if (trim($testcode) === '' && trim($stdin) === '' && trim($expected) === '' && trim($extra) === ''){
                continue; // Ignore testcases with only whitespace in them.
            }
            $testcase = new stdClass;
            if ($validation){
                $testcase->rownum = $i;  // The row number in the edit form - relevant only when validating.
            }
            $testcase->questionid = $question->id ?? 0;
            $testcase->testtype = $test_data->testtype ?? 0;
            $testcase->testcode = $testcode;
            $testcase->stdin = $stdin;
            $testcase->expected = $expected;
            $testcase->extra = $extra;
            $testcase->useasexample = !empty($test_data->useasexample);
            $testcase->display = $test_data->display;
            $testcase->hiderestiffail = empty($test_data->hiderestiffail);
            $testcase->mark = trim($test_data->mark) == '' ? 1.0 : floatval($test_data->mark);
            $testcase->ordering = intval($test_data->ordering ?? 1);
            $testcases[] = $testcase;
        }

        // do not sort because we have only 1 item
//        usort($testcases, function ($tc1, $tc2) {
//            if ($tc1->ordering === $tc2->ordering) {
//                return 0;
//            } else {
//                return $tc1->ordering < $tc2->ordering ? -1 : 1;
//            }
//        });  // Sort by ordering field.

        $question->testcases = $testcases;
    }

    protected function copy_sql_queries_from_form(&$question, $validation) {
        if (!isset($question->sqlquery) || !isset($question->fieldname)){
            return;
        }

        $sql_queries = array();
        foreach ($question->sqlquery as $key => $code){
            $fieldname = trim($question->fieldname[$key] ?? '');
            $code = trim($code);
            if (empty($fieldname && $code)){
                continue;
            }

            $sql = new stdClass();
            $sql->questionid = $question->id ?? 0;
            $sql->fieldname = $question->fieldname[$key] ?? 0;
            $sql->query = $code;
            $sql->lastcode = trim($question->lastcode[$key] ?? '');
            $sql->expected = $question->sqlexpected[$key] ?? '';
            $sql->fields = $sql->data = '';
            if (!empty($sql->expected)){
                $expected_data = json_decode($question->sqlexpected[$key], 1);
                $sql->fields = json_encode($expected_data['fields']);
                $sql->data = json_encode($expected_data['data']);
            }
            $sql_queries[] = $sql;
        }
        $question->sql_queries = $sql_queries;
    }

    // Override save_question to record in $form if this is a new question or
    // not. Needed by save_question_options when saving prototypes.
    // Note that the $form parameter to save_question is passed through
    // to save_question_options as the $question parameter.
    public function save_question($question, $form) {
        $form->isnew = empty($question->id);
        $form->generalfeedback = ['text' => ''];
        return parent::save_question($question, $form);
    }

    // This override saves all the extra question data, including
    // the set of testcases and any datafiles to the database.
    public function save_question_options($question) {
        global $USER;

        // Tidy the form, handle inheritance from prototype.
        $this->clean_question_form($question);

        $this->_save_testcases($question);
        $this->_save_sqlqueries($question);

        parent::save_question_options($question);
        // Write test cases to DB, reusing old ones where possible.

        $this->notify_prototype_children_if_any($question);

        // Lastly, save any datafiles (support files + sample answer files).
        if ($USER->id) {
            // The id check is a hack to deal with phpunit initialisation, when no user exists.
            foreach (array('datafiles' => 'datafile') as $fileset => $filearea) {
                if (empty($question->$fileset)) continue;

                file_save_draft_area_files($question->$fileset, $question->context->id, 'qtype_pythonrunner', $filearea, (int) $question->id,
                    $this->fileoptions);
            }
        }

        return true;
    }

    protected function _save_testcases($question){
        global $DB;
        $testcasetable = 'question_python_tests';
        if (!$oldtestcases = $DB->get_records($testcasetable, array('questionid' => $question->id), 'id ASC')){
            $oldtestcases = array();
        }

        foreach ($question->testcases as $tc){
            // do not filter expected. Can be non-ASCII symbol here
            $tc->testcode = trim($tc->testcode);
            $tc->stdin = trim($tc->stdin);
            $tc->extra = trim($tc->extra);
            if (($oldtestcase = array_shift($oldtestcases))){ // Existing testcase, so reuse it.
                $tc->id = $oldtestcase->id;
                $DB->update_record($testcasetable, $tc);
                continue;
            }

            // A new testcase.
            $tc->questionid = $question->id;
            $id = $DB->insert_record($testcasetable, $tc);
        }

        // Delete old testcase records.
        foreach ($oldtestcases as $otc){
            $DB->delete_records($testcasetable, array('id' => $otc->id));
        }
    }

    protected function _save_sqlqueries($question){
        global $DB;
        if ($question->pythontype != PYTHON_OTHER){
            return;
        }

        $queries_table = 'question_python_sqlqueries';
        $old_queries = $DB->get_records($queries_table, array('questionid' => $question->id), 'id ASC');
        $template = '';
        foreach ($question->sql_queries as $sq){
            // do not filter expected. Can be non-ASCII symbol here
            $sq->fieldname = trim($sq->fieldname);
            $sq->query = trim($sq->query);
            $template .= "$sq->fieldname=pd.read_json('{{{$sq->fieldname}}}')".PHP_EOL;
            if (($old_query = array_shift($old_queries))){ // Existing testcase, so reuse it.
                $sq->id = $old_query->id;
                $DB->update_record($queries_table, $sq);
                continue;
            }

            // A new testcase.
            $sq->questionid = $question->id;
            $id = $DB->insert_record($queries_table, $sq);
        }

        // Delete old testcase records.
        foreach ($old_queries as $otc){
            $DB->delete_records($queries_table, array('id' => $otc->id));
        }

        $question->template = 'import pandas as pd'.PHP_EOL.$template.$question->template;
    }


    /**
     * Clean up the "question" (which is actually the question editing form)
     * ready for saving or for testing before saving ($isvalidation == true).
     * @param $question the question editing form
     * @param $isvalidation true if we're cleaning for validation rather than saving.
     */
    public function clean_question_form($question, $isvalidation=false) {
        $fields = $this->extra_question_fields();
        array_shift($fields); // Discard table name.
        $customised = isset($question->customise) && $question->customise;
        $isprototype = $question->prototypetype != 0;
        if ($customised && $question->prototypetype > 0 && !$isvalidation &&
                $question->pythonrunnertype != $question->typename) {
            // Saving a new user-defined prototype.
            // Copy new type name into pythonrunnertype.
            $question->pythonrunnertype = $question->typename;
        }

        // If we're saving a new prototype, make sure its pythonrunnertype is
        // unique by appending a suitable suffix. This shouldn't happen via
        // question edit form, but could be a spurious import or a question
        // duplication mouse click.
        if ($question->isnew && $isprototype && !$isvalidation) {
            $suffix = '';
            $type = $question->pythonrunnertype;
            while (true) {
                // Check for the existence of a prototype with this type name and suffix.
                $row = $this->get_prototype($type . $suffix, $question->context, true);
                if ($row === null) {
                    break;
                }
                $suffix = $suffix == '' ? '-1' : $suffix - 1;
                if ($suffix == '-9') {
                    throw new qtype_pythonrunner_exception('Too many templates with almost identical names');
                }
            }
            $question->pythonrunnertype = $type . $suffix;
        }

        // Set all inherited fields to null if the corresponding form
        // field is blank or if it's being saved with customise explicitly
        // turned off and it's not a prototype.
        $questioninherits = isset($question->customise) && !$question->customise && !$isprototype;
        foreach ($fields as $field) {
            $isinherited = !in_array($field, $this->noninherited_fields());
            $isblankstring = !isset($question->$field) || (is_string($question->$field) && trim($question->$field) === '');
            if ($isinherited && ($isblankstring || $questioninherits)) {
                $question->$field = null;
            }
        }

        if (trim($question->sandbox) === 'DEFAULT') {
            $question->sandbox = null;
        }

        // Convert penalty regime string to generic form without '%'s and with
        // ', ' as a separator.
        if(!empty($question->penaltyregime)){
            $penaltyregime = str_replace('%', '', $question->penaltyregime);
            $penaltyregime = str_replace(',', ', ', $penaltyregime);
            $question->penaltyregime = preg_replace('/ *,? +/', ', ', $penaltyregime);
        } else {
            $question->penaltyregime = get_config('qtype_pythonrunner', 'penaltyregime');
        }

        // Copy and clean testcases.
        if (!isset($question->testcases)) {
            $this->copy_testcases_from_form($question, $isvalidation);
        }

        if (!isset($question->sql_queries)) {
            $this->copy_sql_queries_from_form($question, $isvalidation);
        }
    }

    /**
     * Move all the files belonging to this question from one context to another.
     * Override superclass implementation to handle the extra data files and
     * sample answer files we have in CodeRunner questions.
     * @param int $questionid the question being moved.
     * @param int $oldcontextid the context it is moving from.
     * @param int $newcontextid the context it is moving to.
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        foreach (array('datafile', 'samplefile') as $filetype) {
            $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, 'qtype_pythonrunner', $filetype, $questionid);
        }
    }

    // Load the question options (all the question extension fields and
    // testcases) from the database into the question.
    // The various fields are initialised from the prototype, then overridden
    // by any non-null values in the specific question.
    // As a side effect, the question->prototype field is set to the prototype.
    public function get_question_options($question) {
        global $CFG, $DB, $OUTPUT;
        parent::get_question_options($question);
        $options =& $question->options;
        if ($options->prototypetype != 0) { // Question prototype?
            // Yes. It's 100% customised with nothing to inherit.
            $options->customise = true;
        } else {
            $qtype = $options->pythonrunnertype;
            $context = $this->question_context($question);
            $question->prototype = $this->get_prototype($qtype, $context);
            $this->set_inherited_fields($options, $question->prototype);
        }

        // Add in any testcases.
        if ($testcases = $DB->get_records('question_python_tests', array('questionid' => $question->id), 'id ASC')) {
            $options->testcases = array_values($testcases); // Reindex tests from zero.
        } else {
            $options->testcases = array();
        }

        $sql_queries = [];
        if ($options->pythontype == PYTHON_OTHER){
            $sql_queries = $DB->get_records('question_python_sqlqueries', ['questionid' => $question->id]);
        }
        $options->sql_queries = array_values($sql_queries);

        return true;
    }

    /**
     * Add to the given target object all the inherited fields from the question's prototype
     * record that have not been overridden (i.e. that are null).
     * If the given prototype is null (a broken question) nothing happens.
     * If any of the inherited fields are modified (i.e. any of the extra fields not
     * in the noninheritedFields list), the 'customise' field is set.
     * This is used only to display the customisation panel during authoring.
     * @param object $target the target object whose fields are being set. It should
     * be either a qtype_pythonrunner_question object or its options field ($question->options).
     * @param string $prototype the prototype question. Null if non-existent (a broken question).
     */
    public function set_inherited_fields($target, $prototype) {
        $target->customise = false; // Starting assumption.

        if ($prototype === null) {
            return;
        }

        $extrafields = $this->extra_question_fields();
        array_shift($extrafields); // Remove the extraneous qtype_pythonrunner_options entry.
        $inheritedfields = array_diff($extrafields, $this->noninherited_fields());
        foreach ($inheritedfields as $field) {
            $prototypevalue = $prototype->$field;
            if (isset($target->$field) &&
                    $target->$field !== null &&
                    $target->$field !== '' &&
                    $target->$field != $prototypevalue) {
                    $target->customise = true; // An inherited field has been changed.
            } else {
                $target->$field = $prototypevalue; // Inherit the field value.
            }
        }

        if (!isset($target->sandbox)) {
            $target->sandbox = null;
        }

        if (!isset($target->grader)) {
            $target->grader = null;
        }

        if (!isset($target->sandboxparams) || trim($target->sandboxparams) === '') {
            $target->sandboxparams = null;
        }
    }

    /**
     * Get all available prototypes for the given course.
     * Only the most recent version of each prototype question is returned.
     * @param int $courseid the ID of the course whose prototypes are required.
     * @return stdClass[] prototype rows from question_python_options.
     */
    public static function get_all_prototypes($courseid) {
        global $DB;
        $coursecontext = context_course::instance($courseid);
        list($contextcondition, $params) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true));

        $rows = $DB->get_records_sql("
                SELECT qco.*
                  FROM {question_python_options} qco
                  JOIN {question} q ON q.id = qco.questionid
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                  WHERE (qv.version = (SELECT MAX(v.version)
                                        FROM {question_versions} v
                                        JOIN {question_bank_entries} be ON be.id = v.questionbankentryid
                                       WHERE be.id = qbe.id)
                                     )
                        AND prototypetype != 0
                        AND qc.contextid $contextcondition", $params);

        return $rows;
    }

    /**
     * Get a given named prototype available in a given context.
     *
     * To be valid, the named prototype (a question of the specified type
     * and with prototypetype non zero) must be in a question category that's
     * available in the given current context.
     * Only the most recent version of the question prototype is returned.
     *
     * @param string $pythonrunnertype prototype name.
     * @param context $context a context.
     * @param boolean $checkexistenceonly if true the function returns true if
     * the prototype exists, rather than the prototype itself.
     * @return qtype_pythonrunner_question prototype the prototype for the given
     * question type in the given context or null if no prototype can be found
     * or if more than one prototype is found.
     */
    public static function get_prototype($pythonrunnertype, $context, $checkexistenceonly=false) {
        global $DB;
        list($contextcondition, $params) = $DB->get_in_or_equal($context->get_parent_context_ids(true));
        $params[] = $pythonrunnertype;

        $sql = "SELECT q.id
                  FROM {question_python_options} qco
                  JOIN {question} q ON qco.questionid = q.id
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                  JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                 WHERE (qv.version = (SELECT MAX(v.version)
                                        FROM {question_versions} v
                                        JOIN {question_bank_entries} be ON be.id = v.questionbankentryid
                                       WHERE be.id = qbe.id)
                                     )
                   AND qco.prototypetype != 0
                   AND qc.contextid $contextcondition
                   AND qco.pythonrunnertype = ?";

        $validprotoids = $DB->get_records_sql($sql, $params);
        if (count($validprotoids) !== 1) {
            return null;  // Exactly one prototype should be found.
        } else if ($checkexistenceonly) {
            return true;
        } else {
            $proto = reset($validprotoids);
            $prototype = question_bank::load_question($proto->id);
            self::update_question_text_maybe($prototype);
            return $prototype;
        }
    }

    /**
     * Get the context for a question.
     *
     * @param stdClass $question a row from either the question or question_python_options tables.
     * @return context the corresponding context id.
     */
    public static function question_context($question) {
        return context::instance_by_id(self::question_contextid($question));
    }

    /**
     * Get the context id for a question.
     *
     * @param stdClass $question a row from either the question or question_python_options tables.
     * @return int the corresponding context id.
     */
    public static function question_contextid($question) {
        global $DB;

        if (isset($question->contextid)) {
            return $question->contextid;
        }

        $questionid = $question->questionid ?? $question->id;
        $sql = "SELECT contextid FROM {question_categories} qc
                    JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
                    JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                    JOIN {question} q ON qv.questionid = q.id
                WHERE q.id = ?";
        return $DB->get_field_sql($sql, array($questionid), MUST_EXIST);
    }

    // For built-in prototypes, replace the question text (which is used for
    // in-line help in the question authoring form) with the appropriate
    // language string.
    protected static function update_question_text_maybe($prototype) {
        if ($prototype->prototypetype == 1) { // Built-in prototype.
            $stringname = 'qtype_' . $prototype->pythonrunnertype;
            $prototype->questiontext = sqlrunner_str($stringname);
        }
    }

    // Initialise the question_definition object from the questiondata
    // read from the database (probably a cached version of the question
    // object from the database enhanced by a call to get_question_options).
    // Only fields not explicitly listed in extra_question_fields (i.e. those
    // fields not from the question_python_options table) need handling here.
    // All we do is flatten the question->options fields down into the
    // question itself, which will be all those fields of question->options
    // not already flattened down by the parent implementation.
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        foreach ($questiondata->options as $field => $value) {
            if (!isset($question->$field)) {
                $question->$field = $value;
            }
        }
    }

    // Override default question deletion code to delete all the question's
    // testcases.
    // Includes a check if the question being deleted is a prototype. Currently
    // I don't have a good way to check if the prototype being deleted has
    // "children" in the current context. It's over to question authors to make
    // sure they don't delete in-use prototypes.
    // All I do when a prototype is deleted is invalidate cached child questions
    // so that at least their subsequent behaviour is consistent with the
    // missing prototype. This can occasionally be helpful, e.g. if a duplicate
    // prototype has somehow been created, and is then deleted again, the
    // child question will now function correctly.
    public function delete_question($questionid, $contextid) {
        global $DB;

        $question = $DB->get_record('question_python_options', array('questionid' => $questionid));

        $this->notify_prototype_children_if_any($question);
        $success = $DB->delete_records('question_python_tests', array('questionid' => $questionid));
        return $success && parent::delete_question($questionid, $contextid);
    }

    // Function to notify any children of a given question (if it is a
    // prototype) that the parent has been edited (or perhaps deleted).
    private function notify_prototype_children_if_any($question) {
        global $DB;

        if (defined('CACHE_DISABLE_ALL') && CACHE_DISABLE_ALL !== false) {
            return;  // If caching is off, nothing to do.
        }
        if (!empty($question->prototypetype)) {
            $typename = $question->pythonrunnertype;
            $children = $DB->get_records('question_python_options', array('prototypetype' => 0, 'pythonrunnertype' => $typename));
            foreach ($children as $child) {
                question_bank::notify_question_edited($child->questionid);
            }
        }
    }

    /******************** EDIT FORM OPTIONS ************************/

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return array(
            0 => get_string('no'),
            1 => '1',
            2 => '2',
            3 => '3',
            -1 => get_string('unlimited'),
        );
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return array(
            0 => pythonrunner_str('attachmentsoptional'),
            1 => '1',
            2 => '2',
            3 => '3'
        );
    }

    /**
     * @return array the options for maximum file size
     */
    public function attachment_filesize_max() {
        return array(
            1024 => '1 kB',
            10240 => '10 kB',
            102400 => '100 kB',
            1048576 => '1 MB',
            10485760 => '10 MB',
            104857600 => '100 MB'
        );
    }

    /******************** IMPORT/EXPORT FUNCTIONS ***************************/

    /**
     * Imports question from the Moodle XML format
     *
     * Overrides default since pythonrunner questions contain a list of testcases,
     * not a list of answers.
     *
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if ($extra != null) {
            throw new coding_exception("pythonrunner:import_from_xml: unexpected 'extra' parameter");
        }

        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        $newdefaults = array(
            'precheck' => 0,
            'validateonsave' => 1,
            'answerpreload' => '',
            'globalextra' => '',
            'useace' => 1,
            'iscombinatortemplate' => null,  // Probably unnecessary?
            'template' => null,  // Probably unnecessary?
            'templateparamslang' => 'twig',
            'templateparamsevald' => null,
            'uiparameters' => null,
            'hidecheck' => 0,
            'attachments' => 0,
            'giveupallowed' => 0,
        );

        foreach ($extraquestionfields as $field) {
            if ($field === 'iscombinatortemplate' && isset($qo->iscombinatortemplate)) {
                continue; // Already loaded in the case of a legacy question.
            }
            if (array_key_exists($field, $newdefaults)) {
                $default = $newdefaults[$field];
            } else {
                $default = '';
            }
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), $default);
        }

        $qo->isnew = true;
        $qo->testcases = array();

        if (isset($data['#']['testcases'][0]['#']['testcase']) &&
                is_array($data['#']['testcases'][0]['#']['testcase'])) {
            $testcases = $data['#']['testcases'][0]['#']['testcase'];
            foreach ($testcases as $testcase) {
                $tc = new stdClass;
                $tc->testcode = $testcase['#']['testcode'][0]['#']['text'][0]['#'];
                $tc->stdin = $testcase['#']['stdin'][0]['#']['text'][0]['#'];
                if (isset($testcase['#']['output'])) { // Handle old exports.
                    $tc->expected = $testcase['#']['output'][0]['#']['text'][0]['#'];
                } else {
                    $tc->expected = $testcase['#']['expected'][0]['#']['text'][0]['#'];
                }
                $tc->extra = $testcase['#']['extra'][0]['#']['text'][0]['#'];
                $tc->display = 'SHOW';
                $tc->mark = 1.0;
                if (isset($testcase['@']['mark'])) {
                    $tc->mark = floatval($testcase['@']['mark']);
                }
                if (isset($testcase['@']['hidden']) && $testcase['@']['hidden'] == "1") {
                    $tc->display = 'HIDE';  // Handle old-style export too.
                }
                if (isset($testcase['#']['display'])) {
                    $tc->display = $testcase['#']['display'][0]['#']['text'][0]['#'];
                }
                if (isset($testcase['@']['hiderestiffail'] )) {
                    $tc->hiderestiffail = $testcase['@']['hiderestiffail'] == "1" ? 1 : 0;
                } else {
                    $tc->hiderestiffail = 0;
                }
                if (isset($testcase['@']['testtype'] )) {
                    $tc->testtype = intval($testcase['@']['testtype']);
                } else {
                    $tc->testtype = 0;
                }
                $tc->useasexample = $testcase['@']['useasexample'] == "1" ? 1 : 0;
                $qo->testcases[] = $tc;
            }
        }

        // Import any support files.
        $datafiles = $format->getpath($data,
                array('#', 'testcases', 0, '#', 'file'), array());
        if (is_array($datafiles)) { // Seems like a non-array does occur in some versions of PHP!
            $qo->datafiles = $format->import_files_as_draft($datafiles);
        }

        // Import any sample answer attachments.
        if (isset($data['#']['answerfiles'])) {
            $samplefiles = $format->getpath($data, array('#', 'answerfiles', 0, '#', 'file'), array());
            if (is_array($samplefiles)) {
                $qo->sampleanswerattachments = $format->import_files_as_draft($samplefiles);
            }
        }

        return $qo;
    }

    /**
     * Export question to the Moodle XML format
     *
     * We override the default method because we don't have 'answers' but
     * testcases.
     *
     */

    // Exporting is complicated by inheritance from the prototype.
    // To deal with this we re-read the prototype and include in the
    // export only the pythonrunner extra fields that are not inherited or that
    // are not equal in value to the field from the prototype.
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        global $COURSE;
        if ($extra !== null) {
            throw new coding_exception("pythonrunner:export_to_xml: Unexpected parameter");
        }

        // Copy the question so we can modify it for export
        // (Just in case the original gets used elsewhere).
        $questiontoexport = clone $question;

        $qtype = $question->options->pythonrunnertype;
        $coursecontext = context_course::instance($COURSE->id);
        $row = self::get_prototype($qtype, $coursecontext);

        // Clear all inherited fields equal in value to the corresponding Prototype field
        // (but only if we found a prototype and this is not a prototype question itself).
        if ($row && $questiontoexport->options->prototypetype == 0) {
            $noninheritedfields = $this->noninherited_fields();
            $extrafields = $this->extra_question_fields();
            foreach ($row as $field => $value) {
                if (in_array($field, $extrafields) &&
                        !in_array($field, $noninheritedfields) &&
                        $question->options->$field === $value) {
                    $questiontoexport->options->$field = null;
                }
            }
        }

        $expout = parent::export_to_xml($questiontoexport, $format, $extra);
        $expout .= "    <testcases>\n";

        foreach ($question->options->testcases as $testcase) {
            $useasexample = $testcase->useasexample ? 1 : 0;
            $hiderestiffail = $testcase->hiderestiffail ? 1 : 0;
            $testtype = isset($testcase->testtype) ? $testcase->testtype : 0;
            $mark = sprintf("%.7f", $testcase->mark);
            $expout .= "      <testcase testtype=\"$testtype\" useasexample=\"$useasexample\"";
            $expout .= " hiderestiffail=\"$hiderestiffail\" mark=\"$mark\" >\n";
            foreach (array('testcode', 'stdin', 'expected', 'extra', 'display') as $field) {
                $exportedvalue = $format->writetext($testcase->$field, 4);
                $expout .= "      <{$field}>\n        {$exportedvalue}      </{$field}>\n";
            }
            $expout .= "    </testcase>\n";
        }

        // Add datafiles within the scope of the <testcases> element.
        $fs = get_file_storage();
        $contextid = $question->contextid;
        $datafiles = $fs->get_area_files(
                $contextid, 'qtype_pythonrunner', 'datafile', $question->id);
        $expout .= $format->write_files($datafiles);

        $expout .= "    </testcases>\n";

        // If there are any sample answer attachments, add them in a new
        // <answerfiles> element.
        $sampleanswerfiles = $fs->get_area_files(
                $contextid, 'qtype_pythonrunner', 'samplefile', $question->id);
        if (count($sampleanswerfiles) > 0) {
            $expout .= "    <answerfiles>\n";
            $expout .= $format->write_files($sampleanswerfiles);
            $expout .= "    </answerfiles>\n";
        }

        return $expout;
    }
}

