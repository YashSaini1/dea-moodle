<?php

namespace auth_stripe;

use auth_stripe\model\coupon;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use auth_stripe\model\user_tier_price;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\subscription\tier_price_entity;
use auth_stripe\subscription\tier_price_loader;
use auth_stripe\subscription\tier_processor;
use local_ab_testing\test\premium_price_annual_test;
use local_ab_testing\test\premium_price_test;
use local_sql\moodle\role_manager;

class subscription_processor {

    /** @var \stdClass{tier:user_tier[]} $user */
    public $user;

    public ?product $product;

    public ?stripe $stripe;

    public function __construct(&$user, product $product = null, $stripe = null){
        $this->user = &$user;
        $this->product = $product;
        $this->stripe = $stripe;

        $this->user = tier_processor::init_user_tiers($this->user, true);
    }

    public function apply(price $price, $tier_data = []){
	if (tier_processor::check_page($this->product->sql_page, $this->user)){
            $this->_update_tier_price($price);
            return;
        }

        $this->_add_tier($price, $tier_data);
        if ($this->product->check_page(core::MAIN_PAGE)){
            $this->apply_premium();
        } elseif ($this->product->is_coaching_page()) {
            $this->apply_coaching();
        } elseif ($this->product->check_page(core::SPECIAL_PREMIUM_PAGE)) {
            $this->apply_special_premium($price);
        } else {
            core::error('Trying to by undefined page', [
                'product'    => $this->product,
                'price'      => $price,
                'user_tiers' => $this->user->tier,
            ]);
        }
    }

    protected function _update_tier_price(price $price){
        foreach ($this->user->tier as $tier){
            if ($tier->tier == $this->product->tier){
                break;
            }
        }
        \auth_stripe\core::track_time('Starting problem location');
        $tier_price = user_tier_price::get(['usertierid' => $tier->id]);
        \auth_stripe\core::track_time('$tier_price: '.print_r($tier_price, true));
        if ($tier_price) {
            \auth_stripe\core::track_time('OK $tier_price IS NOT NULL');
            $tier_price->priceid = $price->id;
            $tier_price->stripepriceid = $price->priceid;
            $tier_price->couponid = $this->_get_couponid();
            $tier_price->save();
        } else {
            \auth_stripe\core::track_time('ERROR $tier_price IS NULL');
        }
    }

    public static function add_tier(product $product, $userid, price $price = null, $tier_data = [], $couponid = null){
        global $DB, $CFG;
        $tier = new user_tier($tier_data);
        $tier->set_config([
            'userid' => $userid,
            'tier'   => $product->tier,
        ]);
        $tier->save();

        if (!empty($price)){
            user_tier_price::create([
                'priceid'       => $price->id,
                'usertierid'    => $tier->id,
                'stripepriceid' => $price->priceid,
                'couponid'      => $couponid,
            ]);
        }

        $tiers = tier_price_loader::get_all_by_user($userid);
        if (empty($tiers)){
            $tiers = tier_processor::empty_tier($userid);
        }

        \auth_stripe\core::track_time('product '.print_r($product, true));
        $user = \core_user::get_user($userid);
        if (($product->tier == '1' || $product->tier == '3' || $product->tier == '4' || $product->tier == '5') && $user->waitonboarding == '0' && $user) {
            $DB->set_field('user', 'waitonboarding', '1', ['id' => $user->id]);
            $admin_users = role_manager::get_users_with_role(role_manager::ADMIN_ROLE);
            $client_admin = null;
            foreach ($admin_users as $admin_user) {
                if (!is_siteadmin($admin_user->id)) {
                    $client_admin = \core_user::get_user($admin_user->id);
                    break;
                }
            }

            $subject_admin = \auth_stripe\core::str('subject_admin');
            $message_admin = \auth_stripe\core::str('message_admin', ['firstname' => $client_admin->firstname,
                'firstname_user' => $user->firstname, 'lastname_user' => $user->lastname,
                'email_user' => $user->email, 'link' => $CFG->wwwroot.'/user/profile.php?id='.$user->id]);
            $subject_user = \auth_stripe\core::str('subject_user');
            $message_user = \auth_stripe\core::str('message_user', $user->firstname);

            email_to_user($client_admin, $client_admin, $subject_admin, $message_admin);
            email_to_user($user, $client_admin, $subject_user, $message_user);
        }
        return $tier;
    }

    protected function _add_tier(price $price = null, $tier_data = []){
        static::add_tier($this->product, $this->user->id, $price, $tier_data, $this->_get_couponid());
    }

    protected function _get_couponid(){
        $stripe_couponid = stripe::get_coupon();
        $coupon = coupon::get_by_stipeid($stripe_couponid);
        if (empty($coupon)){
            return null;
        }
        return $coupon->id;
    }

    public function apply_premium(){
        /** @var user_tier $tier */
        $tier = end($this->user->tier);
        $tier->can_cancel = 1;
        $tier->save();
    }

    public function apply_special_premium(price $price){
        /** @var user_tier $tier */
        $tier = end($this->user->tier);
        $tier->current_period_start = time();
        $tier->current_period_end = 0;
        $tier->can_cancel = 0;
        if ($price->period != core::PERIOD_ONE_TIME){
            $tier->time_cancelled = strtotime('+1 year');
        }
        $tier->save();
    }

    public function apply_coaching(){
        \local_sql\coaching::add_coaching_role($this->user);

        /** @var user_tier $tier */
        $tier = end($this->user->tier);
        if (empty($tier->current_period_start)){
            $tier->current_period_start = time();
            $tier->current_period_end = strtotime('+1 year');
            // Set one year of coaching
            $tier->save();
        }

//        $this->_apply_delayed_premium();
        $this->apply_free_premium();
    }

    public function apply_free_premium(){
        /** @var user_tier $tier */
        foreach ($this->user->tier as $tier){
            if ($tier->is_premium()){
                return;
            }
        }

        $premium = product::get_by_page(core::MAIN_PAGE);
        static::add_tier($premium, $this->user->id);
        $this->user = tier_processor::init_user_tiers($this->user, true);
    }

    protected function _apply_delayed_premium(){
        core::set_trigger_events(false);

        $subscription_params = [
            'billing_time' => strtotime('+ '.COACHING_PREMIUM_TRIAL_WEEKS.' weeks'),
        ];

        if (tier_processor::user_has_tier(user_tier::PREMIUM_TIER, $this->user)){
            $tier_object = null;
            /** @var user_tier $tier */
            foreach ($this->user->tier as $tier){
                if ($tier->is_premium()){
                    $tier_object = $tier;
                    break;
                }
            }

            if ($tier_object){
                [$premium, $tier_entity] = tier_price_loader::get_product_with_price_by_tier($tier_object);
                $price = $tier_entity->price;
                if (empty($price)){
                    $price = $this->_get_premium_price($premium);
                }
                $subscription_params['billing_time'] += $tier_object->current_period_end - time();
                $this->cancel_subscription($price, $premium);
            }
        }

        if (empty($premium) || empty($price)){
            // Do not add any checks.
            // This products must exist in our system
            $premium = product::get_by_page(core::MAIN_PAGE);
            $price = $this->_get_premium_price($premium);
        }

        $this->stripe->buy_product($premium, $price, ['subscription' => $subscription_params]);
        core::set_trigger_events(true);
    }

    protected function _get_premium_price(product $product){
        $price_params = ['productid' => $product->id, 'period' => [core::PERIOD_DAY, core::PERIOD_MONTH]];
        if (get_config('local_ab_testing', 'enable') && premium_price_annual_test::is_enabled()){
            $user_campaign = premium_price_annual_test::get_user_campaign();
            if (empty($user_campaign)){
                $user_campaign = premium_price_annual_test::get_default_campaign();
            }
            $price_params['ab_info'] = $user_campaign;
        }
        return price::get($price_params);
    }

    public function cancel_subscription(price $price, product $product, $cancel_params = []){
        $subscription = $this->stripe->get_stripe_subscription($price, $product);
        if (empty($subscription)){
            return false;
        }

        $this->stripe->cancel_subscription($subscription->id, $cancel_params);
        $this->delete_local_subscription($product->tier);
        return true;
    }

    public function delete_local_subscription($tier = null, $stripe_subscription = null, $force_delete_coaching = false){
        $msg = '';
        if (empty($tier) && empty($stripe_subscription)){
            return 'Nothing to delete';
        }

        if (!empty($tier)){
            $user_tiers = user_tier::get_all([
                'userid' => $this->user->id,
                'tier'   => $tier,
            ]);
            foreach ($user_tiers as $user_tier){
                $user_tier->delete();
            }

            // At current moment, I don't know the best solution:
            // 1) if user has any non-free tier, user still have free tier
            // 2) if user has ane non-free tier, delete free tier
//        if(count($this->user->tiers) == 1){
//            \auth_stripe\subscription\tier_processor::empty_tier($this->user->id);
//        }

            if ($this->user->id == core::get_userid()){
                global $USER;
                tier_processor::init_user_tiers($USER, true);
            } else {
                \core\session\manager::kill_user_sessions($this->user->id);
            }
            return 'Deleted by tier "'.$tier.'" for userid '.$this->user->id;
        }

        ///// Process hook
        $tier_entity = tier_price_loader::get_entity_by_user_stripe_subscription($this->user, $stripe_subscription);
        if (empty($tier_entity)){
            return 'No such user tier';
        }

        $tier = $tier_entity->tier;

        if ($tier->is_coaching()){
            if (!$force_delete_coaching) return 'Not delete coaching subscription. Return.';

            $msg = $this->find_and_delete_premium_subscription();
            \local_sql\coaching::unenrol_user($this->user->id);
        }

        $tier->delete();
        \core\session\manager::kill_user_sessions($this->user->id);
        return 'Deleted. '.$msg;
        // At current moment, I don't know the best solution:
        // 1) if user has any non-free tier, user still have free tier
        // 2) if user has ane non-free tier, delete free tier
//        if(count($this->user->tiers) == 1){
//            \auth_stripe\subscription\tier_processor::empty_tier($this->user->id);
//        }
    }

    /**
     *
     */
    public function find_and_delete_premium_subscription(){
        $tier_entity = $product = null;
        $products = product::get_all_sorted_by_tier();

        $tier_entity_records = tier_price_loader::get_records($this->user, true);
        foreach ($tier_entity_records as $tier_entity_record){
            $product = $products[$tier_entity_record->tier];
            if (!empty($product) && $product->check_page(core::MAIN_PAGE)){
                $tier_entity = tier_price_entity::create($tier_entity_record);
                $priceid = $tier_entity_record->stripepriceid;
                break;
            }
        }

        if (empty($tier_entity)){
            return 'User no have premium.';
        }

        $premium_subscription = $this->stripe->get_stripe_subscription($tier_entity->price, $product);
        $this->stripe->cancel_subscription($premium_subscription->id);
        $tier_entity->tier->delete();
        return 'Premium deleted.';
    }
}
