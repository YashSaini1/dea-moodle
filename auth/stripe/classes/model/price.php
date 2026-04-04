<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use auth_stripe\model\price\price_description;
use auth_stripe\model\price\price_email;
use local_sql\core\model\base_object;

/**
 * Moodle price entity
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class price extends base_object {

    static protected string $table = stripe_database::TABLE_PRICE;

    const PERIODS = [
        core::PERIOD_DAY,
        core::PERIOD_MONTH,
        core::PERIOD_YEAR,
        core::PERIOD_ONE_TIME,
    ];

    const PRICE_LIST_URL = '/auth/stripe/admin/list.php';
    const ADD_PRICE_URL = '/auth/stripe/admin/add_price.php';

    const SPECIAL_PRICE_LIST_URL = '/auth/stripe/admin/special_list.php';
    const EDIT_SPECIAL_PRICE_URL = '/auth/stripe/admin/edit_special_price.php';

    public $id;
    public string $plan_name;

    /**
     * @var numeric local product id
     */
    public string $productid;
    public $price = 0;

    /**
     * @var string stripe price id
     */
    public string $priceid;
    public string $period = '-';
    public string $currency;
    public int $max_times;
    public $dependency;
    public bool $is_allow_coupon = false;
    public int $base_price = 1;

    public $enabled = 1;
    public $ab_info = '';
    public $owner = '';

//    public $product;

    protected function _after_init(){
        parent::_after_init();
        if (!empty($this->price)){
            $this->price = $this->price / 100;
        }
    }

    public function delete(){
        parent::delete();
        user_tier_price::delete_by_price($this->id);
        price_description::delete_by_price($this->id);
        price_email::delete_by_price($this->id);
        $this->_delete_token();
    }

    public function save(){
        if (empty($this->id)){
            $this->owner = core::get_userid();
        }

        if (empty($this->currency)){
            $currency = get_config('auth_stripe', 'currency');
            $this->currency = !empty($currency) ? strtolower($currency) : 'usd';
        }
        parent::save();
    }

    /**
     * @param object|false $record
     *
     * @return static|null
     */
    protected static function _create_from_record($record){
        if (!$record){
            return null;
        }

        $product = new static();
        foreach ($record as $field => $value){
            $product->$field = $value;
        }
        $product->price = $product->price / 100;
        return $product;
    }


    /// TODO: Move this block to the price_form_processor
    public static function create_or_update_from_form($fromform, $pos){
        $plan_name = trim($fromform->plan_name[$pos]);
        if (empty($plan_name) && empty($fromform->dependency[$pos])){
            return false;
        }

        if (!empty($fromform->dependency[$pos])){
            $plan_name = '';
        }

        $prices = static::get_product_prices($fromform->productid);
        $price = $prices[$pos] ?? null;
        if (empty($price)){
            $price = new static();
        } else {
            $price = clone $price;
        }

        $price->plan_name = $plan_name;
        $price->ab_info = $fromform->ab_info[$pos];
        $price->enabled = $fromform->enabled[$pos];

        return static::_init_from_form($price, $fromform, $pos);
    }

    /**
     * @param $fromform
     * @param $pos
     *
     * @return static|false
     */
    public static function create_from_form($fromform, $pos){
        $plan_name = trim($fromform->plan_name[$pos]);
        if (empty($plan_name) && empty($fromform->dependency[$pos])){
            return false;
        }

        if (!empty($fromform->dependency[$pos])){
            $plan_name = '';
        }

        $price = new static();
        $price->plan_name = $plan_name;

        return static::_init_from_form($price, $fromform, $pos);
    }

    protected static function _init_from_form($price, $fromform, $pos){
        $period = static::PERIODS[$fromform->period[$pos]];
        if (($period != core::PERIOD_ONE_TIME && empty($fromform->max_times[$pos])) || empty($fromform->price[$pos])){
            return false;
        }

        $price->productid = $fromform->productid;
        $price->price = $fromform->price[$pos] * 100;
        $price->period = $period;

        if ($price->period == core::PERIOD_ONE_TIME){
            $price->max_times = -1;
        } else {
            $price->max_times = !empty($fromform->max_times[$pos]) ? $fromform->max_times[$pos] : -1;
        }

        $price->base_price = !empty($fromform->base_price[$pos]) ?? 1;
        $price->is_allow_coupon = $fromform->is_coupon_allowed[$pos];
        $price->is_checkout = $fromform->is_checkout[$pos];
        return $price;
    }
    /// END price_form_processor block

    //// TODO: Move this block to new price_token model
    /**
     * @return static[]
     */
    public static function get_all_by_token($token){
        global $DB;
        $sql = "SELECT pr.*
                FROM {".stripe_database::TABLE_PRICE_TOKEN."} prt
                JOIN {".static::table()."} pr ON pr.id = prt.priceid
                WHERE prt.token = ?";
        $price_records = $DB->get_records_sql($sql, [$token]);
        $prices = [];
        foreach ($price_records as $price_record){
            $prices[] = static::_create_from_record($price_record);
        }
        return $prices;
    }

    public static function get_token_by_price_id(int $priceid): ?string {
        global $DB;
        $record = $DB->get_record(stripe_database::TABLE_PRICE_TOKEN, ['priceid' => $priceid]);
        return $record ? $record->token : null;
    }

    public function generate_payment_token(){
        $token = $this->_check_token(random_string());
        $this->save_token($token);
        return $token;
    }

    public function save_token($token){
        global $DB;
        $data = [
            'priceid' => $this->id,
            'token'   => $token,
        ];
        $DB->insert_record(stripe_database::TABLE_PRICE_TOKEN, $data);
    }

    protected function _check_token($token){
        global $DB;
        if (!$DB->record_exists(stripe_database::TABLE_PRICE_TOKEN, ['token' => $token])){
            return $token;
        }
        return $this->_check_token(random_string());
    }

    protected function _delete_token(){
        global $DB;
        $DB->delete_records(stripe_database::TABLE_PRICE_TOKEN, ['priceid' => $this->id]);
    }
    /// END price_token block

    /**
     * @return static[]
     */
    public static function get_product_prices($productid){
        static $product_prices = [];
        if (!array_key_exists($productid, $product_prices)){
            $product_prices[$productid] = static::get_all(['productid' => $productid, 'base_price' => 1]);
        }
        return $product_prices[$productid];
    }

    /**
     * @return static[]
     */
    public static function get_all_dependent_prices($conditions = []){
        $conditions['sql'] = 'dependency <> 0';
        $price_records = static::get_records($conditions);
        $prices = [];
        foreach ($price_records as $price_record){
            $prices[$price_record->dependency] = static::_create_from_record($price_record);
        }
        return $prices;
    }

    public function get_stripe_params(){
        $params = [
            'nickname'            => $this->plan_name.' price',
            'unit_amount_decimal' => $this->price,
            'product'             => product::get_by_id($this->productid)->productid,
            'billing_scheme'      => 'per_unit', // set reccuring price for subscription (needs for stripe)
        ];

        if (core::is_period_price($this->period)){
            $params['recurring[interval]'] = $this->period;
        }

        return $params;
    }

    public function get_dependent_prices(): array{
        return static::get_all(['dependency' => $this->id]);
    }

    public function get_price_dependency(): ?price{
        $dependent_prices = $this->get_dependent_prices();
        foreach ($dependent_prices as $dependent_price){
            if (core::is_period_price($dependent_price->period)){
                return $dependent_price;
            }
        }
        return null;
    }

    public function get_full_amount(){
        $price_sum = $this->price;
        if (core::is_period_price($this->period)){
            $price_sum *= $this->max_times;
        }
        $dependent_prices = $this->get_dependent_prices();
        foreach ($dependent_prices as $dependent_price){
            $price_sum += $dependent_price->get_full_amount();
        }
        return $price_sum;
    }
}