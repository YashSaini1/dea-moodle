<?php

use auth_stripe\core;
use auth_stripe\stripe;
use auth_stripe\subscription\tier_price_loader;

require_once('../../config.php');

$tierid = required_param('tier', PARAM_INT);

require_login();

$stripe = new stripe();
try {
    /** @var \auth_stripe\model\user_tier $tier */
    foreach ($USER->tier as $tier){
        if ($tier->id == $tierid){
            [$product, $tier_entity] = tier_price_loader::get_product_with_price_by_tier($tier);
            $subscription = $stripe->get_stripe_subscription($tier_entity->price, $product);
            if (empty($subscription)){
                \core\notification::error(core::str('cancel_tier:failed'));
                break;
            }
            $stripe->update_stripe_subscription($subscription->id, ['cancel_at_period_end' => true]);
            $tier->time_cancelled = $subscription->current_period_end;
            $tier->save();
            \core\notification::success(core::str('cancel_tier:success'));
        }
    }
} catch (Throwable $e){
    core::error('CANCEL TIER ERROR: '.$e->getMessage());
    \core\notification::error(core::str('cancel_tier:failed'));
}

core::redirect_to_profile();