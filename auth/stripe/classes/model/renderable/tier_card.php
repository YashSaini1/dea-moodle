<?php

namespace auth_stripe\model\renderable;

use auth_stripe\core;
use core\output\named_templatable;
use renderer_base;

class tier_card implements named_templatable, \renderable {

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';

    public $name = '';
    public $text = '';
    public $formatted_price = false;
    public $discounted_price = false;
    public $update_link = false;
    public $cancel_button = false;
    public $tierid = false;
    public $status = self::STATUS_ACTIVE;
    public $status_text;
    public $cancelled = false;

    public function __construct(){
        $this->status_text = core::str('status:'.$this->status);
    }

    public function get_template_name(\renderer_base $renderer): string{
        return 'auth_stripe/tier_card';
    }

    public function export_for_template(renderer_base $output){
        return $this;
    }
}