<?php

/**
 * Python runner answer page
 *
 * @package   qtype_pythonrunner
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/theme/sql/quiz_lib.php');
require_once($CFG->dirroot . '/question/type/pythonrunner/lib.php');

// Get the parameters from the URL.
$attemptid = required_param('attemptid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

$attemptobj = theme_sql_quiz_create_attempt_handling_errors($attemptid);
$qa =$attemptobj->get_question_attempt($slot);
$quizobj = $attemptobj->get_quizobj();

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

$questionid = $qa->get_question_id();
$question = $qa->get_question();
$cases = ($question->testcases) ? reset($question->testcases) : null;
$testcode = ($cases && $cases->testcode) ? $cases->testcode : '';
$expected = ($cases && $cases->expected) ? $cases->expected : '';

$context = $quizobj->get_context();

/* This code is needed for block_sql_comment to work */
$page = $attemptobj->get_question_page($slot);
$slots = [
    0 => $slot,
];

$name = $attemptobj->get_question_name($slot);

$PAGE->set_url('/question/type/pythonrunner/answer.php', array('id' => $context->id));
$PAGE->set_context($context);
$PAGE->set_title($name);
$PAGE->set_pagelayout('questioneditor');
$PAGE->add_body_classes(['limitedwidth', 'answer_page']);

$title = sqlrunner_str('answer');
$PAGE->navbar->add($name,$attemptobj->attempt_url(null, $page),\navigation_node::TYPE_CUSTOM,null,'name');
$PAGE->navbar->add($title);
$PAGE->set_heading('', 1);

/** @var mod_quiz_renderer $output */
$output = $PAGE->get_renderer('mod_quiz');

$renderer = $PAGE->get_renderer('qtype_pythonrunner');

// Display.
echo $output->header();
echo $output->heading($title, 3);

echo html_writer::start_div('answer_wrapper');
//$qa->get_control_field_name()
$display = new question_display_options();
$display->readonly = true;

echo $renderer->formulation_and_controls($qa, $display, $testcode);
//echo html_writer::div('<pre>'.$testcode.'</pre>', 'testcode');
//echo html_writer::div(python_build_result($expected, false), 'expected');
echo html_writer::end_div();

echo $output->footer();
