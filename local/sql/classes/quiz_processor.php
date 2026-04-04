<?php

namespace local_sql;

use theme_sql\mod_quiz\theme_sql_quiz;
use theme_sql\mod_quiz\theme_sql_quiz_attempt;
use theme_sql\mod_quiz\theme_sql_structure;

/**
 * Processor to update quiz or question attempts if quiz will be edited (create|update|move|delete question)
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_processor {

    /**
     * @var int
     */
    protected $_quizid;

    /**
     * @var theme_sql_quiz
     */
    protected $_quiz;

    /**
     * @var theme_sql_quiz
     */
    protected $_structure;

    public function __construct($quizid){
        $this->_quizid = $quizid;
        $this->_quiz = theme_sql_quiz::create($quizid);
        $this->_structure = theme_sql_structure::create_for_quiz($this->_quiz);
    }

    /**
     * Load and save the attempts data.
     *
     * @param int $quizid
     *
     * @return array|mixed
     */
    public static function get_attempts($quizid){
        global $DB;
        static $attempts = [];
        if (!array_key_exists($quizid, $attempts)){
            $attempts[$quizid] = $DB->get_records('quiz_attempts', ['quiz' => $quizid, 'preview' => 0]);
        }
        return $attempts[$quizid];
    }

    /**
     * Check all quiz questions and create question attempts in existing quiz attempts
     *
     * @return bool
     */
    public function create_attempts(){
        $this->_quiz->preload_questions();
        $this->_quiz->load_questions();

        $quiz_attempts = static::get_attempts($this->_quizid);
        $qa_params = [
            'quiz' => $this->_quiz,
        ];

        foreach ($quiz_attempts as $attempt_rec){
            $qa_params['attempt'] = $attempt_rec;
            $qa = theme_sql_quiz_attempt::create_from_params($qa_params);
            $qa->check_all_question_attempts();
        }
        return true;
    }

    /**
     * Check all slots for existing and repaginate question_attempts
     * If attempt for question exists in quiz attempt, but this question is not exists
     * in a quiz, this attempt will be deleted
     */
    public function delete_question_attempts(){
        global $DB;
        $this->_quiz->preload_questions();
        $this->_quiz->load_questions();

        // if no attempts return
        if (empty(static::get_attempts($this->_quizid))) return;

        $quiz_attempts = static::get_attempts($this->_quizid);

        // delete all existing quiz attempts if no questions in quiz
        if (empty($this->_quiz->has_questions())){
            foreach ($quiz_attempts as $attempt_rec){
                quiz_delete_attempt($attempt_rec, $this->_quiz->get_quiz());
            }
            return;
        }

        $questions = $this->_quiz->get_questions();
        // once calculate quiz layout
        $layout = $this->_get_quiz_layout($questions);

        $quiz_attempts = static::load_question_attempts($quiz_attempts);
        $update_question_att = $delete_question_att = $update_quiz_att = [];

        /** @var \stdClass $qa quiz attempt */
        foreach ($quiz_attempts as $qa){
            foreach ($qa->question_attempts as $q_att){
                // this question is deleted, need to delete this attempt
                if (!array_key_exists($q_att->questionid, $questions)){
                    $delete_question_att[] = $q_att->id;
                    continue;
                }
                $question = $questions[$q_att->questionid];
                // this question is moved or its slot after deleted question, update its slot
                if ($q_att->slot != $question->slot){
                    $update_question_att[] = (object)[
                        'id'   => $q_att->id,
                        'slot' => $question->slot,
                    ];
                }

            }
            // save layout
            $update_quiz_att[] = (object)[
                'id'     => $qa->id,
                'layout' => $layout,
            ];
        }

        $transaction = $DB->start_delegated_transaction();

        // first delete useless attempts, because table question attempts
        // has an uniq constraint for questionusageid and slot fields
        $this->_delete_question_attempts($delete_question_att);

        // update new question_attempts
        theme_sql_quiz_attempt::save_question_attempts($update_question_att);

        // update quiz_attempts
        foreach ($update_quiz_att as $qa){
            theme_sql_quiz_attempt::save_record($qa);
        }

        $transaction->allow_commit();
    }

    protected function _get_quiz_layout($questions){
        $layout = [];
        foreach ($questions as $q){
            $layout[] = $q->slot;
            $layout[] = 0;
        }
        return implode(',', $layout);
    }

    /**
     * This function is custom analog of from @see question_engine_data_mapper::delete_questions_usage_by_activities()
     * But we don't need to delete question_usage, only question_attempt
     *
     * Also do not delete student files because our custom question do not contains any uploads
     */
    protected function _delete_question_attempts($qaids){
        global $DB;

        if ($DB->get_dbfamily() == 'mysql'){
            $this->_delete_question_attempts_records_for_mysql($qaids);
            return;
        }

        [$sql_qaids, $params] = $DB->get_in_or_equal($qaids);
        $where = "qa.id $sql_qaids";

        $DB->delete_records_select('question_attempt_step_data', "attemptstepid IN (
                SELECT qas.id
                FROM {question_attempts} qa
                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                WHERE $where)", $params);

        $DB->delete_records_select('question_attempt_steps', "questionattemptid IN (
                SELECT qa.id
                FROM {question_attempts} qa
                WHERE $where)", $params);

        $DB->delete_records_select('question_attempts', "id $sql_qaids", $params);
    }

    /**
     * This is copy of @see question_engine_data_mapper::delete_usage_records_for_mysql()
     * But we don't need to delete question_usage, only question_attempt
     *
     * @param array $qaids question attempts ids
     */
    protected function _delete_question_attempts_records_for_mysql(array $qaids){
        global $DB;
        // Get the list of question attempts to delete and delete them in chunks.
        [$qa_sql, $qa_params] = $DB->get_in_or_equal($qaids);

        $DB->execute('
                DELETE qa, qas, qasd
                  FROM {question_attempts}          qa
             LEFT JOIN {question_attempt_steps}     qas  ON qas.questionattemptid = qa.id
             LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                 WHERE qa.id '.$qa_sql,
            $qa_params);
    }

    /**
     * Update all affected question attempts after slot moving on quiz questions page
     *
     * @param int $previousslot
     * @param int $slotid
     */
    public function slot_moved($previousslot, $slotid){
        global $DB;
        $this->_quiz->preload_questions();
        $this->_quiz->load_questions();

        $quiz_attempts = static::get_attempts($this->_quizid);
        $questions = $this->_quiz->get_questions();
        // once calculate quiz layout
        $layout = $this->_get_quiz_layout($questions);

        $newslot = $this->_structure->get_slot_by_id($slotid)->slot;

        // new slot number,
        $max_slot = count($questions) + 1;
        // load direction needs because question_attempts table have an uniq constraint, so we cannot save
        // two question attempts with the same slot in 1 quiz attempt.
        // instead of this, we load question attempts in needed order
        $load_direction = ($newslot > $previousslot);

        $quiz_attempts = static::load_question_attempts($quiz_attempts, $load_direction);
        $update_question_att = $moved_question_attempts = [];

        /** @var \stdClass $qa quiz attempt */
        foreach ($quiz_attempts as $qa){
            foreach ($qa->question_attempts as $q_att){
                $question = $questions[$q_att->questionid];
                // this question is moved or its slot after deleted question, update its slot
                if ($q_att->slot != $question->slot){
                    $updatedslot = $question->slot;

                    // if this is moved question, set him unreal slot number ($maxslot).
                    // this needs due the facts, that question_attempts table have an uniq constraint
                    // after saving all other attempts, we update this value to correct one
                    if ($q_att->slot == $previousslot){
                        $updatedslot = $max_slot;
                        $moved_question_attempts[] = $q_att->id;
                    }

                    $update_question_att[] = (object)[
                        'id'   => $q_att->id,
                        'slot' => $updatedslot,
                    ];
                }
            }
            // save layout
            $update_quiz_att[] = (object)[
                'id'     => $qa->id,
                'layout' => $layout,
            ];
        }

        $transaction = $DB->start_delegated_transaction();

        // update new question_attempts
        theme_sql_quiz_attempt::save_question_attempts($update_question_att);

        // Set the correct slot value to temp question_attempts
        [$qa_sql, $qa_params] = $DB->get_in_or_equal($moved_question_attempts, SQL_PARAMS_NAMED, 'qaid');
        $qa_params['newvalue'] = $newslot <= 0 ? 1 : $newslot;
        $DB->execute("UPDATE {question_attempts} SET slot=:newvalue WHERE id $qa_sql", $qa_params);

        // update quiz_attempts
        foreach ($update_quiz_att as $qa){
            theme_sql_quiz_attempt::save_record($qa);
        }

        $transaction->allow_commit();
    }

    public function repair_quiz_attempts(){
        global $DB;
        $this->_quiz->preload_questions();
        $this->_quiz->load_questions();

        $quiz_attempts = static::get_attempts($this->_quizid);
        $questions = $this->_quiz->get_questions();
        $quiz_attempts = static::load_question_attempts($quiz_attempts);

        $updated_question_attempts = [];

        foreach ($questions as $q){
            $i = $q->slot - 1;
            foreach ($quiz_attempts as $qa){
                $question_attempts = $qa->question_attempts;
                if (array_key_exists($i, $question_attempts) && $question_attempts[$i]->questionid != $q->id){
                    $updated_question_attempts[] = [
                        'id'         => $question_attempts[$i]->id,
                        'questionid' => $q->id,
                    ];
                }
            }
        }

        if (!empty($updated_question_attempts)){
            echo 'update '.print_r($updated_question_attempts, 1);
            theme_sql_quiz_attempt::save_question_attempts($updated_question_attempts);
        } else {
            echo 'nothing to update';
        }
    }

    public static function load_question_attempts($quiz_attempts, $asc_direction = true){
        global $DB;
        [$qa_sql, $params] = $DB->get_in_or_equal(array_keys($quiz_attempts));

        $direction = 'ASC';
        if (!$asc_direction){
            $direction = 'DESC';
        }

        $sql = "SELECT que_a.*, qa.id as quiz_attempt
                FROM {quiz_attempts} qa
                JOIN {question_usages} qu ON qu.id = qa.uniqueid
                JOIN {question_attempts} que_a ON que_a.questionusageid = qu.id
                WHERE qa.id $qa_sql
                ORDER BY qa.id, que_a.slot $direction";


        $rs = $DB->get_recordset_sql($sql, $params);
        if (!$rs->valid()){
            // Not going to iterate (but exit), close rs.
            $rs->close();
            return [];
        }

        foreach ($rs as $question_att){
            $qaid = $question_att->quiz_attempt;
//            $quiz_attempts[$qaid]->question_attempts =  $quiz_attempts[$qaid]->qestion_attempts ?? [];
            $quiz_attempts[$qaid]->question_attempts[] = $question_att;
        }
        return $quiz_attempts;
    }
}