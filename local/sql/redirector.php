<?php

/**
 * Redirect to the oauth login page (add sesskey parameter for those who is coming from Wordpress landing)
 *
 * @package     local_sql
 * @copyright   2024 Inna Kovtyak <inna.shik@smartapptech.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$pmurl = optional_param('wantsurl',null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_TEXT);

$PAGE->set_url('/local/sql/redirector.php');

$PAGE->set_context(\context_system::instance());

$PAGE->set_title("Redirect");

if(is_null($id) || is_null($pmurl)) {
    redirect(new moodle_url('/login/index.php'));
}

redirect(new moodle_url('/auth/oauth2/login.php', ['id' => $id, 'wantsurl' => $pmurl, 'sesskey' => sesskey()]));