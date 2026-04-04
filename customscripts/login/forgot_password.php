<?php

/**
 * Forgot password routine.
 *
 * Finds the user and calls the appropriate routine for their authentication type.
 *
 * There are several pathways to/through this page, summarised below:
 * 1. User clicks the 'forgotten your username or password?' link on the login page.
 *  - No token is received, render the username/email search form.
 * 2. User clicks the link in the forgot password email
 *  - Token received as GET param, store the token in session, redirect to self
 * 3. Redirected from (2)
 *  - Fetch token from session, and continue to run the reset routine defined in 'core_login_process_password_set()'.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');
require_once($CFG->dirroot . '/auth/stripe/locallib.php');
require_once($CFG->customscripts . '/login/forgot_password_form.php');

$token = optional_param('token', false, PARAM_ALPHANUM);
$type = optional_param('type', 1, PARAM_INT);

$PAGE->set_url('/login/forgot_password.php');
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('login');

// setup text strings
$strforgotten = get_string('passwordforgotten');
$strlogin = get_string('login');

$PAGE->set_title($strforgotten);

// if alternatepasswordurl is defined, then we'll just head there
if(!empty($CFG->forgottenpasswordurl)){
    redirect($CFG->forgottenpasswordurl);
}

// if you are logged in then you shouldn't be here!
if(isloggedin() and !isguestuser()){
    redirect($CFG->wwwroot . '/index.php', get_string('loginalready'), 5);
}

// Fetch the token from the session, if present, and unset the session var immediately.
$tokeninsession = false;
if(!empty($SESSION->password_reset_token)){
    $token = $SESSION->password_reset_token;
    unset($SESSION->password_reset_token);
    $tokeninsession = true;
}

if(empty($token)){
    // This is a new password reset request.
    // Process the request; identify the user & send confirmation email.
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('theme_sql/auth/forgot_password',
        array('form'              => stripe_login_process_password_reset_request(),
              'logo-white-imgsrc' => '/theme/sql/pix/sql-logo-white.svg',
              'logo-imgsrc'       => '/theme/sql/pix/sql-logo.svg',
              'signinimgsrc'     => '/theme/sql/pix/auth/preview-sign-in.png',
              'signin_logo'     => '/theme/sql/pix/logo.svg',
              'signinurl'        => get_login_url()
        ));
} else{
    // A token has been found, but not in the session, and not from a form post.
    // This must be the user following the original rest link, so store the reset token in the session and redirect to self.
    // The session var is intentionally used only during the lifespan of one request (the redirect) and is unset above.
    if(!$tokeninsession && $_SERVER['REQUEST_METHOD'] === 'GET'){
        $SESSION->password_reset_token = $token;
        $SESSION->type = $type;
        redirect($CFG->wwwroot . '/login/forgot_password.php');
    } else{
        // Continue with the password reset process.
        stripe_login_process_password_set_custom($token, $SESSION->type);
    }
}
echo $OUTPUT->footer();
die();