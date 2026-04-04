<?php

/**
 * Local SQL upgrade lib
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_sql\moodle\course_customfield;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Upgrade the repository position.
 * We need only the upload file repo
 */
function update_repository_sortnumber(){
    global $DB;

    $repositories = $DB->get_records('repository');
    if (empty($repositories)){
        return;
    }

    $repo = null;
    foreach ($repositories as $repo){
        if ($repo->type == 'upload'){
            break;
        }
    }

    if (!$repo){
        return;
    }

    $first = reset($repositories);
    $first->sortorder = $repo->sortorder;
    $repo->sortorder = 1;

    $DB->update_record('repository', $first);
    $DB->update_record('repository', $repo);
}

/**
 * Create a field for entering the number of questions
 *
 * @throws dml_exception
 */
function create_custom_field(){
    global $DB;
    // Create a category for a field
    $category_name = "Number of free questions";
    $category_id = $DB->get_field('customfield_category', 'id', ['name' => $category_name]);
    if (!$category_id) {
        $mdl_customfield_category = array(
            "name" => "Number of free questions",
            "description" => NULL,
            "descriptionformat" => 0,
            "sortorder" => 0,
            "timecreated" => time(),
            "timemodified" => time(),
            "component" => "core_course",
            "area" => "course",
            "itemid" => 0,
            "contextid" => 1,
        );
        $category_id = $DB->insert_record('customfield_category', $mdl_customfield_category);
    }

    $category_name = "Is coaching course?";
    $category_coaching_course_id = $DB->get_field('customfield_category', 'id', ['name' => $category_name]);
    if (!$category_coaching_course_id) {
        $mdl_customfield_category = array(
            "name" => $category_name,
            "description" => NULL,
            "descriptionformat" => 0,
            "sortorder" => 1,
            "timecreated" => time(),
            "timemodified" => time(),
            "component" => "core_course",
            "area" => "course",
            "itemid" => 0,
            "contextid" => 1,
        );
        $category_coaching_course_id = $DB->insert_record('customfield_category', $mdl_customfield_category);
    }

    // Create a field for entering the number of questions
    $field_id = $DB->get_field('customfield_field', 'id', ['shortname' => course_customfield::FREE_QUESTION_FIELD]);
    if (!$field_id) {
        $mdl_customfield_field = array(
            "shortname" => course_customfield::FREE_QUESTION_FIELD,
            "name" => "Number of questions for the free version",
            "type" => "text",
            "description" => "<p dir=\"ltr\" style=\"text-align: left;\">Number of questions for the free version<br></p>",
            "descriptionformat" => 1,
            "sortorder" => 0,
            "categoryid" => $category_id,
            "configdata" => '{"required":"0","uniquevalues":"0","defaultvalue":"5","displaysize":50,"maxlength":1333,"ispassword":"0","link":"","locked":"0","visibility":"2"}',
            "timecreated" => time(),
            "timemodified" => time(),
        );
        $DB->insert_record('customfield_field', $mdl_customfield_field);
    }

    $field_id = $DB->get_field('customfield_field', 'id', ['shortname' => course_customfield::COACHING_COURSE_FIELD]);
    if (!$field_id) {
        $mdl_customfield_field = array(
            "shortname" => course_customfield::COACHING_COURSE_FIELD,
            "name" => "Is coaching course?",
            "type" => "checkbox",
            "description" => "",
            "descriptionformat" => 1,
            "sortorder" => 0,
            "categoryid" => $category_coaching_course_id,
            "configdata" => '{"required":"0","uniquevalues":"0","checkbydefault":"0","locked":"0","visibility":"2"}',
            "timecreated" => time(),
            "timemodified" => time(),
        );
        $DB->insert_record('customfield_field', $mdl_customfield_field);
    }
}