<?php

namespace auth_stripe\processor\user_tier\subscription;

use auth_stripe\core;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_processor;
use auth_stripe\subscription_processor;
use local_sql\moodle\role_manager;

require_once("$CFG->dirroot/availability/condition/extended/lib.php");

class manual_subscription_manager {

    protected bool $_is_events_enabled;

    const PREMIUM = 'premium';
//    const SPECIAL_PREMIUM = 'special_premium';
    const COACHING = 'coaching';
    const SELLER = 'seller';

    const ONBOARDING = 'onboarding';

    public const TYPES_PAGES = [
        self::PREMIUM  => core::MAIN_PAGE,
        //        self::SPECIAL_PREMIUM => core::SPECIAL_PREMIUM_PAGE,
        self::COACHING => core::SECOND_COACHING_PAGE,
        self::SELLER   => false,
    ];

    public function __construct(){
        $this->_is_events_enabled = core::is_trigger_events();
    }

    public function user_has_subscription($type, $user): bool{
        if ($type == static::SELLER){
            return role_manager::is_seller($user);
        }

        return tier_processor::check_page(static::TYPES_PAGES[$type], $user);
    }

    public function apply($type, $user): bool{
        $userid = core::get_id($user);

        if (!$userid){
            core::debug('Invalid user object to apply', [$type, $user]);
            throw new \Exception('Invalid parameters');
        }

        if (!$this->_is_type_available($type)){
            core::debug('Unavailable apply type "'.$type.'" to userid '.$userid, [$type, $user]);
            return false;
        }

        // Check if current subscription does exist
        if ($this->user_has_subscription($type, $user)){
            return false;
        }


        if ($user->waitonboarding == 1){
            return false;
        }

        // Process moodle role
        if ($type == static::SELLER){
            role_manager::assign_role(role_manager::SELLER_ROLE, $userid);
            return true;
        }

        // Process stripe subscriptions
        core::set_trigger_events(false);

        $product = product::get_by_page(static::TYPES_PAGES[$type]);
        subscription_processor::add_tier($product, $userid, null, ['current_period_start' => time(), 'can_cancel' => 0]);

        if ($type == static::COACHING){
            $sub_manager = new subscription_processor($user);
            $sub_manager->apply_coaching();
        }

        core::set_trigger_events($this->_is_events_enabled);
        return true;
    }

    public function remove($type, $user): bool{
        $userid = core::get_id($user);

        if (!$userid){
            core::debug('Invalid user object to remove', [$type, $user]);
            throw new \Exception('Invalid parameters');
        }

        if (!$this->_is_type_available($type)){
            core::debug('Unavailable remove type "'.$type.'" to userid '.$userid, [$type, $user]);
            return false;
        }

        // Check if current subscription does not exist
        if (!$this->user_has_subscription($type, $user)){
            return false;
        }

        if ($user->waitonboarding == 1){
            return false;
        }

        // Process moodle role
        if ($type == static::SELLER){
            role_manager::unassign_role(role_manager::SELLER_ROLE, $userid);
            return true;
        }

        // Process stripe subscriptions
        core::set_trigger_events(false);

        $tiers = $this->_parse_type_to_tiers($type);
        $deleted = false;
        foreach ($tiers as $tier){
            $user_tier = tier_processor::user_has_tier($tier, $user, true);
            if ($user_tier){
                $user_tier->delete();
                $deleted = true;
            }
        }

        core::set_trigger_events($this->_is_events_enabled);

        if (!$deleted){
            core::debug('Does not remove type "'.$type.'" for userid '.$userid, [$type, $user->tier]);
            return false;
        }

        return true;
    }

    public function block_unblock($type, $user, $action): bool {
        $userid = core::get_id($user);

        if (!$userid) {
            core::debug('Invalid user object to remove', [$type, $user]);
            throw new \Exception('Invalid parameters');
        }
        global $DB;

        try {
            $DB->set_field('user', 'waitonboarding', ($action == 'block' ? 1 : 0), ['id' => $userid]);
        } catch (\Exception $e) {
            core::debug('Failed to update waitonboarding for userid '.$userid, [$e->getMessage()]);
            return false;
        }

        core::set_trigger_events($this->_is_events_enabled);
        return true;
    }

    public function add_extended($type, $user, $action, $list): bool {
        global $DB;
        $userid = core::get_id($user);

        if (!$userid) {
            core::debug('Invalid user object to remove', [$type, $user, $list]);
            throw new \Exception('Invalid parameters');
        }

        $existing_records = $DB->get_records_menu('sql_extended', ['userid' => $userid], '', 'sectionid, id');
        $existing_ids = array_keys($existing_records);

        if (empty($list)) {
            if (!empty($existing_ids)) {
                list($in_sql, $params) = $DB->get_in_or_equal($existing_ids);
                $DB->delete_records_select('sql_extended', "userid = ? AND sectionid $in_sql", array_merge([$userid], $params));
            }
            return true;
        }

        $list_ids = array_map('trim', explode(',', $list));
        $to_add = array_diff($list_ids, $existing_ids);
        $to_remove = array_diff($existing_ids, $list_ids);

        if (!empty($to_add)) {
            $records = array_map(fn($id) => ['userid' => $userid, 'sectionid' => $id, 'timecreated' => time()], $to_add);
            $DB->insert_records('sql_extended', $records);
            array_map(fn($id) => auto_enrol_user($id, $userid), $to_add);
        }

        if (!empty($to_remove)) {
            list($in_sql, $params) = $DB->get_in_or_equal($to_remove);
            $DB->delete_records_select('sql_extended', "userid = ? AND sectionid $in_sql", array_merge([$userid], $params));
        }

        core::set_trigger_events($this->_is_events_enabled);
        return true;
    }

    protected function _is_type_available($type): bool{
        return array_key_exists($type, static::TYPES_PAGES);
    }

    protected function _parse_type_to_tiers($type): array{
        if ($type == static::COACHING){
            return [user_tier::COACHING_TIER];
        }

        if ($type == static::PREMIUM){
            return [user_tier::PREMIUM_TIER, user_tier::SPECIAL_PREMIUM_TIER];
        }

        return [];
    }
}