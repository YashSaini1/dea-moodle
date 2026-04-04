<?php

/**
 * Authentication Plugin: Stripe Authentication
 *
 * @copyright   2021 Kirill Slyusar
 * @package     auth_stripe
 */

use auth_stripe\core;
use auth_stripe\model\customer;
use auth_stripe\util\PhoneNumber;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Email authentication plugin.
 */
class auth_plugin_stripe extends auth_plugin_base
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->authtype = 'stripe';
        $this->config = get_config('auth_stripe');
    }

    /**
     * Validates the standard sign-up data (except recaptcha that is validated by the form element).
     *
     * @param  array $data  the sign-up data
     * @param  array $files files among the data
     * @return array list of errors, being the key the data element name and the value the error itself
     * @since Moodle 3.2
     */
    function signup_validate_data($data, $files)
    {
        global $CFG, $DB;

        $errors = array();
        $authplugin = get_auth_plugin($CFG->registerauth);

        if ($DB->record_exists('user', array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
            $errors['username'] = get_string('usernameexists');
        } else {
            // Check allowed characters.
            if ($data['username'] !== core_text::strtolower($data['username'])) {
                $errors['username'] = get_string('usernamelowercase');
            } else {
                if ($data['username'] !== core_user::clean_field($data['username'], 'username')) {
                    $errors['username'] = get_string('invalidusername');
                }
            }
        }


        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        } else if (empty($CFG->allowaccountssameemail)) {
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
                'email1' => $data['email'],
                'email2' => $data['email'],
                'mnethostid' => $CFG->mnet_localhost_id,
            );

            // If there are other user(s) that already have the same email, show an error.
            if ($DB->record_exists_sql($sql, $params)) {
                $forgotpasswordurl = new moodle_url('/login/forgot_password.php');
                $forgotpasswordlink = html_writer::link($forgotpasswordurl, get_string('emailexistshintlink'));
                $errors['email'] = get_string('emailexists') . ' ' . get_string('emailexistssignuphint', 'moodle', $forgotpasswordlink);
            }
        }
        if (empty($data['email2'])) {
            $errors['email2'] = get_string('missingemail');
        } else if (core_text::strtolower($data['email2']) != core_text::strtolower($data['email'])) {
            $errors['email2'] = get_string('invalidemail');
        }
        if (!isset($errors['email'])) {
            if ($err = email_is_not_allowed($data['email'])) {
                $errors['email'] = $err;
            }
        }

        // Construct fake user object to check password policy against required information.
        $tempuser = new stdClass();
        $tempuser->id = 1;
        $tempuser->username = $data['username'];
        $tempuser->firstname = $data['firstname'];
        $tempuser->lastname = $data['lastname'];
        $tempuser->email = $data['email'];

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg, $tempuser)) {
            $errors['password'] = $errmsg;
        }

        // Validate customisable profile fields. (profile_validation expects an object as the parameter with userid set).
        $dataobject = (object)$data;
        $dataobject->id = 0;
        $errors += profile_validation($dataobject, $files);

        return $errors;
    }


    function signup_form($customdata = null){
        return new \auth_stripe\form\signup_form(
            null,
            $customdata,
            'post',
            '',
            array('autocomplete' => 'on')
        );
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_email()
    {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    function user_confirm($username, $confirmsecret)
    {
        global $DB, $SESSION;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->auth != $this->authtype) {
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret == $confirmsecret && $user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));

                if ($wantsurl = get_user_preferences('auth_stripe_wantsurl', false, $user)) {
                    // Ensure user gets returned to page they were trying to access before signing up.
                    $SESSION->wantsurl = $wantsurl;
                    unset_user_preference('auth_stripe_wantsurl', $user);
                }

                return AUTH_CONFIRM_OK;
            }
        } else {
            return AUTH_CONFIRM_ERROR;
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/auth/stripe/locallib.php');

        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    function user_update($olduser, $newuser){
        $newuser->phone1 = PhoneNumber::parse($newuser->phone1);
        return parent::user_update($olduser, $newuser);
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword)
    {
        $user = get_complete_user_data('id', $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    function can_signup()
    {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify = true)
    {
        // Standard signup, without custom confirmatinurl.
        return $this->user_signup_with_confirmation($user, $notify);
    }

    function user_authenticated_hook(&$user, $username, $password){
        parent::user_authenticated_hook($user, $username, $password);
        $user = \auth_stripe\subscription\tier_processor::init_user_tiers($user, false, true);
    }

    /**
     * @param $user
     */
    protected function _create_payment_step_form($user){
        global $PAGE,$DB;

        $user->step = 2;
        $mform = $this->signup_form($user);
        $mform->set_data($user);

        $params = [];
        $params['loginurl'] = get_login_url();
        $params['publish_key'] = $this->config->public_key;
        $params['fail_str'] = core::str('createaccountfail');
        $params['have_sub_str'] = core::str('alreadyhavesub');
        $params['already_logged_str'] = core::str('alreadylogged');
        $params['many_request'] = core::str('manyrequest');
        $params['useremail'] = $user->email;

        $PAGE->requires->js_call_amd('auth_stripe/registerForm', 'init', array($params));

        $PAGE->set_pagelayout('login');
        $output = $PAGE->get_renderer('auth_' . $this->authtype);


        $stripeform_context = [
            'requireagreements' => true,
            'new_user' => empty($user->id)
        ];

        // Get signup moodle form
        $formhtml = '';//$mform->export_for_template($output)['formhtml'];
        // Get stripe payment form and add it before moodle form
        $formhtml = $output->render_from_template('auth_stripe/payment_stripe_form', $stripeform_context);

        echo $output->header();
        echo $output->render_signup_form($formhtml);
        echo $output->footer();
        exit;
    }

    /**
     * Sign up a new user ready for confirmation.
     *
     * Password is passed in plaintext.
     * A custom confirmationurl could be used.
     *
     * @param object  $user            new user object
     * @param boolean $notify          print notice with link and terminate
     * @param string  $confirmationurl user confirmation URL
     *
     * @return boolean true if everything well ok and $notify is set to true
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    public function user_signup_with_confirmation($user, $notify = true, $confirmationurl = null){
        global $CFG;
        require_once($CFG->dirroot.'/auth/stripe/lib.php');

        auth_stripe_create_student($user);

        $this->redirect_to_change_password_page($user);
        return true;

        // Use local payment page instead
        // $this->redirect_to_remote_stripe_payment_page($user);
        // return true;
    }

    public function redirect_to_change_password_page($user) {
        $resetrecord = core_login_generate_password_reset($user);
        redirect(new moodle_url('/login/forgot_password.php', ['token' => $resetrecord->token, 'type' => 0]));
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    function can_confirm()
    {
        return true;
    }

    function prevent_local_passwords()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal()
    {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password()
    {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url()
    {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password()
    {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set()
    {
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled.
     * @return bool
     */
    function is_captcha_enabled()
    {
        return get_config("auth_{$this->authtype}", 'recaptcha');
    }

    /**
     * @return bool|string
     * @throws moodle_exception
     */
    function render_payment_stripe_form($coupon_allowed = false, $applied_coupon = null){
        global $USER, $PAGE;
        $output = $PAGE->get_renderer('auth_'.$this->authtype);
        $params = [
            'loginurl'           => get_login_url(),
            'publish_key'        => $this->config->public_key,
            'fail_str'           => core::str('createaccountfail'),
            'have_sub_str'       => core::str('alreadyhavesub'),
            'already_logged_str' => core::str('alreadylogged'),
            'many_request'       => core::str('manyrequest'),
            'coupon:apply'       => core::str('coupon:apply'),
            'coupon:remove'      => core::str('coupon:remove'),
            'useremail'          => $USER->email ?? null,
            'coupon_allowed'     => $coupon_allowed,
        ];

        if (!empty($applied_coupon)){
            $params['applied_coupon'] = $applied_coupon;
        }

        $PAGE->requires->js_call_amd('auth_stripe/registerForm', 'init', array($params));

        $PAGE->set_pagelayout('login');

        $stripeform_context = [
            'requireagreements' => true,
            'new_user'          => empty($USER->id),
            'email'             => $USER->email ?? null,
            'coupon_allowed'    => $coupon_allowed,
        ];

        return $output->render_from_template('auth_stripe/payment_stripe_form', $stripeform_context);
    }
}
