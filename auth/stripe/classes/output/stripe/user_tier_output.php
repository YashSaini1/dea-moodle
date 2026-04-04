<?php

namespace auth_stripe\output\stripe;

use auth_stripe\core;
use auth_stripe\model\user_tier;
use auth_stripe\processor\promobanner\user_promo_banner_renderer;
use auth_stripe\subscription\tier_price_entity;
use auth_stripe\subscription\tier_price_loader;
use auth_stripe\subscription\tier_processor;
use moodle_url;
use auth_stripe\model\coupon;

class user_tier_output {

    public $userid;
    public bool $is_current_user;
    public bool $is_admin;
    public object $user;
    public int $now;
    public array $tiers;
    protected $has_cancel_button = false;

    /**
     * @param numeric|object $user_or_id
     */
    public function __construct($user_or_id = null){
        $this->userid = core::get_userid($user_or_id);
        $this->is_current_user = core::get_userid() == $this->userid;
        $this->now = time();

        if (!$this->is_current_user){
            $this->user = \core_user::get_user($this->userid);
        } else {
            global $USER;
            $this->user = $USER;
        }

        $this->is_admin = \local_sql\moodle\role_manager::is_admin($this->user);
        if ($this->is_admin && $this->is_current_user){
            $this->tiers = [];
        } else {
            $this->tiers = static::_get_user_tiers($this->userid);
        }
    }

    /**
     * Load user tiers from db
     *
     * @param \stdClass|int $user_or_id
     *
     * @return array|tier_price_entity[]|mixed
     */
    protected static function _get_user_tiers($user_or_id){
        static $tiers = [];

        $userid = core::get_id($user_or_id);
        if (!isset($tiers[$userid])){
            $tiers[$userid] = tier_price_loader::get_all_by_user($userid);
        }

        // If suddenly the user does not have any tier, we will create it.
        if (empty($tiers[$userid])){
            $tiers[$userid] = [tier_processor::empty_tier($userid)];
        }

        return $tiers[$userid];
    }

    /**
     * Validate and return upgrade url
     *
     * @param array $params url params
     *
     * @return false|\moodle_url
     */
    public function get_upgrade_url($params = null){
        global $USER;
        if (user_promo_banner_renderer::is_should_rendered()){
            return false;
        }

        if (static::is_wait_onboarding($USER->id)) {
            return false;
        }

        return tier_processor::user_has_tier(user_tier::PREMIUM_TIER, $this->user) ? false : static::_get_premium_url($params);
    }

    public static function is_wait_onboarding($user_id){
        $update_user = \core_user::get_user($user_id);
        return isset($update_user->waitonboarding) && $update_user->waitonboarding == 1;
    }

    /**
     * Return premium url without any validation
     *
     * @param array $params url params
     *
     * @return \moodle_url
     */
    protected static function _get_premium_url($params = null){
        $config = get_config('auth_stripe');
        $stripe_link = $config->upgrade_stripe_link;

        if (isset($params['apply_coupon'])) {
            $url = new \moodle_url($stripe_link);
            $url->param('coupon', coupon::get_by_name($params['apply_coupon'])->stripeid);
            return $url;
        }
        return new \moodle_url($stripe_link);
    }

    /**
     * Render all user tiers
     *
     * @return bool|string
     */
    public function render(){
        if ($this->is_admin){
            $tier_entity = new tier_price_entity(new user_tier([
                'current_period_start' => $this->user->firstaccess, // fore some reasons timecreated field is equals to 0
            ]), null);
            $tier_card_renderer = new tier_card_renderer($tier_entity, $this);

            return $this->_render_subscriptions($tier_card_renderer->coaching_card());
        }

        $result_cards = $this->_render_cards();

        if (empty($result_cards)){
            $premium_url = null;
            if ($this->is_current_user){
                $premium_url = static::_get_premium_url();
            }
            $tier_card_renderer = new tier_card_renderer(null, $this);
            $result_cards .= $tier_card_renderer->free_card($premium_url);
        }

        if ($this->has_cancel_button){
            static::_init_cancel_tier_js();
        }

        return $this->_render_subscriptions($result_cards);
    }

    /**
     * Render all tier cards information
     *
     * @return string
     */
    protected function _render_cards(): string{
        $result_cards = '';
        foreach ($this->tiers as $tier_entity){
            if ($tier_entity->tier->is_free()){
                continue;
            }

            $tier_card_renderer = new tier_card_renderer($tier_entity, $this);

            if ($tier_entity->tier->is_coaching()){
                $result_cards .= $tier_card_renderer->coaching_card();
                continue;
            }

            if ($tier_entity->tier->is_special_premium()){
                $this->has_cancel_button = false; // Do not allow to cancel special premium
                $result_cards .= $tier_card_renderer->special_premium_card();
                continue;
            }

            if ($tier_entity->tier->is_premium()){
                if ($tier_entity->tier->can_cancel && empty($this->tier_entity->tier->time_cancelled)){
                    $this->has_cancel_button = true;
                }
                $result_cards .= $tier_card_renderer->premium_card();
                continue;
            }

            $result_cards .= $tier_card_renderer->default_card();
        }
        return $result_cards;
    }

    protected function _render_subscriptions($tier_cards){
        global $OUTPUT;
        return $OUTPUT->render_from_template('auth_stripe/user_subscription', [
            'tier_cards' => $tier_cards,
        ]);
    }

    protected static function _init_cancel_tier_js(){
        global $PAGE;
        $PAGE->requires->js_call_amd(core::PLUGIN_NAME.'/cancel_premium_popup', 'init', [
            'cancel_tier_url' => CANCEL_TIER_URL,
            'popup_title' => core::str('cancel_tier:cancel_premium_title')
        ]);
    }
}