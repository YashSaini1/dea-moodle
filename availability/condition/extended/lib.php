<?php

use local_sql\moodle\role_manager;

function get_courseid_by_sectionid(int $sectionid): int {
    global $DB;
    return $DB->get_field('course_sections', 'course', ['id' => $sectionid], MUST_EXIST);
}

function auto_enrol_user(int $sectionid, int $userid): void {
    $courseid = get_courseid_by_sectionid($sectionid);

    if (is_enrolled(\context_course::instance($courseid), $userid)) return;

    $role = role_manager::get_student_role();
    $manual = enrol_get_plugin('manual');

    $enrols = enrol_get_instances($courseid, true);
    if (empty($enrols)) return;

    foreach ($enrols as $enrol){
        $manual->enrol_user($enrol, $userid, $role->id, time());
    }
}

function auto_unenrol_user(int $sectionid, int $userid): void {
    $courseid = get_courseid_by_sectionid($sectionid);

    if (!is_enrolled(\context_course::instance($courseid), $userid)) return;

    $manual = enrol_get_plugin('manual');

    $enrols = enrol_get_instances($courseid, true);
    if (empty($enrols)) return;

    foreach ($enrols as $enrol){
        $manual->unenrol_user($enrol, $userid);
    }
}

function user_admitted(int $courseid): bool {
    global $DB, $USER;

    $sql = "SELECT EXISTS (
                SELECT 1
                FROM {sql_extended} se
                JOIN {course_sections} cs ON se.sectionid = cs.id
                WHERE se.userid = ?
                AND cs.course = ?
            )";

    return $DB->get_field_sql($sql, [$USER->id, $courseid]);
}