<?php

use auth_stripe\core;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\price;
use auth_stripe\model\price\price_email;
use auth_stripe\model\product;
use auth_stripe\model\user_promo_banner;
use auth_stripe\processor\promobanner\user_promo_banner_renderer;
use auth_stripe\stripe;
use auth_stripe\util\PhoneNumber;
use local_referral\core\referrals\referrals_manager;

defined('MOODLE_INTERNAL') || die;

const DEFAULT_TIER_COUNT = 5;

const MAIN_PAYMENT_URL = '/auth/stripe/payment/main.php';
const COACHING_PAYMENT_URL = '/auth/stripe/payment/coaching.php';
const SECOND_COACHING_PAYMENT_URL = '/auth/stripe/payment/second_coaching.php';
const CHECKOUT_PAYMENT_URL = '/auth/stripe/v2/checkout.php';
const SPECIAL_PREMIUM_PAYMENT_URL = '/auth/stripe/payment/special_premium.php';

const PRODUCT_URL = '/auth/stripe/admin/index.php';

const PRICE_URL = '/auth/stripe/admin/prices.php';

const CANCEL_TIER_URL = '/auth/stripe/cancel_tier.php';

const ACCESS_GUIDE_HERE = 'https://drive.google.com/file/d/1uV-7spRTp_xkPyKU642Ya8BnVjshiPuA/view?usp=sharing';
const BOOK_CALL_HERE = 'https://calendly.com/coaching_de_academy';

const COACHING_PREMIUM_TRIAL_WEEKS = '12';

function stripe_name(){
    return 'auth_stripe';
}

/**
 * @param stdClass $user
 */
function auth_stripe_create_student($user){
    global $CFG;
    require_once($CFG->dirroot.'/auth/stripe/locallib.php');

    $user = stripe_create_user($user);

    stripe_send_register_mail($user);
}

function stripe_notice_page($template, $context){
    global $OUTPUT, $PAGE;
    $PAGE->set_pagelayout('notice');

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('auth_stripe/notice/'.$template, $context);
    echo $OUTPUT->footer();
}

/**
 * @param stdClass $user
 *
 * @return stdClass created user with id
 */
function stripe_create_user($user, $createpassword = false, $allow_redirect = true){
    global $CFG, $DB, $SESSION;
    require_once($CFG->dirroot.'/user/profile/lib.php');
    require_once($CFG->dirroot.'/user/lib.php');

    $user->email = strtolower($user->email);
    if (!isset($user->username)){
        $user->username = strtolower(random_string());
    }

    if (!isset($user->username)){
        if ($allow_redirect){
            redirect(new moodle_url('/login/index.php'));
        }
        return null;
    }

    if (!empty($user->phone1)){
        $user->phone1 = PhoneNumber::parse($user->phone1);
    }
    if (!$createpassword){
        $plainpassword = $user->secret;
        $user->password = hash_internal_user_password($plainpassword);
        if (empty($user->calendartype)){
            $user->calendartype = $CFG->calendartype;
        }
    }

    $user->confirmed = 1;
    if ($DB->record_exists('user', array('username' => $user->username))){
        if ($allow_redirect){
            redirect(new moodle_url('/login/index.php'));
        }
        return $user;
    }

    $user->id = user_create_user($user, false, false);
    if (!$createpassword){
        user_add_password_history($user->id, $plainpassword);
    }

    // Save any custom profile field information.
    profile_save_data($user);

    // Save wantsurl against user's profile, so we can return them there upon confirmation.
    if (!empty($SESSION->wantsurl)){
        set_user_preference('auth_stripe_wantsurl', $SESSION->wantsurl, $user);
    }

    if ($createpassword){
        $uname = $user->username;
        $user->username = $user->email;
        stripe_setnew_password_and_mail($user);
        $user->username = $uname;
        unset_user_preference('create_password', $user);
        set_user_preference('auth_forcepasswordchange', 1, $user);
    }

    // Trigger event.
    \core\event\user_created::create_from_userid($user->id)->trigger();
    return $user;
}

// TODO: Rewrite this function by moodle event \core\event\user_created
//          and add checkbox in site administration need to send email or not
function stripe_send_register_mail($user){
    $site = get_site();
    $supportuser = core_user::get_support_user();

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->email = $user->email;
    $data->username = $user->username;
    $data->sitename = $site->fullname;
    $message = core::str('email_teacher_confirmation_html', $data);
    $subject = core::str('email_confirms_flow_subject', format_string($site->fullname));

    return email_to_user($user, $supportuser, $subject, $message, null, null, null, null);
}

function stripe_pix($name, $alt = ''){
    global $OUTPUT;
    if (empty($alt)){
        $alt = $name;
    }
    return $OUTPUT->pix_icon($name, $alt, stripe_name());
}

/**
 * @param $form
 * @param array|null $product_cards
 *
 * @return array{logourl:string, sitename:string, formhtml:string, product_cards:stdClass[], single_card:boolean}
 */
function stripe_get_layout_context($form = null, $product_cards = null){
    global $OUTPUT, $SITE;

    $url = $OUTPUT->get_logo_url();
    if ($url) {
        $url = $url->out(false);
    }

    return [
        'logourl'        => $url,
        'sitename'       => format_string($SITE->fullname, true, ['escape' => false]),
        'formhtml'       => $form,
        'product_cards'  => $product_cards,
        'single_card'    => empty($product_cards[1]), // in mustache you must provide non-associative array. So, we can check [1] element for empty
    ];
}

/**
 * @param auth_plugin_stripe $authplugin
 * @param string $page_type
 */
function stripe_render_payment_page($authplugin, $page_type, $token = '', $coupon = null){
    global $PAGE, $USER;
    $output = $PAGE->get_renderer('auth_'.$authplugin->authtype);

    stripe_body_add_payment_class();
    stripe::set_page($page_type);

    if (!empty($token)){
        $product = product::get_by_page($page_type);
        $prices = price::get_all_by_token($token);
        $product_cards = product::render_prices($product, $prices);
    } else {
        $products = product::get_all_by_page($page_type);
        $product_cards = product::render_product_cards($products);
    }


    if (referrals_manager::is_user_bonus_allow($USER->id) && empty($coupon)) {
        $ref_product = $product ?? $products[0];
        $coupon = referrals_manager::get_coupon(false, $ref_product->is_coaching_page());
    }

    $coupon_allowed = false;
    if ($product_cards[0]['is_allow_coupon'] || isset($coupon)){
        $coupon_allowed = true;
    }

    $form = $authplugin->render_payment_stripe_form($coupon_allowed, $coupon);
    $template_data = stripe_get_layout_context($form, $product_cards);
    if (core::COACHING_PAGE == $page_type){
        $template_data['additional_product_info'] = $output->render_from_template('auth_stripe/cards/coaching_additional', []);
    }

    echo $output->header();
    echo $output->render_from_template('auth_stripe/payment_layout', $template_data);
    echo $output->footer();
    die;
}

function stripe_body_add_payment_class(){
    global $PAGE;
    $PAGE->add_body_class('payment_page');
}

function stripe_body_add_admin_class(){
    global $PAGE;
    $PAGE->add_body_class('stripe_admin_page');
}

// Use this hook to init $USER->tier variable
function auth_stripe_before_session_start(){
//    setup_user_tier();
}

function setup_user_tier(){
    global $USER;
    if(empty($USER)){
        return;
    }

    if (!isset($USER->tier)){
        $USER = \auth_stripe\subscription\tier_processor::init_user_tiers($USER, 1);
    }
}

function create_new_user_from_email($email){
    global $CFG;
    require_once($CFG->libdir.'/authlib.php');
    require_once($CFG->dirroot.'/user/editlib.php');

    $user = new stdClass();
    $user->email = $user->firstname = $user->lastname = $email;
    $user = signup_setup_new_user($user);
    return stripe_create_user($user, true, false);
}

/**
 * Sets specified user's password and send the new password to the user via email.
 *
 * @param stdClass $user A {@link $USER} object
 * @param bool $fasthash If true, use a low cost factor when generating the hash for speed.
 * @return bool|string Returns "true" if mail was sent OK and "false" if there was an error
 */
function stripe_setnew_password_and_mail($user, $fasthash = false) {
    global $CFG, $DB;

    // We try to send the mail in language the user understands,
    // unfortunately the filter_string() does not support alternative langs yet
    // so multilang will not work properly for site->fullname.
    $lang = empty($user->lang) ? get_newuser_language() : $user->lang;

    $site  = get_site();

    $page_type = stripe::get_page();

    $supportuser = core_user::get_support_user();

    $newpassword = generate_password();

    update_internal_user_password($user, $newpassword, $fasthash);

    $a = new stdClass();
    $a->firstname   = fullname($user, true);
    $a->sitename    = format_string($site->fullname);
    $a->username    = $user->username;
    $a->newpassword = $newpassword;
    $a->link        = $CFG->wwwroot .'/login/?lang='.$lang;
    $a->signoff     = generate_email_signoff();

    if ($page_type == core::SPECIAL_PREMIUM_PAGE){
        $a->firstname = $user->email;

        global $SESSION;
        /** @var price $price */
        $price = $SESSION->price;

        $price_email = price_email::get_by_price($price);
        if (!$price_email) {
            $message = price_email::get_default($a);
        } else {
            $message = $price_email->out($a);
        }
    } else {
        $identifier = 'newusernewpasswordtext';
        $component = '';
        $message = (string)new lang_string($identifier, $component, $a, $lang);
    }

    $subject = format_string($site->fullname) .': '. (string)new lang_string('newusernewpasswordsubj', '', $a, $lang);

    // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
    return email_to_user($user, $supportuser, $subject, $message);
}

function auth_stripe_before_http_headers(){
    global $SESSION, $PAGE;
    if (!empty($SESSION->payment_info)){
        $jscode = "if (typeof window['gtag'] === 'function'){ gtag('event', 'stripe_payment', ".json_encode($SESSION->payment_info)."); } else { console.log('Cannot use gtag for payment');}";
        core::info($jscode);
        $PAGE->requires->js_init_code($jscode, true);
        unset($SESSION->payment_info);
    }
}

function auth_stripe_before_standard_top_of_body_html(){
    if (!user_promo_banner_renderer::is_should_rendered()){
        return '';
    }

    global $USER;
    if (user_promo_banner_renderer::black_friday()) {
        $promobanner = [
            'type' => 'new_user',
            'timecreated' => '1731317632',
            'timedue' => '1825925632',
        ];
    } else {
        $promobanner = $USER->{user_promo_banner::USER_FIELD};
    }

    $user_promo_banner_renderer = new user_promo_banner_renderer($promobanner);
    return $user_promo_banner_renderer->render();
}

/**
 * @throws coding_exception
 */
function validate_fullname($fullname) {
    $fullname = trim($fullname);

    if (empty($fullname)) return false;

    $parts = preg_split('/\s+/', $fullname);

    if (count($parts) < 2) return false;

    $firstname = trim(array_shift($parts));
    $lastname  = trim(implode(' ', $parts));

    $firstname = clean_param($firstname, PARAM_TEXT);
    $lastname  = clean_param($lastname, PARAM_TEXT);

    if (mb_strlen($firstname) > 100) return false;

    if (mb_strlen($lastname) > 100) return false;

    return array(
        'firstname' => $firstname,
        'lastname'  => $lastname
    );
}

/**
 * @throws coding_exception
 */
function split_fullname($user): bool {
    if (empty($user)) return false;

    $res = validate_fullname($user->fullname);

    if (!is_array($res) || !isset($res['firstname']) || !isset($res['lastname'])) return false;

    $user->firstname = $res['firstname'];
    $user->lastname = $res['lastname'];

    return true;
}