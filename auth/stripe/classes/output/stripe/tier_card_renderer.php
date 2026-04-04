<?php

namespace auth_stripe\output\stripe;

use auth_stripe\core;
use auth_stripe\model\renderable\tier_card;
use auth_stripe\model\user_tier;
use auth_stripe\processor\user_tier\card_text_processor;
use auth_stripe\subscription\tier_price_entity;
use auth_stripe\subscription\tier_processor;
use auth_stripe\util\price_display_util;
use auth_stripe\util\util;

class tier_card_renderer {

    public ?tier_price_entity $tier_entity;

    public user_tier_output $tier_output;

    public card_text_processor $card_text_processor;

    /**
     * @param tier_price_entity|null $tier_price_entity $tier_price_entity
     * @param user_tier_output       $tier_output
     */
    public function __construct(?tier_price_entity $tier_price_entity, user_tier_output $tier_output){
        $this->tier_entity = $tier_price_entity;
        $this->tier_output = $tier_output;
        if($tier_price_entity){
            $this->card_text_processor = new card_text_processor($tier_price_entity, $tier_output);
        }
    }

    /**
     * Render standard card if new tier will be added (non-coaching, non-premium)
     *
     * @return string
     */
    public function default_card(){
        $tier_card = new tier_card();
        $tier = $this->tier_entity->tier;
        $product = user_tier::get_product($tier->tier);
        $tier_card->name = $product->name;
        $tier_card->text = '';
        if ($this->tier_entity->price){
            $tier_card->text = '<p>Price: "'.$this->tier_entity->price->plan_name.'".';
            if (!empty($this->tier_entity->tier->current_period_start)){
                $tier_card->text .= '</br><b>Start date:</b> '.util::format_date($this->tier_entity->tier->current_period_start).'.';
            }
            $tier_card->text .= '</p>';
        }
        $tier_card->formatted_price = price_display_util::format_profile_price($this->tier_entity->price);

        return $this->render($tier_card);
    }

    /**
     * Render free tier card
     *
     * @param string|null $upgrade_url
     *
     * @return string
     */
    public function free_card($upgrade_url = null){
        $tier_card = new tier_card();
        $tier_card->name = core::str('starter');
        $tier_card->text = core::str('text_starter');
        $tier_card->formatted_price = '0/mo';

        if (!empty($upgrade_url)){
            $tier_card->update_link = $upgrade_url;
        }
        return $this->render($tier_card);
    }

    /**
     * Render premium card
     *
     * @return string
     */
    public function premium_card(){
        $a = new \stdClass();
        $a->renewal = util::format_date($this->tier_entity->tier->current_period_end);

        [$formatted_price, $coupon_price] = price_display_util::format_profile_price_with_coupon($this->tier_entity, null);

        $tier_card = new tier_card();
        $tier_card->name = core::str('premium');

        if (!empty($coupon_price)){
            $tier_card->discounted_price = $coupon_price;
        }
        $tier_card->formatted_price = $formatted_price;

        $tier_card->tierid = $this->tier_entity->tier->id;
        if (!empty($this->tier_entity->tier->time_cancelled)){
            $tier_card->status = tier_card::STATUS_CANCELLED;
            $tier_card->cancelled = true;
            $tier_card->status_text = core::str('status:'.tier_card::STATUS_CANCELLED);
            $a->period = $this->tier_entity->price->period;
            $tier_card->text = core::str('cancelled_premium_message', $a);
        } else {
            $a->period = util::format_premium_period($this->tier_entity->price);
            if ($this->tier_output->is_current_user){
                $tier_card->cancel_button = $this->tier_entity->tier->can_cancel;
            }

            // card text
            $tier_card->text = core::str('premium_message');
            if (!empty($a->period)){
                $tier_card->text = $tier_card->text . ' ' . core::str('premium_message_1', $a);
            }

            if (tier_processor::user_has_tier(user_tier::COACHING_TIER, $this->tier_output->user)) {
                $trial_text = \html_writer::tag('b', core::str('premium_lifetime'));
                $tier_card->text = $trial_text . ' ' . $tier_card->text;
            }

            if (!empty($coupon_price)){
                $tier_card->text .= '';
            }
        }
        return $this->render($tier_card);
    }

    /**
     * Render special premium card
     *
     * @return string
     */
    public function special_premium_card(){
        [$formatted_price, $coupon_price] = price_display_util::format_profile_price_with_coupon($this->tier_entity);

        $a = new \stdClass();
        $a->startdate = util::format_date($this->tier_entity->tier->current_period_start);
        $a->period = util::format_premium_period($this->tier_entity->price);

        $tier_card = new tier_card();
        $tier_card->name = core::str('special_premium:name');
        $tier_card->formatted_price = $formatted_price;

        $tier_card->text = core::str('special_premium:message', $a);

        if (!empty($coupon_price)){
            $tier_card->discounted_price = $coupon_price;
        }

        return $this->render($tier_card);
    }

    /**
     * Render coaching card
     *
     * @return string
     */
    public function coaching_card(){
        $tier_card = new tier_card();
        $tier_card->name = core::str('coaching_subscription:title');
        $tier_card->text = $this->card_text_processor->get_coaching_text();
        return $this->render($tier_card);
    }

    /**
     * @param tier_card $tier_card
     *
     * @return string
     */
    public function render(tier_card $tier_card){
        global $OUTPUT;
        if ($this->tier_entity && !empty($this->tier_entity->coupon)){
            $coupon_message = $this->card_text_processor->get_coupon_text();
            if (!empty($coupon_message)){
                $tier_card->text .= '</br><b>'.$coupon_message.'</b>';
            }
        }

        return $OUTPUT->render($tier_card);
    }
}