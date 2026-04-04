<?php

/** The PythonEqualityGrader class. Compares the output from a given test case,
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

use qtype_pythonrunner\constants as C;

defined('MOODLE_INTERNAL') || die();

class qtype_pythonrunner_python_equality_grader extends qtype_pythonrunner_grader {

    /**
     * This grader tests if zipped sql output matches the actual expected data
     */
    public function name(){
        return 'PythonEqualityGrader';
    }

    public function grade_known_good(&$output, &$testcase){
        $cleanedoutput = strtolower(trim($output));
        $cleanedexpected = strtolower(trim($testcase->expected));

        $cleanedoutput = qtype_sqlrunner_util::clean($cleanedoutput);
        $cleanedexpected = qtype_sqlrunner_util::clean($cleanedexpected);

        $cleanedoutput = preg_replace('/('.PHP_EOL.'){2,}/', PHP_EOL, $cleanedoutput);
        $cleanedexpected = preg_replace('/('.PHP_EOL.'){2,}/', PHP_EOL, $cleanedexpected);

        $iscorrect = $cleanedoutput == $cleanedexpected;
        $awardedmark = $iscorrect ? $testcase->mark : 0.0;

        return new qtype_sqlrunner_sql_test_result($testcase, $iscorrect, $awardedmark, $output);
    }
}