<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use auth_stripe\subscription\tier_processor;
use core_user;
use local_sql\core\model\base_object;

/**
 * Moodle user subscription entity
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_tier extends base_object {

    static protected string $table = stripe_database::TABLE_USER_TIER;

    // Mark tiers as numbers.
    // Free and premium tiers contains real $product->tier value
    // That's a coincidence
    const FREE_TIER = 0;
    const PREMIUM_TIER = 1;
    const COACHING_TIER = 2;
    const SPECIAL_PREMIUM_TIER = 3;

    public $id;
    public $userid;
    public $tier;
    public $can_cancel;
    public $time_cancelled;
    public $time_created;
    public $current_period_start;
    public $current_period_end;

    /**
     *
     * @param array  $conditions
     * @param string $sort
     * @param string $indexed_by
     *
     * @return static[]
     */
    public static function get_all($conditions = [], $sort = '', $indexed_by = '', $onboarding = false){
        $records = parent::get_records($conditions, $sort);

        $now = time();
        $result = [];
        foreach ($records as $record){
            $tier = static::_create_from_record($record);
            if (!empty($tier->time_cancelled) && $tier->time_cancelled < $now){
                $tier->delete();
                continue;
            }

            if ($onboarding) {
                $user = core_user::get_user($tier->userid);
                if ($user->waitonboarding == '1' && ($record->tier == '1' || $record->tier == '4' || $record->tier == '5' || $record->tier == '3')) {
                    $tier->tier = '0';
                }
            }

            $result[$record->tier] = $tier;
        }
        return $result;
    }

    public function save(){
        if (empty($this->id)){
            $this->time_created = time();
        }
        parent::save();
    }

    public function delete(){
        if ($this->is_coaching()){
            \local_sql\coaching::remove_coaching_role($this->userid);
        }

        if (core::is_trigger_events()){
            $event = \auth_stripe\event\cancel_tier::create_by_tier($this);
            $event->trigger();
        }

        parent::delete();
        user_tier_price::delete_by_tier($this->id);
    }

    /**
     * @param int $tier
     *
     * @return product|null
     */
    public static function get_product($tier): ?product{
        $products = product::get_all_sorted_by_tier();
        return $products[$tier] ?? null;
    }

    public function is_free(): bool{
        return $this->tier == static::FREE_TIER;
    }

    public function is_premium(): bool{
        if (!$this->_validate_time_cancelled()){
            return false;
        }
        $product = static::get_product($this->tier);
        return !empty($product) && $product->is_premium_page();
    }

    public function is_coaching(): bool{
        $product = static::get_product($this->tier);
        return !empty($product) && ($product->is_coaching_page());
    }

    public function is_special_premium(): bool{
        if (!$this->_validate_time_cancelled()){
            return false;
        }
        $product = static::get_product($this->tier);
        return !empty($product) && ($product->check_page(core::SPECIAL_PREMIUM_PAGE));
    }

    protected function _validate_time_cancelled(): bool{
        if (!empty($this->time_cancelled) && $this->time_cancelled < time()){
            $this->delete();
            if ($this->userid == core::get_userid()){
                global $USER;
                tier_processor::init_user_tiers($USER, true);
            } else {
                \core\session\manager::kill_user_sessions($this->userid);
            }
            return false;
        }
        return true;
    }

    public function __toString(){
        return $this->tier;
    }
}