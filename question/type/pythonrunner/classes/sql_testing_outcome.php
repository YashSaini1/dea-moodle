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

class qtype_pythonrunner_sql_testing_outcome extends qtype_sqlrunner_testing_outcome {

    protected function print_admin_result_table(){
        $numerrors = 0;
        $button_name = 'Set SQL result';
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
                $o .= '<div class="sqlfailrow_'.$rownum.'">
                        <div class="expected" style="display:none">
                            <pre id="id_fail_expected_'.$rownum.'">'.$expected.'</pre>
                        </div>
                        <div class="result">
                            <pre id="id_sqlgot_'.$rownum.'" style="display:none">'.$got.'</pre>
                            <button type="button" class="replace_templateparams_with_sqlgot" data-order="'.$rownum.'"> '.$button_name.'</button>
                        </div>
                    </div>';
                $o .= render_submission_table($got);
            }
        }

        if (empty($o)){
            return '';
        }

        return html_writer::div($o, 'results');
    }
}
