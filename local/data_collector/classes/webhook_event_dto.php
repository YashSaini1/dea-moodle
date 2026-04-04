<?php

namespace local_data_collector;

use auth_stripe\core as stripe;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_price_entity;
use auth_stripe\util\util;

class webhook_event_dto {

    public $date;
    public $userid;
    public $user_name;
    public $user_email;
    public $type;
    public $data = null;

    public function __construct($user = null){
        if (empty($user)){
            return;
        }

        $this->userid = $user->id;
        $this->user_email = $user->email;
        $this->user_name = fullname($user);
    }

    /**
     * @param moodle_event_dto $eventdata
     *
     * @return static
     */
    public static function create_from_custom_data(moodle_event_dto $eventdata){
        $user = core::get_user($eventdata->userid);
        $webhook_event = new static($user);
        $webhook_event->type = $eventdata->type;
        $webhook_event->date = $eventdata->date;
        $webhook_event->_load_additional_data($eventdata);
        return $webhook_event;
    }

    /**
     * @param moodle_event_dto $eventdata
     *
     * @return void
     */
    protected function _load_additional_data(moodle_event_dto $eventdata){
        switch ($this->type){
            case core::EVENT_TYPE_LOGIN:
                $this->_load_login_data();
                break;
            case core::EVENT_TYPE_PAYMENT:
                $this->_load_payment_data($eventdata);
                break;
            case core::EVENT_TYPE_CANCEL_TIER:
                $this->_load_cancel_tier_data($eventdata);
                break;
            case core::EVENT_TYPE_SIGNUP:
            default:
                break;
        }
    }

    protected function _load_login_data(){
        $tier = user_tier::get(['userid' => $this->userid], 'current_period_start DESC');
        $this->data = [
            'tier' => util::get_tier_shortname($tier),
        ];
    }

    protected function _load_payment_data($eventdata){
        $product = product::get_by_id($eventdata->data->product);
        $price = price::get_by_id($eventdata->data->price);

        $tier = user_tier::get(['userid' => $this->userid, 'tier' => $product->tier]);
        $this->data = [
            'productid'     => $product->productid,
            'productname'   => $product->name,
            'tier'          => util::get_tier_shortname($tier, $price),
            'amount'        => $price->price,
            'priceid'       => $price->priceid,
            'pricename'     => $price->plan_name,
            'price_details' => [
                'type'      => stripe::is_period_price($price->period) ? core::PRICE_TYPE_SUBSCRIPTION : core::PRICE_TYPE_ONE_TIME,
                'duration'  => $price->period,
                'startdate' => $tier->current_period_start,
                'enddate'   => $tier->current_period_end,
            ],
        ];
    }

    protected function _load_cancel_tier_data($eventdata){
        if (empty($eventdata->data->tier_price)){
            $this->data = [
                'productid'   => null,
                'productname' => null,
                'tier'        => null,
                'priceid'     => null,
                'pricename'   => null,
                'error'       => 'No available user tier',
            ];
            return;
        }

        $tier_entity = tier_price_entity::create($eventdata->data->tier_price);
        $product = product::get_by_id($tier_entity->price->productid);
        $this->data = [
            'productid'   => $product->productid,
            'productname' => $product->name,
            'tier'        => util::get_tier_shortname($tier_entity->tier, $tier_entity->price),
            'priceid'     => $tier_entity->price->priceid,
            'pricename'   => $tier_entity->price->plan_name,
        ];
    }
}