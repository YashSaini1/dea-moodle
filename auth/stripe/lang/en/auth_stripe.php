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
 * Strings for component 'auth_stripe', language 'en'.
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_heading_stripe_description'] = 'Stripe settings';
$string['auth_stripe_description'] = '<p>Stripe payment based self-registration enables a user to create their own account via a \'Create new account\' button on the login page. The user then receives an email containing a secure link to a page where they can confirm their account. Future logins just check the username and password against the stored values in the Moodle database.</p>
<p>This plugin clones of the Email-based self-registration plugin that also enrols a user into selected courses based on a payment by card supplied due to Stripe payment system.</p>
<p>It is also recommended to install an additional block auth_stripe_info for students, which will allow updating the curriculum and making payments. This block must be set by the administrator on the profile page for all users.</p>';
$string['auth_stripe_noemail'] = 'Tried to send you an email but failed!';
$string['auth_stripe_recaptcha'] = 'Adds a visual/audio confirmation form element to the sign-up page for email self-registering users. This protects your site against spammers and contributes to a worthwhile cause. See https://www.google.com/recaptcha for more details.';
$string['auth_stripe_recaptcha_key'] = 'Enable reCAPTCHA element';
$string['auth_stripe_settings'] = 'Settings';
$string['pluginname'] = 'Stripe payment based self-registration';
$string['secret_stripekey'] = 'Stripe Account Secret Key';
$string['public_stripekey'] = 'Stripe Account Public Key';
$string['webhook_endpoint'] = 'Stripe Connect (Webhook - Signing secret) endpoint';
$string['webhook_endpoint_description'] = '<a href="https://stripe.com/docs/webhooks/go-live" target="_blank">See the documentation</a>';
$string['currency_description'] = 'Use one type of currency (<a href="https://stripe.com/docs/currencies" target="_blank">three-letter ISO code</a>)';
$string['resetrecordexpired'] = 'The password reset link you used is more than {$a} old and has expired. Please initiate a new password reset.';
$string['authentication_settings'] = 'Manage authentication settings';
$string['payment_settings'] = 'Payment settings';
$string['upgrade_stripe_link_description'] = 'Link for the stripe payment page (for "Upgrade to premium" button). Leave empty for using usual settings(moodle page for payment)';
$string['upgrade_stripe_link'] = 'Stripe page link';

$string['admin_stripe'] = 'Admin stripe';
$string['tier'] = 'Tier {$a}';

$string['subject_admin'] = 'User Onboarding';
$string['subject_user'] = 'User Onboarding';
$string['message_admin'] = 'Hi {$a->firstname}.

We are pleased to inform you that the onboarding process for user <a href="{$a->link}" target="_blank">{$a->firstname_user} {$a->lastname_user}</a> ({$a->email_user}) has officially started. 

Please ensure that all necessary steps are completed and that the user receives all required information to successfully complete their onboarding.';

$string['message_user'] = 'Hi {$a}.

We are excited to let you know that your onboarding process with DE Academy has officially started!

You will receive further instructions and information shortly to help you complete all the steps. Please keep an eye on your inbox and feel free to reach out if you have any questions or need assistance.

Welcome aboard!';

$string['set_tier_product'] = 'Set product for tiers';
$string['set_product_prices'] = 'Set prices for product';
$string['special_price_editing'] = 'Special price editing';

$string['authentication_settings_description'] = '<p>In addition to enabling the plugin, Stripe payment based self-registration must also be selected from the self registration drop-down menu on the <a href="/admin/settings.php?section=manageauths#admin-registerauth" target="_blank">\'Manage authentication\'</a> page.</p>';
$string['firstname_field'] = 'First Name';
$string['lastname_field'] = 'Last Name';
$string['username_field'] = 'Username';
$string['email_field'] = 'Email';
$string['phone_field'] = 'Phone Number';
$string['phone_field_placeholder'] = '123291234567';
$string['password_field'] = 'Password';
$string['setup_password_text'] = 'Set Up Password';
$string['phone_field_help'] = 'We will not send any SMS verification messages to the indicated phone number.<br>No fee will be charged.';
$string['full_name'] = 'Full Name';
$string['please_enter_full_name'] = 'Please enter your full name.';
$string['full_name_placeholder'] = 'John Doe';
$string['sign_up_title'] = 'Land a Higher Paying Data Job';
$string['sign_up_button_title'] = 'Land Dream Data Job';


$string['new_password_text'] = 'New Password';
$string['confirm_new_password_text'] = 'Confirm New Password';
$string['save_new_password_text'] = 'Set up password and Log in';

$string['try_again'] = 'Try again';
$string['privacy_text'] = '© '.date('Y').' DEacademy . All rights reserved.';

$string['your_plan'] = 'Your plan';
$string['choose_a_plan'] = 'Choose a plan';

$string['profile'] = 'My Profile';

$string['error_required'] = ' This field is required';
$string['validation_error:phone_number_must_starts'] = 'Phone number must starts with +';
$string['validation_error:phone_number_invalid'] = 'Invalid phone number';
$string['error:undefined'] = 'Something went wrong!';

$string['cardholder_name_field'] = 'Cardholder`s Name';
$string['credit_card_field'] = 'Card';
$string['require_agreements_terms_conditions'] = 'I agree with <a href="/terms.php" target="_blank"> terms and conditions</a>';
$string['go_back'] = 'Back';
$string['payment_title'] = 'Payment';
$string['submit_payment'] = 'Submit payment';

$string['subscription'] = 'Subscription';
$string['upgrade_to_premium'] = 'Upgrade to Premium';
$string['get_premium'] = 'Get Premium';
$string['referral_program'] = 'Referral program';
$string['ref_info_link'] = 'https://dataengineeracademy.com/referral-program/';

$string['cancel_tier:cancel_premium'] = 'Cancel Premium';
$string['cancel_tier:cancel_premium_title'] = 'Do you confirm that you want to cancel your Premium subscription?';
$string['cancel_tier:cancel'] = 'No';
$string['cancel_tier:confirm'] = 'Yes';
$string['extended_cancel'] = 'Cancel';
$string['extended_save'] = 'Save';

$string['cancel_tier:success'] = 'Subscription was successfully cancelled';
$string['cancel_tier:failed'] = 'Cannot cancel subscription';

$string['update_tier:popup_text'] = 'Do you confirm to <span id="update-tier-popup-action"></span>?';

$string['status:active'] = 'Active';
$string['status:cancelled'] = 'Cancelled';
$string['what_you_get'] = 'What you get';

$string['premium'] = 'Premium';
$string['premium_text'] = 'Paid {$a}. You can cancel your Premium subscription at any time on your own.';
$string['premium_item1'] = 'Access our full content library';
$string['premium_item2'] = 'Real World Data Engineering Projects For Your Resume';
$string['premium_item3'] = 'Connect With Real World Data Engineers';
$string['premium_item4'] = 'Lifetime access to the coaching course';

$string['premium_item:new1'] = 'Go from zero to job ready';
$string['premium_item:new2'] = 'Our top Python, SQL, World Data Modeling  programs';
$string['premium_item:new3'] = 'More ways to learn to code';

$string['premium_item:annual_item1'] = 'Employment-ready programs';
$string['premium_item:annual_item2'] = 'Priority Customer Support';
$string['premium_item:annual_item3'] = 'Save an extra 30% vs. monthly payment';
$string['premium_item:annual_item4'] = '1 hour mock interview session';

$string['special_premium:name'] = 'Special Premium';
$string['special_premium:message'] = '<p>Access to a full tasks library for accelerated progress.</br><b>Start date</b>: {$a->startdate}.</p>';

$string['coaching_subscription:title'] = 'Coaching Course';
$string['coaching_subscription:start_date'] = '<p>The coaching course <b>start date</b>: {$a}.</p>';
$string['coaching_subscription:second_payment_date'] = '<p>The second payment will be charged automatically in {$a->period} after the first payment.<br><b>Date of the second payment: {$a->nextdate}.</b></p>';
$string['coaching_subscription:next_payment_date'] = '<p>The next payment will be charged automatically in {$a->period} after the previous payment.<br><b>Date of the next payment: {$a->nextdate}.</b></p>';
$string['coaching_subscription:lifetime_access'] = 'Lifetime access to the coaching course.';

$string['reset_password_expired'] = 'Link expired';
$string['forgot_check_email'] = '<p>Check out <a href="mailto:{$a->usermail}">{$a->usermail}</a>. If you continue to have difficulty, please contact the site administrator at <a href="mailto:{$a->adminmail}">{$a->adminmail}</a>. Please note that if you registered through Single Sign-On (SSO), password recovery through this site is not necessary.</p>';
$string['forgot_instructions_sent'] = 'Access instructions sent';

$string['go_to_login_page'] = 'Go to Log in page';
$string['go_to_login'] = 'Go to Log in';
$string['go_to_profile'] = 'Go to Profile';
$string['payment_successfully'] = 'Payment was successful';

$string['signup_haveaccount_text'] = 'Already have an account?';
$string['login'] = 'Log in';
$string['signup'] = 'Sign up';

$string['manyrequest'] = 'You are sending too many requests, please wait 20 seconds.';
$string['alreadylogged'] = 'You have current session, please log out and continue registration.';
$string['createaccountfail'] = 'We are unable to create your account. Please check your payment details.';
$string['alreadyhavesub'] = 'You already have subscription. We will send information to your email.';

$string['text_starter'] = 'Forever access to some part of tasks on each level. You can try the platform for free.';
$string['starter'] = 'Starter';

$string['auth_striperecaptcha'] = 'Adds a visual/audio confirmation form element to the sign-up page for email self-registering users. This protects your site against spammers and contributes to a worthwhile cause. See https://www.google.com/recaptcha for more details.';
$string['auth_striperecaptcha_key'] = 'Enable reCAPTCHA element';

$string['premium_message'] = 'Access to a full tasks library for accelerated progress.';
$string['premium_message_1'] = 'Paid {$a->period}. The next payment date: {$a->renewal}.';
$string['cancelled_premium_message'] = 'You have already canceled your subscription for next {$a->period}. This {$a->period}\'s premium subscription will remain <b>valid until {$a->renewal}</b>.';
$string['premium_lifetime'] = 'For coaching clients.';

$string['price:manage_prices'] = 'Manage prices';
$string['price:add_price'] = 'Add prices';
$string['price:plan_name'] = 'Plan name';
$string['price:period'] = 'Period type';
$string['price:price'] = 'Price';
$string['price:max_times'] = 'Number of write-offs';
$string['price:dependency'] = 'Is this price depends from previous';
$string['price:coupon_allow'] = 'Is coupon allowed?';
$string['price:is_checkout'] = 'Is stripe checkout? (v2)';
$string['price:enabled'] = 'Enable price';
$string['price:ab_info'] = 'A/B information';
$string['price:token_url'] = 'Page URL';
$string['price:edit_price'] = 'Edit price';

$string['period:one_time'] = 'One time';
$string['period:day'] = 'Daily';
$string['period:month'] = 'Monthly';
$string['period:year'] = 'Yearly';

/// Coupons
$string['coupon'] = 'Coupon';
$string['coupon:name'] = 'Name';
$string['coupon:stripeid'] = 'Stripe ID';
$string['coupon:amount_off'] = 'Amount off';
$string['coupon:percent_off'] = 'Percent off';
$string['coupon:currency'] = 'Currency';
$string['coupon:duration'] = 'Duration (type)';
$string['coupon:duration_in_months'] = 'Duration in months';
$string['coupon:enabled'] = 'Enabled';

$string['coupon:duration:once'] = 'Applies to the first charge with this coupon applied';
$string['coupon:duration:repeating'] = 'Applies to charges in the first {$a} months with this coupon applied.';
$string['coupon:duration:forever'] = 'Applies to all charges with this coupon applied.';

$string['coupon:profile:repeating'] = 'You have a {$a->discount} discount on this subscription until {$a->due_date}.';
$string['coupon:profile:forever'] = 'You have a forever {$a->discount} discount for this subscription.';

$string['coupon:apply'] = 'Apply coupon';
$string['coupon:remove'] = 'Remove coupon';

$string['coupon:manage'] = 'Manage coupons';
$string['coupon:register'] = 'Register coupon';
$string['coupon:created'] = 'Coupon successfully added!';

$string['coupon:error:already_registered'] = 'Coupon already registered';
$string['coupon:error:already_registered_with_such_name'] = 'Coupon with such name \'{$a}\' (register ignored) already registered';
$string['coupon:error:invalid_coupon'] = 'Invalid coupon!';
$string['coupon:error:undefined_duration'] = 'Undefined duration \'{$a}\' was retrieved from stripe';

/// Promocodes (for subscriptions)
$string['promocode'] = 'Promocode';
$string['promocode:code'] = 'Code';
$string['promocode:stripeid'] = 'Stripe ID';
$string['promocode:currency'] = 'Currency';
$string['promocode:enabled'] = 'Enabled';

$string['promocode:manage'] = 'Manage Promocodes';
$string['promocode:register'] = 'Register';
$string['promocode:created'] = 'Promocode successfully added!';

$string['promocode:error:already_registered'] = 'Coupon already registered';
$string['promocode:error:invalid_promocode'] = 'Invalid promocode!';

/// Promobanner
$string['promobanner'] = 'Promobanner';
$string['promobanner:settings'] = 'Promo Banner settings';
$string['promobanner:enabled'] = 'Is enabled';
$string['promobanner:event:blackfriday'] = 'Black Friday';
$string['promobanner:coupon'] = 'Enter registered coupon name';
$string['promobanner:banner_text'] = 'Enter displayed banner text';
$string['promobanner:banner_text_short'] = 'Enter shorted mobile banner text';
$string['promobanner:banner_duration'] = 'Duration';
$string['promobanner:banner_duration_period'] = 'Period';

/// Common
$string['copy'] = 'Copy';
$string['copied'] = 'Copied';

$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';

$string['updating'] = 'Updating...';

$string['paid_success:coaching_title'] = 'You have successfully paid for the course!';
$string['paid_success:access_instructions_sent'] = 'Access instructions sent';
$string['paid_success:user_created_text'] = 'Check out <a class="email-text" href="mailto:{$a->usermail}" id="user-email-text">{$a->usermail}</a>. If you continue to have difficulty, please contact the site administrator at <a class="email-text" href="mailto:{$a->supportmail}" id="support-email-text">{$a->supportmail}</a>';

$string['one_time:billed_once'] = 'Billed once.';
$string['split_payment:billed_twice'] = 'Billed twice.';
$string['split_payment:second_payment'] = 'The second payment will be automatically charged in 30 days after the first payment.';
$string['split_payment:second_payment_date'] = 'Date of the second payment: {$a}.';
$string['deposit_payment:main_info'] = 'The deposit is ${$a->first_deposit}. The next ${$a->period_price} payments automatically will charge each {$a->period} for the next {$a->period_times} {$a->period_many}. The total cost of the course is ${$a->final_price} over {$a->final_times} {$a->period_many}.';
$string['deposit_payment:next_payment_date'] = 'Next payment date: {$a}.';

/// Capabilities
$string['stripe:view_custom_prices'] = 'Possibility to view created second_coaching payment links';
$string['stripe:create_custom_price'] = 'Possibility to create second_coaching payment links';

/// Tasks
$string['task:send_seller_email'] = 'Send seller email background task';
$string['task:send_invoice_email'] = 'Send payment invoice email background task';
$string['task:update_customer'] = 'Update customer data in stripe task';
$string['task:cleanup_expired_user_promobanner'] = 'Сleanup expired user promobanners';

/// Emails
$string['email_confirms_flow_subject'] = 'Successful registration';
$string['email_teacher_confirmation_html'] = 'Congratulations {$a->firstname}!<br/><br/>Your Account \'{$a->email}\' has been successfully created.<br/><br/>Thanks';
$string['newusernewspecialoffer'] = 'Hi {$a->firstname},

A new account has been created at data engineer academy.

<p><b>You have been issued a temporary password and will have to change your password
when you login for the first time.</b></p>

<p><b><u>Your current login information is now:</u></b></p>
   Log in at -> <a href="{$a->link}">{$a->link}</a></br>
   <b>username:</b> {$a->username}
   <b>password:</b> {$a->newpassword}

<div></br>Data Engineer Interview Guide: <a href="https://drive.google.com/file/d/1uV-7spRTp_xkPyKU642Ya8BnVjshiPuA/view?usp=sharing">https://drive.google.com/file/d/1uV-7spRTp_xkPyKU642Ya8BnVjshiPuA/view?usp=sharing</a></br>
Book First Coaching Session: <a href="https://calendly.com/coaching_de_academy">https://calendly.com/coaching_de_academy</a></div>

If the link is not clickable, then cut and paste the URL in a web browser.

Cheers from the \'{$a->sitename}\',
{$a->signoff}';

$string['email:user_paid_seller_subject'] = 'User {$a->fullname} make a payment';
$string['email:user_paid_seller_message'] = 'The user {$a->fullname} has bought \'{$a->productname}\' product for a total price {$a->amount} {$a->currency} with the payment plan of \'{$a->pricename}\'.';

$string['email:coaching_email_subject'] = '{$a->sitename}: Coaching payment';
$string['email:coaching_email_message'] = '<div>Invoice number: {$a->invoice_number}</br>
Invoice date: {$a->invoice_date}</br>
Account Name: {$a->account_name}</br>
Charged amount: {$a->total_price}</br>

For more information please see attached "{$a->invoice_filename}" pdf file.

{$a->sitename}
</div>';
