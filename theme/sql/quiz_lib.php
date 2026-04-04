<?php

use theme_sql\mod_quiz\theme_sql_quiz_attempt;

// mod/quiz/locallib.php already connected here

/**
 * Get quiz attempt and handling error.
 *
 * @param int $attemptid the id of the current attempt.
 * @param int|null $cmid the course_module id for this quiz.
 * @return quiz_attempt $attemptobj all the data about the quiz attempt.
 * @throws moodle_exception
 */
function theme_sql_quiz_create_attempt_handling_errors($attemptid, $cmid = null) {
    try {
        $attempobj = theme_sql_quiz_attempt::create($attemptid);
    } catch (moodle_exception $e) {
        if (!empty($cmid)) {
            list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
            $continuelink = new moodle_url('/mod/quiz/view.php', array('id' => $cmid));
            $context = context_module::instance($cm->id);
            if (has_capability('mod/quiz:preview', $context)) {
                throw new moodle_exception('attempterrorcontentchange', 'quiz', $continuelink);
            } else {
                throw new moodle_exception('attempterrorcontentchangeforuser', 'quiz', $continuelink);
            }
        } else {
            throw new moodle_exception('attempterrorinvalid', 'quiz');
        }
    }
    if (!empty($cmid) && $attempobj->get_cmid() != $cmid) {
        throw new moodle_exception('invalidcoursemodule');
    } else {
        return $attempobj;
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $quiz       quiz object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.1
 */
function theme_sql_quiz_view($quiz, $course, $cm, $context) {

    $params = array(
        'objectid' => $quiz->id,
        'context' => $context
    );

    $event = \mod_quiz\event\course_module_viewed::create($params);
    $event->add_record_snapshot('quiz', $quiz);
    $event->trigger();

    // Completion.
    // do not track view, because view will be tracked if all questions complete

//    $completion = new completion_info($course);
//    $completion->set_module_viewed($cm);
}

/**
 * Deletes question and all associated data from the database
 *
 * It will not delete a question if it is used somewhere, instead it will just delete the reference.
 *
 * @param int $questionid The id of the question being deleted
 */
function sql_question_delete_question($questionid): void {
    global $DB, $CFG;
    require_once($CFG->libdir.'/questionlib.php');

    $question = $DB->get_record('question', ['id' => $questionid]);
    if (!$question) {
        // In some situations, for example if this was a child of a
        // Cloze question that was previously deleted, the question may already
        // have gone. In this case, just do nothing.
        return;
    }

    $sql = 'SELECT qv.id as versionid,
                   qv.version,
                   qbe.id as entryid,
                   qc.id as categoryid,
                   qc.contextid as contextid
              FROM {question} q
              LEFT JOIN {question_versions} qv ON qv.questionid = q.id
              LEFT JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
              LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
             WHERE q.id = ?';
    $questiondata = $DB->get_record_sql($sql, [$question->id]);

    $questionstocheck = [$question->id];

    if ($question->parent) {
        $questionstocheck[] = $question->parent;
    }

    // Do not delete a question if it is used by an activity module
//    if (questions_in_use($questionstocheck)) {
//        return;
//    }

    // This sometimes happens in old sites with bad data.
    if (!$questiondata->contextid) {
        debugging('Deleting question ' . $question->id . ' which is no longer linked to a context. ' .
            'Assuming system context to avoid errors, but this may mean that some data like files, ' .
            'tags, are not cleaned up.');
        $questiondata->contextid = context_system::instance()->id;
        $questiondata->categoryid = 0;
    }

    // Delete previews of the question.
    $dm = new question_engine_data_mapper();
    $dm->delete_previews($question->id);

    // Delete questiontype-specific data.
    question_bank::get_qtype($question->qtype, false)->delete_question($question->id, $questiondata->contextid);

    // Delete all tag instances.
    core_tag_tag::remove_all_item_tags('core_question', 'question', $question->id);

    // Delete the custom filed data for the question.
    $customfieldhandler = qbank_customfields\customfield\question_handler::create();
    $customfieldhandler->delete_instance($question->id);

    // Now recursively delete all child questions
    if ($children = $DB->get_records('question',
        array('parent' => $questionid), '', 'id, qtype')) {
        foreach ($children as $child) {
            if ($child->id != $questionid) {
                sql_question_delete_question($child->id);
            }
        }
    }

    // Delete question comments.
    $DB->delete_records('comments', ['itemid' => $questionid, 'component' => 'qbank_comment',
                                     'commentarea' => 'question']);
    // Finally delete the question record itself.
    $DB->delete_records('question', ['id' => $question->id]);
    $DB->delete_records('question_versions', ['id' => $questiondata->versionid]);
    $DB->delete_records('question_references',
        [
            'version' => $questiondata->version,
            'questionbankentryid' => $questiondata->entryid,
        ]);
    delete_question_bank_entry($questiondata->entryid);
    question_bank::notify_question_edited($question->id);

    // Log the deletion of this question.
    $question->category = $questiondata->categoryid;
    $question->contextid = $questiondata->contextid;
    $event = \core\event\question_deleted::create_from_question_instance($question);
    $event->add_record_snapshot('question', $question);
    $event->trigger();
}