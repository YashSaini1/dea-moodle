<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_url('/auth/stripe/payment/success.php');

$template_context = [];
if (!empty($SESSION->new_user_mail)){
    $template_context['usermail'] = $SESSION->new_user_mail;
    $template_context['supportmail'] = \auth_stripe\core::get_support_mail();
}

unset($SESSION->new_user_mail);
unset($SESSION->success_page);

$template_context['logo_url'] = null;
$url = $OUTPUT->get_logo_url();
if ($url){
    $template_context['logo_url'] = $url->out(false);
}

if (!empty($USER->email)){
    $template_context['logged_in'] = true;
    $template_context['buttonurl'] = $CFG->wwwroot.'/user/profile.php';
} else {
    $template_context['logged_in'] = false;
    $template_context['buttonurl'] = get_login_url();
}

stripe_notice_page('paid_coaching', $template_context);