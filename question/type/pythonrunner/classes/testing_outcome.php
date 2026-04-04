<?php
// This file is part of CodeRunner - http://coderunner.org.nz/
//
// CodeRunner is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunner is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunner.  If not, see <http://www.gnu.org/licenses/>.

/** Defines a testing_outcome class which contains the complete set of
 *  results from running all the tests on a particular submission.
 *
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2013, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_pythonrunner_testing_outcome extends qtype_sqlrunner_testing_outcome {

    protected function _is_sql_grader(){
        return !empty($this->grader) && $this->grader->name() == 'SqlEqualityGrader';
    }

    // Return a message summarising the nature of the error if this outcome
    // is not all correct.
    public function validation_error_message() {
        if($this->_is_sql_grader()){
            return parent::validation_error_message();
        }

        if ($this->invalid() || !empty($this->errormessage)) {
            return html_writer::tag('pre', $this->errormessage);
        }

        if ($this->iscombinatorgrader()){  // Combinator grader results table can't be used.
            return pythonrunner_str('failedtesting').html_writer::empty_tag('br').pythonrunner_str('howtogetmore');
        }
        return $this->print_admin_result_table();
    }

    protected function print_admin_result_table(){
        $numerrors = 0;
        $button_name = 'Set result';
        $o = '';
        foreach ($this->testresults as $i => $testresult){
            if ($testresult->iscorrect){
                continue;
            }

            $numerrors += 1;
            $rownum = isset($testresult->rownum) ? intval($testresult->rownum) : $i;

            if (isset($testresult->expected) && isset($testresult->got)){
                // do not filter expected and got. Can be non ASCII symbol here
                $expected = $testresult->expected;
                $got = $testresult->got;
                $o .= '
                    <div class="failrow_'.$rownum.'">
                        <div class="expected" style="display:none">
                            <pre id="id_fail_expected_'.$rownum.'">'.$expected.'</pre>
                        </div>
                        <div class="result">
                            <pre id="id_got_'.$rownum.'" style="display:none">'.$got.'</pre>
                            <button type="button" data-order="'.$rownum.'" class="replaceexpectedwithgot"> '.$button_name.'</button>
                        </div>
                    </div>';
                $o .= python_build_result($got);
            }
        }
        if (empty($o)){
            return $o;
        }
        $o = html_writer::div($o, 'results');

        return $o;
    }

    public function build_table($output){
        if ($this->_is_sql_grader()){
            return render_submission_table($output);
        }
        return python_build_result($output);
    }

    /**
     *
     * @global type $COURSE
     * @param qtype_pythonrunner $question
     * @return a table of test results.
     * The test result table is an array of table rows (each an array).
     * The first row is a header row, containing strings like 'Test', 'Expected',
     * 'Got' etc. Other rows are the values of those items for the different
     * tests that were run.
     * There are two special case columns. If the header is 'iscorrect', the
     * value in the row should be 0 or 1. The header of this column is left blank
     * and the row contents are replaced by a tick or a cross. There can be
     * multiple iscorrect columns. If the header is
     * 'ishidden', the column is not displayed but instead the row itself is
     * hidden from view unless the user has the grade:viewhidden capability.
     *
     * The set of columns to be displayed is specified by the question's
     * resultcolumns variable (which should be accessed via its result_columns
     * method). The resultcolumns attribute is a JSON-encoded list of column specifiers.
     * A column specifier is itself a list, usually with 2 or 3 elements.
     * The first element is the column header the second is (usually) the test
     * result object field name whose value is to be displayed in the column
     * and the third (optional) element is the sprintf format used to display
     * the field. It is also possible to combine more than one field of the
     * test result object into a single field by adding extra field names into
     * the column specifier before the format, which is then mandatory.
     * For example, to display the mark awarded for a test case as, say
     * '0.71 out of 1.00' the column specifier would be
     * ["Mark", "awarded", "mark", "%.2f out of %.2f"] A special case format
     * specifier is '%h' denoting that the result object field value should be
     * treated as ready-to-output html. Empty columns are suppressed.
     */
    protected function build_results_table(qtype_sqlrunner_question $question) {
        $resultcolumns = $question->result_columns();
        $canviewhidden = self::can_view_hidden();

        // Build the table header, containing all the specified field headers,
        // unless all rows in that column would be blank.

        $columnheaders = array('iscorrect'); // First column is a tick or cross, like last column.
        $hiddencolumns = array();  // Array of true/false for each element of $colspec.
        $numvisiblecolumns = 0;

        foreach ($resultcolumns as $colspec) {

            $len = count($colspec);
            if ($len < 3) {
                $colspec[] = '%s';  // Add missing default format.
            }
            $header = $colspec[0];
            $field = $colspec[1];  // Primary field - there may be more.
            $numnonblank = self::count_non_blanks($field, $this->testresults);
            if ($numnonblank == 0) {
                $hiddencolumns[] = true;
            } else {
                $columnheaders[] = $header;
                $hiddencolumns[] = false;
                $numvisiblecolumns += 1;
            }
        }
        if ($numvisiblecolumns > 1) {
            $columnheaders[] = 'iscorrect';  // Tick or cross at the end, unless <= 1 visible columns.
        }
        $columnheaders[] = 'ishidden';   // Last column controls if row hidden or not.

        $table = array($columnheaders);

        // Process each row of the results table.
        $hidingrest = false;
        foreach ($this->testresults as $testresult) {
            $testisvisible = $this->should_display_result($testresult) && !$hidingrest;
            if ($canviewhidden || $testisvisible) {
                $fraction = $testresult->awarded / $testresult->mark;
                $tablerow = array($fraction);   // Will be rendered as tick or cross.
                $icol = 0;
                foreach ($resultcolumns as $colspec) {
                    $len = count($colspec);
                    if ($len < 3) {
                        $colspec[] = '%s';  // Add missing default format.
                    }
                    if (!$hiddencolumns[$icol]) {
                        $len = count($colspec);
                        $format = $colspec[$len - 1];
                        if ($format === '%h') {  // If it's an html format, use value wrapped in an HTML wrapper.
                            $value = $testresult->gettrimmedvalue($colspec[1]);
                            $tablerow[] = new qtype_sqlrunner_html_wrapper($value);
                        } else if ($format !== '') {  // Else if it's a non-null column.
                            $args = array($format);
                            for ($j = 1; $j < $len - 1; $j++) {
                                $value = $testresult->gettrimmedvalue($colspec[$j]);
                                $args[] = $value;
                            }
                            $content = call_user_func_array('sprintf', $args);
                            $tablerow[] = $content;
                        }
                    }
                    $icol += 1;
                }
                if ($numvisiblecolumns > 1) { // Suppress trailing tick or cross in degenerate case.
                    $tablerow[] = $fraction;
                }
                $tablerow[] = !$testisvisible;
                $table[] = $tablerow;
            }

            if ($testresult->hiderestiffail && !$testresult->iscorrect) {
                $hidingrest = true;
            }

        }

        return $table;
    }

    /**
     * Make an HTML table describing a single failing test case
     * @param string $expected the expected output from the test
     * @param string $got the actual output from the test
     */
    protected static function make_error_html($expected, $got) {
        $table = new html_table();
        $table->attributes['class'] = 'pythonrunner-test-results';
        $table->head = array(
            pythonrunner_str('expectedcolhdr'),
            pythonrunner_str('gotcolhdr'),
        );
        $table->data = array(array(html_writer::tag('pre', s($expected)), html_writer::tag('pre', s($got))));
        return html_writer::table($table);
    }
}
