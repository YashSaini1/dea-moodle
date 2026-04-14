<?php

namespace auth_stripe\form;

use auth_stripe\core;
use auth_stripe\util\PhoneNumber;
use html_writer;
use moodle_url;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/auth/stripe/locallib.php');


/**
 * User sign-up form.
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup_form extends \moodleform implements \renderable, \templatable
{

    function definition()
    {
        $email = optional_param('email', '', PARAM_EMAIL);
        $string_req = core::str('error_required', 'auth_stripe');

        $mform = $this->_form;
        $customdata = (array)$this->_customdata;

        $mform->addElement('hidden', 'tier', 0);
        $mform->setType('tier', PARAM_INT);

        $mform->addElement('hidden', 'url-is-busy-field');
        $mform->setType('url-is-busy-field', PARAM_TEXT);
        $mform->setDefault('url-is-busy-field', new moodle_url('/auth/stripe/isusefield.php'));

        $mform->addElement('html', '<div class="sign-up-form-wrapper">');

        $mform->addElement('html', '<h1 class="sign-up-title">' . core::str("sign_up_title") . '</h1>');

        $mform->addElement('html', '    <div class="trustpilot-widget" style="margin: 20px 0;position: relative;display: flex;left: -67px;width: 100%;" data-locale="en-US" data-template-id="5419b732fbfb950b10de65e5" data-businessunit-id="65ef970d494d641f72b34141" data-style-height="24px" data-style-width="89%" data-theme="white">
            <iframe title="Customer reviews powered by Trustpilot" loading="auto" src="https://widget.trustpilot.com/trustboxes/5419b732fbfb950b10de65e5/index.html?templateId=5419b732fbfb950b10de65e5&amp;businessunitId=65ef970d494d641f72b34141#locale=en-US&amp;styleHeight=24px&amp;styleWidth=100%25&amp;theme=dark" style="position: relative; height: 24px; width: 100%; border-style: none; display: block; overflow: hidden;"></iframe>
        </div>');

        $mform->addElement('html', '<script type="text/javascript" src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js" async></script>');


        $mform->addElement('html', '<div class="sign-up-name-container">');

        $mform->addElement(
            'text',
            'fullname',
            core::str('full_name'),
            'maxlength="200" size="30" autocapitalize="none" placeholder="' . core::str('full_name_placeholder') . '" data-error="' . $string_req . '"'
        );
        $mform->setType('fullname', PARAM_RAW);
        $mform->addRule('fullname', null, 'required', null, 'client');

        $mform->addRule('fullname', core::str('please_enter_full_name'), 'callback', [$this, 'custom_validation'], 'server');


        /*        $name = core::str('lastname_field');
                $mform->addElement('text', 'lastname', $name,
                    'maxlength="100" size="30" autocapitalize="none" placeholder="'.$name.'" data-error="'.$string_req.'"');
                $mform->setType('lastname', PARAM_RAW);
                $mform->addRule('lastname', null, 'required', null, 'client');*/

        // Input field
        $name = core::str('phone_field');
        $placeholder = core::str('phone_field_placeholder');
        $mform->addElement(
            'text',
            'phone',
            $name,
            'id="phone" type="tel" maxlength="20" size="30" data-error="' . $string_req . '"'
        );
        $mform->setType('phone', \core_user::get_property_type('phone1'));
        $mform->addRule('phone', null, 'required', null, 'client');
        $mform->addHelpButton('phone', 'phone_field', 'auth_stripe');

        // Data field. Phone1 will contains phone data with code
        \auth_stripe\util\PhoneNumber::init_for_form($mform, 'phone', 'phone1');
        $mform->addElement('html', '</div>');

        $name = core::str('email_field');
        $mform->addElement(
            'text',
            'email',
            $name,
            'id="email" maxlength="100" size="30" placeholder="' . $name . '" data-error="' . $string_req . '"'
        );
        $mform->setType('email', \core_user::get_property_type('email'));
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', null, 'email', null, 'client');

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', '');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        profile_signup_fields($mform);

        // Hook for plugins to extend form definition.
        core_login_extend_signup_form($mform);

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // Ensure action button is outside the last collapsible profile/category section.
        $mform->addElement('static', 'submitactionsanchor', '', '');
        $mform->closeHeaderBefore('submitactionsanchor');

        $mform->addElement('html', '
        <div class="block-actions">
	    <button id="get-started" type="submit" class="btn btn-primary">
	        ' . core::str("sign_up_button_title") . '
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
	            <path d="M5 13L10 8L5 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
	        </svg>
	    </button>
	    </div>');

        $mform->addElement('html', '</div>');

        //        $mform->setForceLtr('email', PARAM_RAW);
        //        $mform->setDefault('email', $email);
    }


    function definition_after_data()
    {
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * @throws \coding_exception
     */
    function custom_validation($value): bool
    {
        return is_array(validate_fullname($value));
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data  array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     *
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files)
    {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $errors = array_merge($errors, core_login_validate_extend_signup_form($data));
        $required_str = core::str('error_required');

        if (signup_captcha_enabled()) {
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (!$recaptchaelement->verify($response)) {
                    $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                }
            } else {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            }
        }

        if (empty($data['fullname'])) {
            $errors['fullname'] = $required_str;
        }

        // Validate hidden 'phone1' field. but show error to the input 'phone' field 
        if (empty($data['phone1'])) {
            $errors['phone'] = $required_str;
        } elseif ($validation_error = PhoneNumber::validate($data['phone1'])) {
            $errors['phone'] = $validation_error;
        }
        //        if (empty($data['username'])){
        //            $errors['username'] = core::str('error_required');
        //        } elseif ($DB->record_exists('user', array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
        //            $errors['username'] = get_string('usernameexists');
        //        }

        if (empty($data['email'])) {
            $errors['email'] = $required_str;
        } elseif (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        } elseif (empty($CFG->allowaccountssameemail)) {
            // Emails in Moodle as case-insensitive and accents-sensitive. Such a combination can lead to very slow queries
            // on some DBs such as MySQL. So we first get the list of candidate users in a subselect via more effective
            // accent-insensitive query that can make use of the index and only then we search within that limited subset.
            $sql = "SELECT 'x'
                  FROM {user}
                 WHERE " . $DB->sql_equal('email', ':email1', false, true) . "
                   AND id IN (SELECT id
                                FROM {user}
                               WHERE " . $DB->sql_equal('email', ':email2', false, false) . "
                                 AND mnethostid = :mnethostid)";

            $params = array(
                'email1'     => $data['email'],
                'email2'     => $data['email'],
                'mnethostid' => $CFG->mnet_localhost_id,
            );

            // If there are other user(s) that already have the same email, show an error.
            if ($DB->record_exists_sql($sql, $params)) {
                $forgotpasswordurl = new moodle_url('/login/forgot_password.php');
                $forgotpasswordlink = html_writer::link($forgotpasswordurl, get_string('emailexistshintlink'));
                $errors['email'] = get_string('emailexists') . ' ' . get_string('emailexistssignuphint', 'moodle', $forgotpasswordlink);
            }
        }

        if (!isset($errors['email']) && $err = email_is_not_allowed($data['email'])) {
            $errors['email'] = $err;
        }

        return $errors;
    }

    /**
     * Connect phone field js here because we need to save submitted value (if validation failed)
     */
    function display()
    {
        $phone_value = $this->_form->getElementValue('phone1');
        PhoneNumber::render_for_form($this->_form, $phone_value);
        parent::display();
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     *
     * @return array
     */
    public function export_for_template(renderer_base $output)
    {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'formhtml' => $formhtml,
        ];
        return $context;
    }
}
