<?php

/**
 * sqlrunner question definition classes.
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  Richard Lobb, 2011, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_sqlrunner\sqlrunner_database_worker as db;

require_once ('../../../config.php');

$dbname = optional_param('dbname', '', PARAM_TEXT);
$username = optional_param('username', '', PARAM_TEXT);
$userpass = optional_param('userpass', '', PARAM_TEXT);
$create = optional_param('create', false, PARAM_INT);
$exists = optional_param('exists', false, PARAM_INT);
$delete = optional_param('delete', false, PARAM_INT);
$select = optional_param('select', false, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagetype('admin');
$PAGE->set_url('/question/type/sqlrunner/dbsettings.php');

echo $OUTPUT->header();

$db = db::get_instance(null, true);
if ($create){
    if($db->create_database($dbname, $username, $userpass)){
        echo 'created';
    } else {
        echo 'not created';
    }
} elseif ($exists) {
    if($db->check_database_exists($dbname)){
        echo 'exists';
    } else {
        echo 'not exists';
    }
} elseif ($delete) {
    if($db->check_database_exists($dbname)){
        echo 'exists';
        if($db->delete_database($dbname)){
            echo "\ndeleted";
        } else {
            echo "\nnot deleted";
        }
    } else {
        echo 'not exists';
    }
} elseif ($select){
    echo $db->run_query('SELECT * FROM test_table WHERE id > 10;');
}

//echo 'qwe';
echo $OUTPUT->footer();