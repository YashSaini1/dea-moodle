<?php

namespace auth_stripe\observers;

use auth_stripe\core\stripe_database;
use auth_stripe\model\customer;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\user_promo_banner;
use auth_stripe\stripe;
use auth_stripe\subscription\tier_loader;
use Exception;
use local_referral\core\referrals\referrals_manager;

class user_observer {

    public static function user_loggedin(\core\event\user_loggedin $event){
        global $USER, $SESSION;
        if ($USER->auth == 'oauth2' && empty($USER->phone1) && get_user_preferences('auth_forcepasswordchange', false, $USER)){
            $SESSION->changed_auth = $USER->auth;
            $USER->auth = 'stripe';
        }

        $promobanner_config = promobanner_config::get_instance();
        if ($promobanner_config->enabled){
            $user_promo = user_promo_banner::get_by_user($USER->id);
            if ($user_promo){
                $USER->{user_promo_banner::USER_FIELD} = $user_promo;
            }
        }
    }

    public static function user_created(\core\event\user_created $event){
        $user = \core_user::get_user($event->objectid);
        \auth_stripe\subscription\tier_processor::empty_tier($user->id);

        try {
            $stripe = new stripe($user, true);
            $stripe->create_customer($user);
        } catch (Exception $e) {
            \auth_stripe\core::log_message("Create customer ".$e->getMessage());
        }

        referrals_manager::create_link($user->id);

        if (isset($_COOKIE['ref']) && referrals_manager::is_code_valid($_COOKIE['ref'])) {
            referrals_manager::set_referral($user->id, $_COOKIE['ref']);
        }

        if (($user->auth != 'stripe') && empty($user->phone1)){
            set_user_preference('auth_forcepasswordchange', true, $user);
        }

        $promobanner_config = promobanner_config::get_instance();
        if ($promobanner_config->enabled){
            user_promo_banner::create([
                'type'        => user_promo_banner::TYPE_NEW_USER,
                'userid'      => $user->id,
                'timecreated' => $user->timecreated,
                'timedue'     => $promobanner_config->get_due_time($user->timecreated),
            ]);
        }
    }

    public static function user_deleted(\core\event\user_deleted $event){
        global $DB;
        $userid = $event->objectid;
        $old_user = $event->get_record_snapshot('user', $userid);
        $customer = customer::get_by_userid($userid);
        if (!$customer){
            return;
        }

        $stripe = new stripe($old_user);

        $DB->delete_records(stripe_database::TABLE_INVOICE, ['customer_id' => $customer->id]);
        $subscriptions = $DB->get_records(stripe_database::TABLE_SUBSCRIPTION, ['customer_id' => $customer->id]);
        foreach ($subscriptions as $subscription){
            $DB->get_records(stripe_database::TABLE_TRANSACTION, ['id' => $subscription->transaction_id]);
            $DB->delete_records(stripe_database::TABLE_SUBSCRIPTION, ['id' => $subscription->id]);
        }

        $tiers = tier_loader::get_all_by_user($userid);
        foreach ($tiers as $tier){
            $tier->delete();
        }

        // $customer->active = 0;
        $DB->delete_records(stripe_database::TABLE_PAYMENT_METHOD, ['customer_id' => $customer->id]);
        $customer->delete();
        $stripe->delete_customer();
        // delete all user info and set deleted field in customer table
    }
}