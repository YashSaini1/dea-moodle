<?php

/**
 * Forgot password page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  2006 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once('lib.php');

/**
 * Reset forgotten password form definition.
 *
 * @package    core
 * @subpackage auth
 * @copyright  2006 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_login_forgot_password_form extends moodleform {

    /**
     * Define the forgot password form.
     */
    function definition(){
        global $USER, $CFG;
        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        // Hook for plugins to extend form definition.
        core_login_extend_forgot_password_form($mform);
        $mform->addElement('static', 'title', '', '<div class="box-title"><H3>'.get_string('forgot_title_text', 'theme_sql').'</H3></div>', '');
        $mform->addElement('static', 'instructions', '', get_string('forgot_instructions', 'theme_sql'));

        $purpose = user_edit_map_field_purpose($USER->id, 'email');
        $mform->addElement('text', 'email', 'email', 'maxlength="100" size="30" id="email"placeholder="'.
            get_string('page_sign_up_email', 'theme_sql').'"'.$purpose);
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $submitlabel = get_string('forgot_submit_btn', 'theme_sql');
        $mform->addElement('submit', 'submitbuttonemail', $submitlabel, 'id="id_submitbutton"');
    }

    /**
     * Validate user input from the forgot password form.
     *
     * @param array $data  array of submitted form fields.
     * @param array $files submitted with the form.
     *
     * @return array errors occuring during validation.
     */
    function validation($data, $files){
        $errors = parent::validation($data, $files);

        // Extend validation for any form extensions from plugins.
        $errors = array_merge($errors, core_login_validate_extend_forgot_password_form($data));
        $errors += core_login_validate_forgot_password_data($data);

        return $errors;
    }
}
