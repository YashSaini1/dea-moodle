<?php
/**
 * Plugin lib
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_sql\core\sql_database;
use local_sql\moodle\role_manager;

require_once($CFG->dirroot.'/local/sql/files_lib.php');

/**
 * Enrol user to all courses in default category (for our system is a client courses)
 *
 * @param int $userid
 */
function local_sql_enrol_user_to_all_courses($userid){
    global $CFG, $DB;
    require_once($CFG->dirroot.'/theme/sql/lib.php');

    // Do not enrol user to the coaching courses
    $defaultcategory = \core_course_category::get_default();
    $params = ['category' => $defaultcategory->id];
    $coaching_courses = \local_sql\coaching::get_courses();
    $coaching_sql = '';
    if (!empty($coaching_courses)){
        [$sql, $coaching_params] = $DB->get_in_or_equal(array_keys($coaching_courses), SQL_PARAMS_NAMED, 'coachingcourse', false);
        $params += $coaching_params;
        $coaching_sql .= ' AND id '.$sql;
    }
    $courses = $DB->get_records_select('course', 'category=:category '.$coaching_sql, $params);

    \local_sql\core::enrol_user_to_courses($userid, $courses);
}

/**
 * Enrol all "our" users to new course if it in default category (for our system is a client courses)
 * @see \local_sql\moodle\user::get_users_with_role()
 *
 * @param int $courseid
 */
function local_sql_enrol_all_users_to_course($courseid){
    try {
        $course = get_course($courseid);
    } catch (\Exception $e){
        mtrace('Course doesn\'t exists');
        return;
    }

    $defaultcategory = \core_course_category::get_default();
    if ($course->category != $defaultcategory->id) return;

    $timeenrol = time();
    $manual = enrol_get_plugin('manual');

    $enrols = enrol_get_instances($course->id, true);
    if (empty($enrols)){
        mtrace('Course doesn\'t have enrol instances');
        return;
    }

    $roles = [];
    if (\local_sql\coaching::is_coaching_course($courseid)){
        $roles = [
            role_manager::COACHING_ROLE,
            role_manager::STUDENT_ROLE,
            role_manager::ADMIN_ROLE,
        ];
    }

    $users = \local_sql\moodle\user::get_users_with_role($roles);
    foreach ($users as $userinfo){
        foreach ($enrols as $enrol){
            $manual->enrol_user($enrol, $userinfo->userid, $userinfo->roleid, $timeenrol);
        }
    }
    mtrace('Enrolled '.count($users).' users');
}

/**
 * Set userpreference to upload_repository (needs that filepicker works good)
 *
 * @param int $userid
 */
function update_filepicker_preference($userid){
    global $DB;

    $upload_repo = $DB->get_record('repository', ['type' => 'upload']);
    if (empty($upload_repo)) return;

    $repo_preference = $DB->get_record('user_preferences', ['userid' => $userid, 'name' => 'filepicker_recentrepository']);
    if (empty($repo_preference)){
        $preferences = new stdClass();
        $preferences->userid = $userid;
        $preferences->name = 'filepicker_recentrepository';
        $preferences->value = $upload_repo->id;
        $DB->insert_record('user_preferences', $preferences);
        return;
    }
    $repo_preference->value = $upload_repo->id;
    $DB->update_record('user_preferences', $repo_preference);
}

/**
 * Clear inputted string
 *
 * @param string $str
 */
function local_sql_clear_spaces($str){
    return preg_replace('/\s+/', ' ', trim($str));
}

if (\local_sql\analytics::is_enabled()){
    function local_sql_before_standard_html_head(){
        return \local_sql\analytics::apply();
    }
}

function get_users_listing_local($sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='',
                           array $extraparams=null, $extracontext = null) {
    global $DB, $CFG;

    $fullname  = $DB->sql_fullname();

    $select = "deleted <> 1 AND u.id <> :guestid";
    $params = array('guestid' => $CFG->siteguest);

    if (!empty($search)) {
        $search = trim($search);
        $select .= " AND (". $DB->sql_like($fullname, ':search1', false, false).
            " OR ". $DB->sql_like('email', ':search2', false, false).
            " OR username = :search3)";
        $params['search1'] = "%$search%";
        $params['search2'] = "%$search%";
        $params['search3'] = "$search";
    }

    if ($firstinitial) {
        $select .= " AND ". $DB->sql_like('firstname', ':fni', false, false);
        $params['fni'] = "$firstinitial%";
    }
    if ($lastinitial) {
        $select .= " AND ". $DB->sql_like('lastname', ':lni', false, false);
        $params['lni'] = "$lastinitial%";
    }

    if ($extraselect) {
        // The extra WHERE clause may refer to the 'id' column which can now be ambiguous because we
        // changed the query to include joins, so replace any 'id' that is on its own (no alias)
        // with 'u.id'.
        $extraselect = preg_replace('~([ =]|^)id([ =]|$)~', '$1u.id$2', $extraselect);
        $select .= " AND $extraselect";
        $params = $params + (array)$extraparams;
    }

    // If a context is specified, get extra user fields that the current user
    // is supposed to see, otherwise just get the name fields.
    $userfields = \core_user\fields::for_name();
    if ($extracontext) {
        $userfields->with_identity($extracontext, true);
    }

    $userfields->excluding('id');
    $userfields->including('username', 'email', 'city', 'country', 'lastaccess', 'confirmed', 'mnethostid', 'suspended', 'waitonboarding');
    ['selects' => $selects, 'joins' => $joins, 'params' => $joinparams, 'mappings' => $mappings] =
        (array)$userfields->get_sql('u', true);

    if ($sort) {
        $orderbymap = $mappings;
        $orderbymap['default'] = 'lastaccess';
        $sort = get_safe_orderby($orderbymap, $sort, $dir);
    }

    // warning: will return UNCONFIRMED USERS
    return $DB->get_records_sql("SELECT u.id $selects
                                   FROM {user} u
                                        $joins
                                  WHERE $select
                                  $sort", array_merge($params, $joinparams), $page, $recordsperpage);

}

function get_sections_by_condition($condition_key, $condition_value) {
    global $DB;

    $sections = $DB->get_records_select('course_sections', "availability IS NOT NULL AND availability <> ''");
    $matching_sections = [];

    foreach ($sections as $section) {
        $availability = json_decode($section->availability, true);

        if (isset($availability['c'])) {
            foreach ($availability['c'] as $condition) {
                if (isset($condition['type']) && $condition['type'] === $condition_key) {
                    if (isset($condition['access_must']) && $condition['access_must'] == $condition_value) {
                        $matching_sections[] = $section;
                        break;
                    }
                }
            }
        }
    }

    return $matching_sections;
}

function json_die($data, $status_code) {
    ob_clean();
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    die();
}

function security_gen_str($length): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $randomIndex = random_int(0, $charactersLength - 1);
        $token .= $characters[$randomIndex];
    }
    return $token;
}
function get_actual_email($user) {
    global $DB;

    if (empty($user))
        return null;

    $sql = "SELECT * FROM {".sql_database::TABLE_PAYPAL."}
                WHERE userid = :userid
                ORDER BY timecreated DESC
                LIMIT 1";

    $record = $DB->get_record_sql($sql, ['userid' => $user->id]);

    if (empty($record))
        return null;

    return $record->email;
}