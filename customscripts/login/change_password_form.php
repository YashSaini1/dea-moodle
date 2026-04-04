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
 * Change password form definition.
 *
 * @package    core
 * @subpackage auth
 * @copyright  2006 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once('lib.php'); // login/lib.php

class login_change_password_form extends moodleform {

    function definition(){
        global $USER, $CFG, $PAGE;
        $PAGE->requires->js_call_amd('auth_stripe/setpasswordform', 'init');

        $mform = $this->_form;
        $mform->setDisableShortforms();

        $mform->addElement('header', 'changepassword', get_string('changepassword'), '');

        // visible elements
        $mform->addElement('static', 'email', get_string('email_field', 'theme_sql'), $USER->email);

        $policies = array();
        if (!empty($CFG->passwordpolicy)){
            $policies[] = print_password_policy();
        }
        if (!empty($CFG->passwordreuselimit) and $CFG->passwordreuselimit > 0){
            $policies[] = get_string('informminpasswordreuselimit', 'auth', $CFG->passwordreuselimit);
        }
        $policies = false;
        if ($policies){
            $mform->addElement('static', 'passwordpolicyinfo', '', implode('<br />', $policies));
        }
        $purpose = user_edit_map_field_purpose($USER->id, 'password');

        $mform->addElement('html', '<div class="input-field-wrapper">');

        $mform->addElement('password', 'password', get_string('oldpassword'));
        $mform->addRule('password', null, 'required', null, 'client');
        $mform->setType('password', PARAM_RAW);

        $mform->addElement('html', '<div class="password-eye"></div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="input-field-wrapper">');

        $mform->addElement('password', 'newpassword1', get_string('newpassword'));
        $mform->addRule('newpassword1', null, 'required', null, 'client');
        $mform->setType('newpassword1', PARAM_RAW);

        $mform->addElement('html', '<div class="password-eye"></div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="input-field-wrapper">');

        $mform->addElement('password', 'newpassword2', get_string('confirm_new_password', 'theme_sql'));
        $mform->addRule('newpassword2', null, 'required', null, 'client');
        $mform->setType('newpassword2', PARAM_RAW);

        $mform->addElement('html', '<div class="password-eye"></div>');
        $mform->addElement('html', '</div>');

        if (empty($CFG->passwordchangetokendeletion)){
            $mform->addElement('advcheckbox', 'signoutofotherservices', get_string('signoutofotherservices'));
            $mform->addHelpButton('signoutofotherservices', 'signoutofotherservices');
            $mform->setDefault('signoutofotherservices', 1);
        }

        // hidden optional params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Hook for plugins to extend form definition.
        core_login_extend_change_password_form($mform, $USER);

        // buttons
        if (get_user_preferences('auth_forcepasswordchange')){
            $this->add_action_buttons(false);
        } else {
            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('update'));

            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }
    }

    /// perform extra password change validation
    function validation($data, $files){
        global $USER;
        $errors = parent::validation($data, $files);
        $reason = null;

        // Extend validation for any form extensions from plugins.
        $errors = array_merge($errors, core_login_validate_extend_change_password_form($data, $USER));

        // ignore submitted username
        if (!$user = authenticate_user_login($USER->username, $data['password'], true, $reason, false)){
            // $errors['password'] = get_string('invalidlogin');
            // Only logged in users get to this page, which means that the problem will be only with the password
            $errors['password'] = get_string('you_current_password_incorrect','theme_sql');
            return $errors;
        }

        if ($data['newpassword1'] <> $data['newpassword2']){
            $msg = get_string('passwordsdiffer');
            $errors['newpassword1'] = $errors['newpassword2'] = $msg;
            return $errors;
        }

        if ($data['password'] == $data['newpassword1']){
            $msg = get_string('mustchangepassword');
            $errors['newpassword1'] = $errors['newpassword2'] = $msg;
            return $errors;
        }

        if (user_is_previously_used_password($USER->id, $data['newpassword1'])){
            $msg = get_string('errorpasswordreused', 'core_auth');
            $errors['newpassword1'] = $errors['newpassword2'] = $msg;
        }

        $errmsg = '';//prevents eclipse warnings
        if (!check_password_policy($data['newpassword1'], $errmsg, $USER)){
            $errors['newpassword1'] = $errors['newpassword2'] = $errmsg;
        }

        return $errors;
    }
}
