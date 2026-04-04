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
/** The NearEqualityGrader class. Compares the output from a given test case,
 *  awarding full marks if and only if the output "nearly matches" the expected
 *  output. Otherwise, zero marks are awarded. The output is deemed to "nearly
 *  match" the expected if the two are byte for byte identical after trailing
 *  white space and blank lines have been removed from both, sequences of spaces
 *  and tabs have been reduced to a single space and all letters have been
 *  converted to lower case.
 */

/**
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2013, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_pythonrunner_near_equality_grader extends qtype_sqlrunner_near_equality_grader {

    use \qtype_pythonrunner\traits\python_grader_trait;

    protected function grade_known_good(&$output, &$testcase) {
        $cleanedoutput = qtype_pythonrunner_util::clean($output);
        $cleanedexpected = qtype_pythonrunner_util::clean($testcase->expected);
        $iscorrect = $this->reduce($cleanedoutput) == $this->reduce($cleanedexpected);
        $awardedmark = $iscorrect ? $testcase->mark : 0.0;
        return new qtype_sqlrunner_test_result($testcase, $iscorrect, $awardedmark, $cleanedoutput);
    }
}
