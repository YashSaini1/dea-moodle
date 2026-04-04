<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Change password page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/webservice/lib.php');
require_once($CFG->dirroot.'/login/lib.php');
require_once($CFG->customscripts.'/login/change_password_form.php');
require_once($CFG->dirroot.'/auth/stripe/locallib.php');

$id = optional_param('id', SITEID, PARAM_INT); // current course
$return = optional_param('return', 0, PARAM_BOOL); // redirect after password change
$forcereturn = optional_param('forcereturn', 0, PARAM_BOOL); // redirect after password change

// remove sidebar on change password page
$id = SITEID;

$systemcontext = context_system::instance();

$PAGE->set_url('/login/change_password.php', array('id' => $id));

$PAGE->set_context($systemcontext);

if ($return){
    // this redirect prevents security warning because https can not POST to http pages
    if (
        empty($SESSION->wantsurl)
        or stripos(str_replace('https://', 'http://', $SESSION->wantsurl), str_replace('https://', 'http://', $CFG->wwwroot . '/login/change_password.php')) === 0
    ) {
        $returnto = "$CFG->wwwroot/user/preferences.php?userid=$USER->id&course=$id";
    } else {
        $returnto = $SESSION->wantsurl;
    }

    if (!empty($forcereturn)){
        $returnto = new moodle_url("/login/logout.php", array('sesskey' => sesskey()));
    } else {
        $returnto = new moodle_url("/user/profile.php", array('id' => $USER->id));
    }

    unset($SESSION->wantsurl);
    redirect($returnto);
}

$strparticipants = get_string('participants');

if (!$course = $DB->get_record('course', array('id' => $id))){
    throw new \moodle_exception('invalidcourseid');
}

// require proper login; guest user can not change password
if (!isloggedin() or isguestuser()){
    if (empty($SESSION->wantsurl)){
        $SESSION->wantsurl = $CFG->wwwroot.'/login/change_password.php';
    }
    redirect(get_login_url());
}

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_course($course);

$force_password = get_user_preferences('auth_forcepasswordchange', false);
if ($force_password){
    if (empty($SESSION->updated) && ($USER->firstname == $USER->lastname && $USER->lastname == $USER->email)){
        redirect('/user/edit.php');
    } elseif (empty($USER->phone1)) {
        $SESSION->profile_redirect_field = 'phone1';
        redirect('/user/edit.php');
    }
}

// do not require change own password cap if change forced
if (!$force_password){
    require_capability('moodle/user:changeownpassword', $systemcontext);
}

// do not allow "Logged in as" users to change any passwords
if (\core\session\manager::is_loggedinas()){
    throw new \moodle_exception('cannotcallscript');
}

if (is_mnet_remote_user($USER)){
    $message = get_string('usercannotchangepassword', 'mnet');
    if ($idprovider = $DB->get_record('mnet_host', array('id' => $USER->mnethostid))){
        $message .= get_string('userchangepasswordlink', 'mnet', $idprovider);
    }
    throw new \moodle_exception('userchangepasswordlink', 'mnet', '', $message);
}

// load the appropriate auth plugin
$userauth = get_auth_plugin($USER->auth);

if (!$userauth->can_change_password()){
    throw new \moodle_exception('nopasswordchange', 'auth');
}

if ($changeurl = $userauth->change_password_url()){
    // this internal scrip not used
    redirect($changeurl);
}
$fullname = fullname($USER, true);

$mform = new login_change_password_form();
$mform->set_data(array('id' => $course->id));

$navlinks = array();
$navlinks[] = array('name' => $strparticipants, 'link' => "$CFG->wwwroot/user/index.php?id=$course->id", 'type' => 'misc');

if ($mform->is_cancelled()){
    redirect(new moodle_url("/user/profile.php", array('id' => $USER->id)));
} elseif ($data = $mform->get_data()){
    if (!$userauth->user_update_password($USER, $data->newpassword1)){
        throw new moodle_exception('errorpasswordupdate', 'auth');
    }

    if (property_exists($SESSION, 'updated')){
        unset($SESSION->updated);
    }
    user_add_password_history($USER->id, $data->newpassword1);
    $return_url = new moodle_url($PAGE->url, array('return' => 1));

    // Reset login lockout - we want to prevent any accidental confusion here.
    login_unlock_account($USER);

    // register success changing password
    unset_user_preference('auth_forcepasswordchange', $USER);
    unset_user_preference('create_password', $USER);

    $strpasswordchanged = get_string('passwordchanged');

    // Plugins can perform post password change actions once data has been validated.
    core_login_post_change_password_requests($data);

    if (!empty($data->signoutofotherservices)){
        $return_url->param('forcereturn', 1);
        \core\session\manager::kill_user_sessions($USER->id, session_id());
        webservice::delete_user_ws_tokens($USER->id);
        redirect(new moodle_url('/login/logout.php', array('sesskey' => session_id())));
    }

    $PAGE->set_title($strpasswordchanged);
    $PAGE->set_heading($fullname);
    echo $OUTPUT->header();
    echo stripe_notice(get_string('passwordchanged') . '!', ($CFG->wwwroot.'/user/profile.php?id='.$USER->id), get_string('continue'), '', true);
    echo $OUTPUT->footer();
    die; // Never reached.
}

$strchangepassword = get_string('changepassword');
if ($force_password){
    \core\notification::error(get_string('forcepasswordchangenotice'));
}
$PAGE->set_title($strchangepassword);
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
die;