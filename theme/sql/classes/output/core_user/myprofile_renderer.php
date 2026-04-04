<?php

namespace theme_sql\output\core_user;

use auth_stripe\output\subscription\manual_subscription_renderer;
use local_referral\core\referrals\referrals_manager;
use auth_stripe\core;

require_once($CFG->dirroot . '/local/sql/lib.php');
/**
 * Custom render profile
 */
class myprofile_renderer extends \core_user\output\myprofile\renderer {

    function render_user_profile($user, $currentuser){
        $tier_output = new \auth_stripe\output\stripe\user_tier_output($user);

        $context = [
            'timeonplatform' => theme_sql_render_days_on_platform($user),
            'carma_points'   => render_carma_points($user, $currentuser),
            'fullname'       => fullname($user),
            'picture'        => $this->output->user_picture($user, ['size' => 100]),
        ];
        if ($currentuser || \local_sql\moodle\role_manager::is_admin()){
            $context['tier_info'] = $tier_output->render();
            $context['profile_fields'] = [
                [
                    'name' => get_string('phone', 'theme_sql'),
                    'value' => $user->phone1
                ],
                [
                    'name' => get_string('email_field', 'theme_sql'),
                    'value' => $user->email
                ],
                [
                    'name' => get_string('username', 'theme_sql'),
                    'value' => $user->username
                ],
            ];
            $context['editurl'] = (new \moodle_url('/user/edit.php', array('id' => $user->id, 'returnto' => 'profile')))->out(false);

            $context['ref_info_display'] = true;
            $context['friends_registered'] = referrals_manager::get_friends($user->id);
            $context['plans_purchased'] = referrals_manager::get_purchased($user->id);
            $context['balance'] = referrals_manager::get_balance($user->id).'$';
            $context['ref_link'] = (new \moodle_url('/login/signup.php', array('ref' => referrals_manager::get_code($user->id))));
            $context['ref_info_link'] = core::str('ref_info_link', 'auth_stripe');
            $context['email'] = get_actual_email($user);
        }
        if ($currentuser){
            if ($user->auth != 'oauth2') {
                $change_pass_url = new \moodle_url('/login/change_password.php');
                $context['changepasswordurl'] = $change_pass_url->out(false);
            }
        }

        $manual_subscription_renderer = new manual_subscription_renderer($tier_output);
        $context['user_tier_buttons'] = $manual_subscription_renderer->render_buttons();

        $this->page->requires->js_call_amd('theme_sql/referral_manager', 'init', [['sesskey' => sesskey()]]);

        return $this->output->render_from_template('theme_sql/user_profile', $context);
    }
}