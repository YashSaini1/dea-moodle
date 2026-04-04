<?php

/** The SqlEqualityGrader class. Compares the output from a given test case,
 *  awarding full marks if and only if the output "nearly matches" the expected
 *  output. Otherwise, zero marks are awarded. The output is deemed to "nearly
 *  match" the expected if the two are byte for byte identical after trailing
 *  white space and blank lines have been removed from both, sequences of spaces
 *  and tabs have been reduced to a single space and all letters have been
 *  converted to lower case.
 */

/**
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  Richard Lobb, 2013, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_sqlrunner\constants as C;

defined('MOODLE_INTERNAL') || die();

class qtype_sqlrunner_sql_equality_grader extends qtype_sqlrunner_grader {

    /**
     * This grader tests if zipped sql output matches the actual expected data
     */
    public function name() {
        return 'SqlEqualityGrader';
    }

    protected function grade_known_good(&$output, &$testcase) {
        $cleanedoutput = decode_sql_output(strtolower($output));
        $cleanedexpected = decode_sql_output(strtolower($testcase->expected) ?? '');

        $cleanedoutput = !empty($cleanedoutput) && $cleanedoutput['data'] != C::EMPTY_SET ? $cleanedoutput['data'] : [];
        $cleanedexpected = !empty($cleanedexpected) && $cleanedexpected['data'] != C::EMPTY_SET ? $cleanedexpected['data'] : [];
        if (empty($cleanedexpected) && !empty($cleanedoutput) || !empty($cleanedexpected) && empty($cleanedoutput)){
            $awardedmark = 0.0;
            return new qtype_sqlrunner_sql_test_result($testcase, false, $awardedmark, $output);
        }

        $iscorrect = $this->_is_the_same_array($cleanedexpected, $cleanedoutput);

        $awardedmark = $iscorrect ? 1 : 0.0;
        return new qtype_sqlrunner_sql_test_result($testcase, $iscorrect, $awardedmark, $output);
    }

    /**
     * Custom array_diff function
     * Check, that all elements in array1 (each element is an array too) equals to array2 by values
     *
     * @param array[] $array1
     * @param array[] $array2
     *
     * @return bool
     */
    protected function _is_the_same_array($array1, $array2){
        $array1_data = [];
        $encode_value = function($arr){
            $arr = array_values($arr);
            sort($arr);
            return json_encode($arr);
        };

        foreach ($array1 as $value){
            $encoded = $encode_value($value);
            $array1_data[$encoded] = !empty($array1_data[$encoded]) ? $array1_data[$encoded] + 1 : 1;
        }

        foreach ($array2 as $value){
            $encoded = $encode_value($value);
            if (empty($array1_data[$encoded])){
                return false;
            }
            $array1_data[$encoded]--;
            if (empty($array1_data[$encoded])){
                unset($array1_data[$encoded]);
            }
        }

        return empty($array1_data);
    }
}