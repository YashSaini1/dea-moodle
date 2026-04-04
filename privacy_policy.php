<?php

/**
 * Client wanna to a beautiful url for this page, therefore we move out this file
 *
 * @package local_sql
 */
require_once('config.php');

$context = context_system::instance();
$PAGE->set_context($context);

if (!file_exists($CFG->dirroot.'/local/sql/version.php')){
    redirect('/');
}

$PAGE->set_url('/privacy_policy.php');
$PAGE->add_body_class('limitedwidth');

$heading = get_string('privacy', 'local_sql');
$PAGE->set_heading($heading);
$PAGE->set_title($heading);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_sql/privacy_policy', []);
echo $OUTPUT->footer();