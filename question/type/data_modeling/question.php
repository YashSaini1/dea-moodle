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
 * Data modeling question definition class.
 *
 * @package    qtype
 * @subpackage data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/behaviour/adaptive_adapted_for_sqlrunner/behaviour.php');
require_once($CFG->dirroot.'/question/type/data_modeling/lib.php');

/**
 * Represents a data_modeling question.
 *
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class qtype_data_modeling_question extends question_graded_automatically {
    /** @var string question_answer code. */
    public $answer = '';

    public function make_behaviour(question_attempt $qa, $preferredbehaviour){
        // Regardless of the preferred behaviour, always use an adaptive
        // behaviour.
        return new qbehaviour_adaptive_adapted_for_sqlrunner($qa, $preferredbehaviour);
    }

    public function get_expected_data(){
        return array('answer' => PARAM_RAW_TRIMMED, 'table_code' => PARAM_RAW_TRIMMED, 'videourl' => PARAM_RAW_TRIMMED);
    }

    public function start_attempt(question_attempt_step $step, $variant){
//        $step->set_qt_var('_separators',$this->ap->get_point() . '$' . $this->ap->get_separator());
    }

    public function summarise_response(array $response){
        if (isset($response['answer'])){
            $resp = $response['answer'];
        } else {
            $resp = null;
        }

        return $resp;
    }

    public function is_gradable_response(array $response){
        return array_key_exists('answer', $response) && array_key_exists('table_code', $response);
    }

    public function is_complete_response(array $response){
        return $this->is_gradable_response($response);
    }

    public function get_validation_error(array $response){
        if (!$this->is_gradable_response($response)){
            return 'You must provide a valid response.';
        }
        return '';
    }

    public function is_same_response(array $prevresponse, array $newresponse){
        if (!question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer')){
            return false;
        }

        return true;
    }

    public function get_correct_response(){
        return [
            'answer'     => $this->answer,
            'table_code' => '',
            'videourl'   => '',
        ];
    }

    public function get_correct_answer(){
        return $this->answer;
    }

    public function grade_response(array $response){
//        if ($this->has_separate_unit_field()) {
//            $selectedunit = $response['unit'];
//        } else {
//            $selectedunit = null;
//        }
//        list($value, $unit, $multiplier) = $this->ap->apply_units(
//                $response['answer'], $selectedunit);
//
//        $answer = $this->get_matching_answer($value, $multiplier);
//        if (!$answer) {
//            return array(0, question_state::$gradedwrong);
//        }
//
//        $fraction = $this->apply_unit_penalty($answer->fraction, $answer->unitisright);
//        return array($fraction, question_state::graded_state_for_fraction($fraction));
        return array(1, question_state::$complete, [
//                '_answer'     => $response['answer'],
                '_table_code' => $response['table_code'],
            ],
        );
    }
}