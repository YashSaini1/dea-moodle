<?php

/**
 *  Defines a sql_test_result object, which captures the result of a single
 *  testcase run. It contains all the information required to display
 *  one row of the test result table, including all the fields from the
 *  original testcase.
 *  It is treated as a simple record rather than a true class object.
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  Richard Lobb, 2013, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_sqlrunner_sql_test_result extends \qtype_sqlrunner_test_result {

    public function __construct($testcase, $iscorrect, $awardedmark, $got){
        $expected = $testcase->expected;
        parent::__construct($testcase, $iscorrect, $awardedmark, $got);

        $this->expected = $expected;
        $this->got = $got;
    }
}