<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question type class for the data_modeling question type.
 *
 * @package    qtype
 * @subpackage data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/data_modeling/question.php');

/**
 * The data_modeling question type class.
 *
 * This class contains some special features in order to make the
 * question type embeddable within a multianswer (cloze) question
 *
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class qtype_data_modeling extends question_type {

    public function get_question_options($question){
        global $CFG, $DB, $OUTPUT;
        parent::get_question_options($question);
        $this->get_data_modeling_options($question);
        $this->get_data_modeling_answer($question);

        return true;
    }

    public function get_data_modeling_options($question) {
        global $DB;
        $options = $DB->get_record('question_dm', array('question' => $question->id));
        if (!empty($options)){
            $question->table_code = $options->table_data;
            $question->videourl = $options->videourl;
        } else {
            $question->table_code = '';
            $question->videourl = '';
        }

        return true;
    }

    public function get_data_modeling_answer(&$question){
        $answers = $question->options->answers;
        if (empty($answers)){
            $question->answer = '';
            return;
        }
        $answer = reset($answers);
        $question->answer = $answer->answer;
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
    }

    public function save_question($question, $form){
        return parent::save_question($question, $form);
    }

    /**
     * Save the units and the answers associated with this question.
     */
    public function save_question_options($question) {
        global $DB;
        $context = $question->context;

        // Get old versions of the objects.
        $oldanswer = $DB->get_record('question_answers', array('question' => $question->id));

        if (!$oldanswer){
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->fraction = 1;
            $answer->feedback = '';
            $answer->answer = trim($question->answer);
            $answer->id = $DB->insert_record('question_answers', $answer);
        } else {
            $answer = $oldanswer;
            $answer->answer = trim($question->answer);
            $DB->update_record('question_answers', $oldanswer);
        }

        $dm_options = $DB->get_record('question_dm', array('question' => $question->id));
        if(empty($dm_options)){
            $dm_options = (object) [
                'question' => $question->id,
                'table_data' => $question->table_code,
                'videourl' => $question->videourl,
            ];
            $DB->insert_record('question_dm', $dm_options);
        } else {
            $dm_options->table_data = $question->table_code;
            $dm_options->videourl = $question->videourl;
            $DB->update_record('question_dm', $dm_options);
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_data_modeling_answers($question, $questiondata);
    }

    public function initialise_data_modeling_answers(question_definition $question, $questiondata) {
        global $DB;
        $answer = $DB->get_record('question_answers', array('question' => $question->id));
        $question->answer = $answer->answer;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
//        $DB->delete_records('question_data_modeling', array('question' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        foreach ($questiondata->options->answers as $aid => $answer) {
            if ('*' == trim($answer->answer)) {
                return max($answer->fraction - $questiondata->options->unitpenalty, 0);
            }
        }
        return 0;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }
}


/**
 * This class processes numbers with units.
 *
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class qtype_data_modeling_answer_processor {
    /** @var array unit name => multiplier. */
    protected $units;
    /** @var string character used as decimal point. */
    protected $decsep;
    /** @var string character used as thousands separator. */
    protected $thousandssep;
    /** @var boolean whether the units come before or after the number. */
    protected $unitsbefore;

    protected $regex = null;

    public function __construct($units, $unitsbefore = false, $decsep = null,
            $thousandssep = null) {
        if (is_null($decsep)) {
            $decsep = get_string('decsep', 'langconfig');
        }
        $this->decsep = $decsep;

        if (is_null($thousandssep)) {
            $thousandssep = get_string('thousandssep', 'langconfig');
        }
        $this->thousandssep = $thousandssep;

        $this->units = $units;
        $this->unitsbefore = $unitsbefore;
    }

    /**
     * Set the decimal point and thousands separator character that should be used.
     * @param string $decsep
     * @param string $thousandssep
     */
    public function set_characters($decsep, $thousandssep) {
        $this->decsep = $decsep;
        $this->thousandssep = $thousandssep;
        $this->regex = null;
    }

    /** @return string the decimal point character used. */
    public function get_point() {
        return $this->decsep;
    }

    /** @return string the thousands separator character used. */
    public function get_separator() {
        return $this->thousandssep;
    }

    /**
     * @return bool If the student's response contains a '.' or a ',' that
     * matches the thousands separator in the current locale. In this case, the
     * parsing in apply_unit can give a result that the student did not expect.
     */
    public function contains_thousands_seaparator($value) {
        if (!in_array($this->thousandssep, array('.', ','))) {
            return false;
        }

        return strpos($value, $this->thousandssep) !== false;
    }

    /**
     * Create the regular expression that {@link parse_response()} requires.
     * @return string
     */
    protected function build_regex() {
        if (!is_null($this->regex)) {
            return $this->regex;
        }

        $decsep = preg_quote($this->decsep, '/');
        $thousandssep = preg_quote($this->thousandssep, '/');
        $beforepointre = '([+-]?[' . $thousandssep . '\d]*)';
        $decimalsre = $decsep . '(\d*)';
        $exponentre = '(?:e|E|(?:x|\*|×)10(?:\^|\*\*))([+-]?\d+)';

        $numberbit = "{$beforepointre}(?:{$decimalsre})?(?:{$exponentre})?";

        if ($this->unitsbefore) {
            $this->regex = "/{$numberbit}$/";
        } else {
            $this->regex = "/^{$numberbit}/";
        }
        return $this->regex;
    }

    /**
     * This method can be used for more locale-strict parsing of repsonses. At the
     * moment we don't use it, and instead use the more lax parsing in apply_units.
     * This is just a note that this funciton was used in the past, so if you are
     * intersted, look through version control history.
     *
     * Take a string which is a number with or without a decimal point and exponent,
     * and possibly followed by one of the units, and split it into bits.
     * @param string $response a value, optionally with a unit.
     * @return array four strings (some of which may be blank) the digits before
     * and after the decimal point, the exponent, and the unit. All four will be
     * null if the response cannot be parsed.
     */
    protected function parse_response($response) {
        if (!preg_match($this->build_regex(), $response, $matches)) {
            return array(null, null, null, null);
        }

        $matches += array('', '', '', ''); // Fill in any missing matches.
        list($matchedpart, $beforepoint, $decimals, $exponent) = $matches;

        // Strip out thousands separators.
        $beforepoint = str_replace($this->thousandssep, '', $beforepoint);

        // Must be either something before, or something after the decimal point.
        // (The only way to do this in the regex would make it much more complicated.)
        if ($beforepoint === '' && $decimals === '') {
            return array(null, null, null, null);
        }

        if ($this->unitsbefore) {
            $unit = substr($response, 0, -strlen($matchedpart));
        } else {
            $unit = substr($response, strlen($matchedpart));
        }
        $unit = trim($unit);

        return array($beforepoint, $decimals, $exponent, $unit);
    }

    /**
     * Takes a number in almost any localised form, and possibly with a unit
     * after it. It separates off the unit, if present, and converts to the
     * default unit, by using the given unit multiplier.
     *
     * @param string $response a value, optionally with a unit.
     * @return array(numeric, sting) the value with the unit stripped, and normalised
     *      by the unit multiplier, if any, and the unit string, for reference.
     */
    public function apply_units($response, $separateunit = null) {
        // Strip spaces (which may be thousands separators) and change other forms
        // of writing e to e.
        $response = str_replace(' ', '', $response);
        $response = preg_replace('~(?:e|E|(?:x|\*|×)10(?:\^|\*\*))([+-]?\d+)~', 'e$1', $response);

        // If a . is present or there are multiple , (i.e. 2,456,789 ) assume ,
        // is a thouseands separator, and strip it, else assume it is a decimal
        // separator, and change it to ..
        if (strpos($response, '.') !== false || substr_count($response, ',') > 1) {
            $response = str_replace(',', '', $response);
        } else {
            $response = str_replace([$this->thousandssep, $this->decsep, ','], ['', '.', '.'], $response);
        }

        $regex = '[+-]?(?:\d+(?:\\.\d*)?|\\.\d+)(?:e[-+]?\d+)?';
        if ($this->unitsbefore) {
            $regex = "/{$regex}$/";
        } else {
            $regex = "/^{$regex}/";
        }
        if (!preg_match($regex, $response, $matches)) {
            return array(null, null, null);
        }

        $numberstring = $matches[0];
        if ($this->unitsbefore) {
            // Substr returns false when it means '', so cast back to string.
            $unit = (string) substr($response, 0, -strlen($numberstring));
        } else {
            $unit = (string) substr($response, strlen($numberstring));
        }

        if (!is_null($separateunit)) {
            $unit = $separateunit;
        }

        if ($this->is_known_unit($unit)) {
            $multiplier = 1 / $this->units[$unit];
        } else {
            $multiplier = null;
        }

        return array($numberstring + 0, $unit, $multiplier); // The + 0 is to convert to number.
    }

    /**
     * @return string the default unit.
     */
    public function get_default_unit() {
        reset($this->units);
        return key($this->units);
    }

    /**
     * @param string $answer a response.
     * @param string $unit a unit.
     */
    public function add_unit($answer, $unit = null) {
        if (is_null($unit)) {
            $unit = $this->get_default_unit();
        }

        if (!$unit) {
            return $answer;
        }

        if ($this->unitsbefore) {
            return $unit . ' ' . $answer;
        } else {
            return $answer . ' ' . $unit;
        }
    }

    /**
     * Is this unit recognised.
     * @param string $unit the unit
     * @return bool whether this is a unit we recognise.
     */
    public function is_known_unit($unit) {
        return array_key_exists($unit, $this->units);
    }

    /**
     * Whether the units go before or after the number.
     * @return true = before, false = after.
     */
    public function are_units_before() {
        return $this->unitsbefore;
    }

    /**
     * Get the units as an array suitably for passing to html_writer::select.
     * @return array of unit choices.
     */
    public function get_unit_options() {
        $options = array();
        foreach ($this->units as $unit => $notused) {
            $options[$unit] = $unit;
        }
        return $options;
    }
}
