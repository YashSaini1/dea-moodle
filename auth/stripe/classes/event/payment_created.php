<?php

namespace auth_stripe\event;

use auth_stripe\model\dto\stripe_payment_info;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_loader;

class payment_created extends \core\event\base {

    public product $product;

    public price $price;

    public ?stripe_payment_info $stripe_payment_info;

    /**
     * @var user_tier[]
     */
    public array $user_tiers;

    /**
     * @param product                  $product
     * @param price                    $price
     * @param array                    $eventdata
     * @param stripe_payment_info|null $stripe_payment_info $stripe_payment_info
     *
     * @return payment_created|static
     * @throws \coding_exception
     */
    public static function create_by_product(product $product, price $price, array $eventdata, stripe_payment_info $stripe_payment_info = null){
        $user_tiers = array_key_exists('user_tiers', $eventdata) ? $eventdata['user_tiers'] : null;
        unset($eventdata['user_tiers']);

        /** @var static $event */
        $event = static::create($eventdata);
        $event->product = $product;
        $event->price = $price;
        $event->stripe_payment_info = $stripe_payment_info;
        if ($user_tiers){
            $event->user_tiers = $user_tiers;
        } else {
            $event->user_tiers = tier_loader::get_all_by_user($event->userid);
        }
        return $event;
    }

    /**
     * @inheritDoc
     */
    protected function init(){
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = static::LEVEL_OTHER;
    }
}