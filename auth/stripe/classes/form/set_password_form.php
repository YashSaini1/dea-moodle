<?php

namespace auth_stripe\form;
use auth_stripe\core;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/login/lib.php');

/**
 * Set forgotten password form definition.
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_password_form extends \moodleform {

    /**
     * Define the set password form.
     */
    public function definition(){
        $mform = $this->_form;
        $user = $this->_customdata;

        $mform->setDisableShortforms(true);

        // Include the username in the form so browsers will recognise that a password is being set.
        // Token gives authority to change password.
        $mform->addElement('hidden', 'token', '');
        $mform->setType('token', PARAM_ALPHANUM);

        // Visible elements.
        if (empty($user->type)){
            $setup_pass = core::str('setup_password_text');
            $mform->addElement('static', 'title', '', '<div class="box-title"><H3>'.$setup_pass.'</H3></div>', '');
            $mform->addElement('html', '<div class="input-field-wrapper">');

            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="input-field-wrapper">');
            $field_name = core::str('password_field');
            $mform->addElement('password', 'newpassword', $field_name, ['autocomplete' => 'new-password', 'placeholder' => $field_name]);
            $mform->setType('password', PARAM_RAW);
            $mform->addElement('html', '<div class="password-eye"></div>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="input-field-wrapper">');
            $field_name = core::str('confirm_new_password_text');
            $mform->addElement('password', 'confirmpassword', $field_name, ['autocomplete' => 'new-password', 'placeholder' => $field_name]);
            $mform->setType('confirmpassword', PARAM_RAW);
            $mform->addElement('html', '<div class="password-eye"></div>');
            $mform->addElement('html', '</div>');

            // Hook for plugins to extend form definition.

            core_login_extend_set_password_form($mform, $user);

            $this->add_action_buttons(false, core::str('setup_password_text'));
            return;
        }

        $setup_title = core::str('setup_password_text');
        $mform->addElement('static', 'title', '', '<div class="box-title"><H3>'.$setup_title.'</H3></div>', '');

        $email = core::str('email_field');
        $mform->addElement('static', 'email', $email, $user->email);

        $mform->setType('email', PARAM_EMAIL);

        $field_name = core::str('new_password_text');
        $mform->addElement('html', '<div class="input-field-wrapper">');
        $mform->addElement('password', 'newpassword', $field_name, ['autocomplete' => 'new-password', 'placeholder' => $field_name]);
        $mform->setType('password', PARAM_RAW);

        $mform->addElement('html', '<div class="password-eye"></div>');
        $mform->addElement('html', '</div>');

        $field_name = core::str('confirm_new_password_text');
        $mform->addElement('html', '<div class="input-field-wrapper">');
        $mform->addElement('password', 'confirmpassword', $field_name, ['autocomplete' => 'new-password', 'placeholder' => $field_name]);
        $mform->setType('confirmpassword', PARAM_RAW);

        $mform->addElement('html', '<div class="password-eye"></div>');
        $mform->addElement('html', '</div>');

        // Hook for plugins to extend form definition.

        core_login_extend_set_password_form($mform, $user);

        $this->add_action_buttons(false, core::str('save_new_password_text'));
    }

    /**
     * Perform extra password change validation.
     *
     * @param array $data  submitted form fields.
     * @param array $files submitted with the form.
     *
     * @return array errors occuring during validation.
     */
    public function validation($data, $files){
        $user = $this->_customdata;

        $errors = parent::validation($data, $files);

        // Extend validation for any form extensions from plugins.
        $errors = array_merge($errors, core_login_validate_extend_set_password_form($data, $user));

        // Ignore submitted username.
        if ($data['newpassword'] !== $data['confirmpassword']){
            $errors['confirmpassword'] = get_string('passwordsdiffer');
            return $errors;
        }

        $errmsg = ''; // Prevents eclipse warnings.
        if (!check_password_policy($data['newpassword'], $errmsg, $user)){
            $errors['newpassword'] = $errmsg;
            return $errors;
        }

        if (user_is_previously_used_password($user->id, $data['newpassword'])){
            $errors['newpassword'] = get_string('errorpasswordreused', 'core_auth');
        }

        return $errors;
    }
}