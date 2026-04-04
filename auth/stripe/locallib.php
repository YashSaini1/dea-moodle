<?php

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\core\stripe_database;
use core_badges\oauth2\auth;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/auth/stripe/lib.php');

function stripe_login_process_password_reset_request(){
    $mform = new custom_login_forgot_password_form();

    if ($mform->is_cancelled()){
        redirect(get_login_url());
    } elseif ($data = $mform->get_data()) {
        $username = $email = '';
        if (!empty($data->username)){
            $username = $data->username;
        } else {
            $email = $data->email;
        }

        $user = core_user::get_user_by_email($email);

        $is_oauth2_user = (isset($user->auth) && $user->auth == 'oauth2');

        $notice_params = [
            'usermail'  => $email,
            'adminmail' => core::get_support_mail(),
        ];

        if ($is_oauth2_user) {
            sso_notification($user, core_user::get_support_user());
        } else {
            list($status, $notice, $url) = core_login_process_password_reset($username, $email);
            core_login_post_forgot_password_requests($data);
        }

        $notice = core::str('forgot_check_email', $notice_params);
        $title = core::str('forgot_instructions_sent');
        $link_text = core::str('go_to_login_page');
        $url = new moodle_url('/login/index.php');

        return stripe_notice($notice, $url, $link_text, $title, true);
    }

    // DISPLAY FORM.
    return $mform->render();
}

function sso_notification($user, $support) {
    $auth_instance = new auth();
    $info = $auth_instance->get_password_change_info($user);
    return email_to_user($user, $support, $info['subject'], $info['message']);
}

function stripe_login_process_password_set_custom($token, $type){
    global $DB, $CFG, $OUTPUT, $PAGE, $SESSION;
    require_once($CFG->dirroot.'/user/lib.php');

    $pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;
    $sql = "SELECT u.*, upr.token, upr.timerequested, upr.id AS tokenid
              FROM {user} u
              JOIN {user_password_resets} upr ON upr.userid = u.id
             WHERE upr.token = ?";
    $user = $DB->get_record_sql($sql, array($token));

    $forgotpasswordurl = "{$CFG->wwwroot}/login/forgot_password.php";
    if (empty($user) or ($user->timerequested < (time() - $pwresettime - DAYSECS))){
        // There is no valid reset request record - not even a recently expired one.
        // (suspicious)
        // Direct the user to the forgot password page to request a password reset.
        echo $OUTPUT->header();
        echo stripe_notice(
            get_string('noresetrecord'),
            $forgotpasswordurl,
            get_string('continue'),
            null,
            false
        );
        echo $OUTPUT->footer();
        die; // Never reached.
    }
    if ($user->timerequested < (time() - $pwresettime)){
        // There is a reset record, but it's expired.
        // Direct the user to the forgot password page to request a password reset.
        $date = \local_sql\util\DateFormatter::format_time($pwresettime, false);
        echo stripe_notice(
            get_string('resetrecordexpired', 'auth_stripe', $date),
            $forgotpasswordurl,
            core::str('try_again'),
            core::str('reset_password_expired'),
            false
        );
        die; // Never reached.
    }

    if ($user->auth === 'nologin' or !is_enabled_auth($user->auth)){
        // Bad luck - user is not able to login, do not let them set password.
        echo $OUTPUT->header();
        throw new \moodle_exception('forgotteninvalidurl');
    }

    // Check this isn't guest user.
    if (isguestuser($user)){
        throw new \moodle_exception('cannotresetguestpwd');
    }

    // type == 0 - registration_process
    // type == 1 - reset_password_process
    $user->type = $type;
    $mform = new \auth_stripe\form\set_password_form(null, $user);

    // Token is correct, and unexpired.
    $data = $mform->get_data();
    if (empty($data)){
        $PAGE->set_title(core::str('setup_password_text'));
        // User hasn't submitted form, they got here directly from email link.
        // Next, display the form.
        $setdata = [
            'email'     => $user->email,
            'username'  => $user->username,
            'username2' => $user->username,
            'token'     => $user->token,
        ];
        $mform->set_data($setdata);

        $logoimg = '';
        if ($logourl = $OUTPUT->get_logo_url()){
            $logoimg = $logourl->out(false);
        }

        $template_context = [
            'form'        => $mform->render(),
            'logo-imgsrc' => $logoimg,
        ];

        $PAGE->requires->js_call_amd('auth_stripe/setpasswordform', 'init');

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('auth_stripe/setuppassword', $template_context);
        echo $OUTPUT->footer();
        die();
    }

    // User has submitted form.
    // Delete this token so it can't be used again.
    $DB->delete_records('user_password_resets', array('id' => $user->tokenid));
    $userauth = get_auth_plugin($user->auth);
    if (!$userauth->user_update_password($user, $data->newpassword)){
        throw new \moodle_exception('errorpasswordupdate', 'auth');
    }

    user_add_password_history($user->id, $data->newpassword);
    if (!empty($CFG->passwordchangelogout)){
        \core\session\manager::kill_user_sessions($user->id, session_id());
    }
    // Reset login lockout (if present) before a new password is set.
    login_unlock_account($user);
    // Clear any requirement to change passwords.
    unset_user_preference('auth_forcepasswordchange', $user);
    unset_user_preference('create_password', $user);

    if (!empty($user->lang)){
        // Unset previous session language - use user preference instead.
        unset($SESSION->lang);
    }

    complete_user_login($user); // Triggers the login event.
    \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

    $urltogo = core_login_get_return_url();
    unset($SESSION->wantsurl);

    // Plugins can perform post set password actions once data has been validated.
    core_login_post_set_password_requests($data, $user);

    redirect(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]));
}

// TODO:ADD CUSTOM IMAGES
function stripe_notice($message, $link = '', $link_text = '', $title = null, $success = null){
    global $PAGE, $OUTPUT;
    $message = clean_text($message);   // In case nasties are in here.
    if (CLI_SCRIPT){
        echo("!!$message!!\n");
        exit(1); // No success.
    }

    if (!$PAGE->headerprinted){
        // Header not yet printed.
        $PAGE->set_title(get_string('notice'));
        echo $OUTPUT->header();
    } else {
        echo $OUTPUT->container_end_all(false);
    }

    $image = '';
    if (!is_null($success)){
        $image = ($success) ? 'success-big-checkmark' : 'error-big-checkmark';
        $image = stripe_pix($image);
    }

    return $OUTPUT->render_from_template('auth_stripe/notice', array(
        'message'     => $message,
        'link_button' => $link,
        'link_text'   => $link_text,
        'title'       => $title,
        'image'       => $image,
    ));
}

function get_prices_with_tokens($productid){
    global $DB;
    $sql = "SELECT pr.*, prt.token 
        FROM {".stripe_database::TABLE_PRICE."} pr
        LEFT JOIN {".stripe_database::TABLE_PRICE_TOKEN."} prt ON prt.priceid = pr.id
        WHERE pr.productid = ?
        ORDER BY pr.id";
    $price_records = $DB->get_records_sql($sql, [$productid]);

    $prices = $tokens = [];
    foreach ($price_records as $price_record){
        $prices[] = price::get_from_multirecord($price_record);
        $tokens[$price_record->id] = $price_record->token;
    }
    return [$prices, $tokens];
}

function get_price_by_token($token) {
    global $DB;
    $sql = "SELECT * 
            FROM {".stripe_database::TABLE_PRICE_TOKEN."} pt
            JOIN {".stripe_database::TABLE_PRICE."} p ON p.id = pt.priceid
            WHERE pt.token = :token
            LIMIT 1";

    return $DB->get_record_sql($sql, ['token' => $token]);
}

function get_url_from_token($token = null, $payment_url = null){
    global $CFG, $DB;

    $is_checkout = false;

    if ($token) {

        $record = get_price_by_token($token);

        if ($record) {
            $is_checkout = $record->is_checkout;
        }
    }

    $payment_page = $is_checkout ? CHECKOUT_PAYMENT_URL : (!empty($payment_url) ? $payment_url : SECOND_COACHING_PAYMENT_URL);
    $url = $CFG->wwwroot.$payment_page;

    $params = [];
    if (!empty($token)){
        $params['secret'] = $token;
    }
    return new moodle_url($url, $params);
}
