<?php

namespace local_sql\observers;

use local_sql\quiz_processor;

/**
 * Observer for question and quiz editing events
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_observer {

    /**
     * Update all question attempts to the newer version of question
     *
     * @param \core\event\question_created $event
     *
     * @return bool
     */
    public static function save_question(\core\event\question_created $event){
        global $DB;
        $question = \question_bank::load_question_data($event->objectid);
        // question edited
        if ($question->version > 1){
            $questionids = $DB->get_fieldset_select('question_versions', 'questionid', 'questionbankentryid=? AND questionid!=?',
                [$question->questionbankentryid, $question->id,]);

            if (!empty($questionids)){
                [$sql, $params] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'oldq');
                $DB->set_field_select('question_attempts', 'questionid', $question->id, "questionid $sql", $params);
            }
        }

        return true;
    }

    /**
     * @param \mod_quiz\event\slot_created $event
     */
    public static function slot_created(\mod_quiz\event\slot_created $event){
        $quizid = $event->other['quizid'];
        if (empty(quiz_processor::get_attempts($quizid))){
            return;
        }
        $quiz_processor = new quiz_processor($quizid);
        $quiz_processor->create_attempts();
    }

    /**
     * @param \mod_quiz\event\slot_deleted $event
     */
    public static function slot_deleted(\mod_quiz\event\slot_deleted $event){
        $quizid = $event->other['quizid'];
        if (empty(quiz_processor::get_attempts($quizid))){
            return;
        }
        $quiz_processor = new quiz_processor($quizid);
        $quiz_processor->delete_question_attempts();
    }

    /**
     * @param \mod_quiz\event\slot_moved $event
     */
    public static function slot_moved(\mod_quiz\event\slot_moved $event){
        $quizid = $event->other['quizid'];
        if (empty(quiz_processor::get_attempts($quizid))){
            return;
        }
        $quiz_processor = new quiz_processor($quizid);
        $quiz_processor->slot_moved($event->other['previousslotnumber'], $event->objectid);
    }
}