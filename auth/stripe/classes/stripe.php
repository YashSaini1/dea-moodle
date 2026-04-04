<?php

namespace auth_stripe;

defined('MOODLE_INTERNAL') || die;

use auth_stripe\core\stripe_database;
use auth_stripe\model\customer;
use auth_stripe\model\dto\stripe_payment_info;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\subscription\tier_processor;
use Exception;
use stdClass;
use Stripe\Exception\ApiErrorException;

/**
 * Class which process all custom logic with stripe (make logs, update user subscriptions, etc.)
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stripe {

    protected static string $coupon_stripeid = '';

    /**
     * @var StripeAPI
     */
    protected $_stripe;

    // Stripe stripe customer id
    // Store only in auth_stripe_customer table
    // ! Make sure tha this is not equal $this->_customer->id !
    protected $_customerid;

    // local customer record
    protected $_customer;

    // transaction record. In local DB stored only last invoice transaction
    protected $_transaction;

    // moodle user. Can be without id field (if user signing up)
    protected $_user;

    // local subscription record
    protected $_user_subscription;

    /**
     * @param stdClass $user
     * @param bool     $init
     **/
    public function __construct($user = null, $init = true){
        global $USER;

        if (empty($user)){
            $user = $USER;
        }

        $this->_user = $user;

        $this->_init_stripe();
        if ($init){
            $this->_init_customer();
        }
    }

    /**
     * Init stripe
     */
    protected function _init_stripe(){
        $this->_stripe = new StripeAPI();
    }

    /**
     * Initialize customer values
     */
    public function _init_customer(){
        global $DB;
        if (empty($this->_user)) return;

        $this->_customer = customer::get_by_userid($this->_user->id);
        if (empty($this->_customer)) return;

        $this->_customerid = $this->_customer->customerid ?? -1;
        $this->_user_subscription = $DB->get_record(stripe_database::TABLE_SUBSCRIPTION, array('customer_id' => $this->_customer->id));
        if (empty($this->_user_subscription)) return;

        // we store only the last transaction in local DB
        $this->_transaction = $DB->get_record(stripe_database::TABLE_TRANSACTION, array('id' => $this->_user_subscription->transaction_id));
    }

    /**
     * @param string $page
     */
    public static function set_page($page){
        global $SESSION;
        $SESSION->stripe_payment_page = $page;
    }

    /** */
    public static function clear_page(){
        global $SESSION;
        unset($SESSION->stripe_payment_page);
    }

    /**
     * @return string
     */
    public static function get_page(){
        global $SESSION;
        return $SESSION->stripe_payment_page ?? null;
    }

    /**
     * @param string $coupon_stripeid
     */
    public static function set_coupon($coupon_stripeid){
        static::$coupon_stripeid = $coupon_stripeid;
    }

    /**
     * @return string
     */
    public static function get_coupon(){
        return static::$coupon_stripeid;
    }

    /**
     * Create stripe checkout.
     *
     * @param \stdClass $user
     * @param           $priceid
     * @param string    $type
     *
     * @return string stripe payment page url
     */
    public function create_checkout(\stdClass $user, $priceid, string $type = 'reg'): string{
        throw new \moodle_exception('You cannot do this');
    }

    /**
     * Create new product in stripe and moodle
     *
     * @param product $product
     */
    public function create_product(product $product){
        $stripe_product = $this->_stripe->create_product($product->name);
        $product->productid = $stripe_product->id;
        $product->save();
        return $product;
    }

    /**
     * Update stripe and moodle product name/price.
     *
     * @param product $product - product record from db
     * @param product $newproduct
     *
     * @return bool
     */
    public function update_product($product, $newproduct){
        if (empty($product) || empty($newproduct)){
            return false;
        }

        $data = $product->compare($newproduct);
        if (empty($data)){
            return false;
        }

        $this->update_product_by_data($newproduct, $data);
        return true;
    }

    /**
     * Simplify update data
     *
     * @param product $newproduct
     * @param array   $updated_data - supported keys: 'name' - new product name, 'price' - new product price
     */
    public function update_product_by_data(product $newproduct, $updated_data){
        $name = $updated_data['name'] ?? null;
        if (!empty($name)){
            $this->_stripe->update_product($newproduct->productid, ['name' => $name]);
        }
        $newproduct->save();
    }

    /**
     * @param product $product
     */
    public function delete_product(product $product){
        //TODO: delete product from stripe
        $product->delete();
    }

    /**
     * Month price
     * 'nickname' => $name."_price",
     * "currency" => "usd",
     * 'unit_amount_decimal' => 100,
     * 'product' => $productid,
     * 'recurring[interval]' => 'month',
     * 'billing_scheme' => 'per_unit
     *
     * @param price $moodle_price
     *
     * @return \Stripe\Price
     */
    public function create_price(price $moodle_price): \Stripe\Price{
        $params = $moodle_price->get_stripe_params();
        $stripe_price = $this->_stripe->create_price($params);
        $moodle_price->priceid = $stripe_price->id;
        $moodle_price->save();
        return $stripe_price;
    }

    public function update_price(price $price, price $newprice){
        $data = $price->compare($newprice);
        if (empty($data)){
            return;
        }

        if (!empty($data['price']) || !empty($data['period'])){
            // If we change $price->price or period, we need to deactivate current and create new price
            $this->delete_price($newprice, true);
            $this->create_price($newprice);
            // return here because create price call price::save() method
            return;
        } elseif (!empty($data['plan_name'])) {
            // Stripe allows only change price name
            $params = ['nickname' => $price->plan_name.' price'];
            $this->_stripe->update_price($newprice->priceid, $params);
        }
        $newprice->save();
    }

    /**
     * @param price $price
     */
    public function delete_price(price $price, $stripe_only = false){
        $params = ['active' => false];
        $this->_stripe->update_price($price->priceid, $params);
        if (!$stripe_only){
            $price->delete();
        }
    }

    /**
     * @param string $priceid
     *
     * @return \Stripe\Price
     */
    public function get_price($priceid){
        return $this->_stripe->get_price($priceid);
    }

    /**
     * @param string $subscriptionid
     * @param array $params
     *
     * @return \Stripe\Subscription
     */
    public function get_subscription($subscriptionid, $params = []){
        return $this->_stripe->get_subscription($subscriptionid, $params);
    }

    /**
     * Create moodle and stripe customer.
     *
     * @param stdClass $user
     *
     * @return int local customer id
     */
    public function create_customer($user){
        // TODO replace create_customer_with_db() func here
        return $this->create_customer_with_db($user);
    }

    /**
     * @param stdClass $user
     *
     * @return int local customer id
     */
    public function create_customer_with_db($user): string{
        $customer = customer::get(['email' => $user->email]);
        if (empty($customer)){
            $stripe_customer = $this->_stripe->create_customer($user);
            $customer = customer::create([
                'userid'        => $user->id,
                'username'      => $user->username,
                'email'         => $user->email,
                'customerid'    => $stripe_customer->id,
                'status_access' => 'active',
            ]);
        }

        if (empty($customer->customerid)){
            $stripe_customer = $this->_stripe->create_customer($user);
            $customer->customerid = $stripe_customer->id;
            $customer->save();
        }

        $this->_customer = $customer;
        $this->_customerid = $customer->customerid;
        return $this->_customer->id;
    }

    /**
     * Update stripe customer
     *
     * @param array $params
     */
    public function update_customer(array $params){
        // TODO : update local customer data
        $this->_stripe->update_customer($this->_customerid, $params);
    }

    /**
     * Delete stripe customer and all its data
     *
     * @param array $params
     */
    public function delete_customer(array $params = []){
        $this->_stripe->delete_customer($this->_customerid, $params);
    }

    /**
     * Update stripe customer email
     *
     * @param string $email
     */
    public function update_customer_email($email){
        $this->update_customer(array('email' => $email));
    }

    /**
     * Check local customerid.
     * Throw new Exception, if there is empty customer
     *
     * @param int $customerid
     */
    protected function _check_customer($customerid = null){
        if (empty($this->_customer) && empty($customerid)){
            throw new \moodle_exception('Client is not exist');
        }
    }

    /**
     * Create or update payment method from stripe & moodle
     *
     * @param string $payment_method_id - stripe payment id from FE.
     *
     * @return bool
     */
    public function update_payment_method($payment_method_id){
        global $DB;
        $paymentMethods = $this->_stripe->payment_methods();
        $old_method_record = $DB->get_record(stripe_database::TABLE_PAYMENT_METHOD, array('customer_id' => $this->_customer->id));
        if (!empty($old_method_record) && $old_method_record->payment_method_id == $payment_method_id){
            return true;
        }
        // if attach fails, this line throws new Exception
        $attach = $paymentMethods->attach($payment_method_id, ['customer' => $this->_customerid]);

        if (!empty($old_method_record)){
            $old_method = $paymentMethods->retrieve($old_method_record->payment_method_id);
            $this->delete_payment_method($old_method);
        }

        $this->update_customer([
            'invoice_settings' => [
                'default_payment_method' => $payment_method_id,
            ],
        ]);

        $newpayment_method = $paymentMethods->retrieve($payment_method_id);

        $payment_method = new stdClass();
        $payment_method->payment_method_id = $payment_method_id;
        $payment_method->last4 = $newpayment_method->card->last4;
        $payment_method->exp_month = $newpayment_method->card->exp_month;
        $payment_method->exp_year = $newpayment_method->card->exp_year;
        $payment_method->type = $newpayment_method->card->brand;
        $payment_method->client_name = $newpayment_method->billing_details->name;
        $payment_method->customer_id = $this->_customer->id;
        return $DB->insert_record(stripe_database::TABLE_PAYMENT_METHOD, $payment_method);
    }

    /**
     * Delete payment method from stripe & moodle
     *
     * @param \Stripe\PaymentMethod $stripe_payment_method
     *
     * @return bool
     */
    public function delete_payment_method(\Stripe\PaymentMethod $stripe_payment_method){
        global $DB;
        if (empty($stripe_payment_method)){
            return false;
        }

        try {
            $deattach = $stripe_payment_method->detach();
        } catch (ApiErrorException $e){
            if ($this->_customer->id){
                return $DB->delete_records(stripe_database::TABLE_PAYMENT_METHOD, array('customer_id' => $this->_customer->id));
            }
            return false;
        }
        if (!$deattach->customer && $this->_customer->id){
            return $DB->delete_records(stripe_database::TABLE_PAYMENT_METHOD, array('customer_id' => $this->_customer->id));
        }
        return false;
    }

    /**
     * Create invoice in moodle.
     * Do not create stripe invoice, because it will be automatically created with subscription
     *
     * @param \Stripe\Invoice $invoice_stripe
     *
     * @return stdClass moodle invoice record
     */
    public function _create_moodle_invoice($invoice_stripe){
        global $DB;
        $this->_check_customer();

        if (empty($invoice_stripe)){
            throw new \moodle_exception('Empty stripe invoice object');
        }

        $invoice = $this->_parse_invoice_record_from_stripe_invoice($invoice_stripe);
        $invoice->id = $DB->insert_record(stripe_database::TABLE_INVOICE, $invoice);
        return $invoice;
    }

    /**
     * Update invoice in moodle.
     *
     * @param \Stripe\Invoice $invoice_stripe
     *
     * @return false|stdClass
     */
    public function _update_moodle_invoice($invoice_stripe){
        global $DB;
        if (empty($invoice_stripe)){
            throw new \moodle_exception('Empty stripe invoice object');
        }
        $this->_check_customer();

        $invoice = $DB->get_record(stripe_database::TABLE_INVOICE, array('customer_id' => $this->_customer->id));
        if (!$invoice){
            return false;
        }

        $new_invoice = $this->_parse_invoice_record_from_stripe_invoice($invoice_stripe, $invoice);
        $result = static::has_data_changed($invoice, $new_invoice);
        if (!empty($result)){
            $result['id'] = $invoice->id;
            $DB->update_record(stripe_database::TABLE_INVOICE, (object)$result);
        }

        return $new_invoice;
    }

    /**
     * @param \Stripe\Invoice $invoice_stripe
     *
     * @return object moodle invoice record
     */
    protected function _parse_invoice_record_from_stripe_invoice($invoice_stripe, $base_invoice = null){
        $invoice = $base_invoice ?? new stdClass();
        $invoice->invoice_id = $invoice_stripe->id;
        $invoice->customer_id = $this->_customer->id; // moodleid

        $invoice->number = $invoice_stripe->number;
        $invoice->price = $invoice_stripe->total;
        $invoice->status = $invoice_stripe->status;
        $invoice->url = $invoice_stripe->invoice_pdf;

        $invoice->created_at = $invoice_stripe->created;
        $invoice->expire_at = $invoice_stripe->created;
        return $invoice;
    }

    /**
     * Void current customer invoices
     *
     * @return bool
     */
    protected function _void_invoices(){
        global $DB;
        $invoices = $DB->get_records(stripe_database::TABLE_INVOICE, array('customer_id' => $this->_customer->id));
        foreach ($invoices as $invoice){
            if ($invoice->status != 'open'){
                continue;
            }

            if ($this->_stripe->void_invoice($invoice->invoice_id)->status != 'void'){
                return false;
            }
        }
        return true;
    }

    public function retrieve_invoice($invoiceid){
        return $this->_stripe->get_invoice($invoiceid);
    }

    /**
     * Upcoming invoice is NOT real invoice.
     * It needs to preview a real invoice and cannot be paid.
     *
     * @param array $invoice_params
     *
     * @return stdClass|\Stripe\Invoice
     */
    public function get_upcoming_invoice($invoice_params){
        try {
            $invoice = \Stripe\Invoice::upcoming($invoice_params);
        } catch (\Stripe\Exception\ApiErrorException $e){
            return (new stdClass());
        }
        return $invoice;
    }

    public function buy_product(product $product, price $price, $additional_params = []): bool{
	global $USER;
        \auth_stripe\core::track_time('Full payment '.$product->name.' priceid '.$price->id);
        \auth_stripe\core::track_time('Buy product '.$product->name.' price '.$price->price);
        $tier_data = [];
        if ($price->period == core::PERIOD_ONE_TIME){
            $invoice = $this->create_one_time_checkout($price, $product, $additional_params['one_time'] ?? []);
            $stripe_info_dto = new stripe_payment_info(null, $invoice);
        } else {
            $stripe_subscription = $this->create_subscription($price, $product, $additional_params['subscription'] ?? []);
            if (!$stripe_subscription){
                return false;
            }
            $tier_data = [
                'current_period_start' => $stripe_subscription->current_period_start,
                'current_period_end'   => $stripe_subscription->current_period_end,
            ];
            $stripe_info_dto = new stripe_payment_info($stripe_subscription, $stripe_subscription->latest_invoice);
        }
        \auth_stripe\core::track_time('Buy product '.$product->name.' price '.$price->price, 1);

        $result = $this->_apply_dependent_price($product, $price);

        $subscription_processor = new subscription_processor($this->_user, $product, $this);
        $subscription_processor->apply($price, $tier_data);

        if (core::is_trigger_events()){
            $eventdata = ['userid' => $this->_user->id, 'context' => \context_system::instance(), 'user_tiers' => $this->_user->tier];
            $event = \auth_stripe\event\payment_created::create_by_product($product, $price, $eventdata, $stripe_info_dto);
            $event->trigger();
        }
	tier_processor::init_user_tiers($USER, true, true);
        \auth_stripe\core::track_time('Full payment '.$product->name.' priceid '.$price->id, 1);
        return $result;
    }

    protected function _apply_dependent_price(product $product, price $price, bool $is_checkout = false): bool{
        $dependent_prices = $price->get_dependent_prices();
        if (empty($dependent_prices)){
            return true;
        }

        $result = true;
        foreach ($dependent_prices as $dependent_price){
            $result = $result && ($is_checkout
                    ? $this->checkout_processing($product, $dependent_price)
                    : $this->buy_product($product, $dependent_price));
        }
        return $result;
    }

    public function checkout_processing(product $product, price $price): bool {
        $tier_data = [
            'current_period_start' => time(),
            'current_period_end'   => 0,
        ];

        $result = $this->_apply_dependent_price($product, $price, true);

        $subscription_processor = new subscription_processor($this->_user, $product, $this);
        $subscription_processor->apply($price, $tier_data);

        return $result;
    }

    /**
     * Fully create user subscription
     *
     * @param price   $price
     * @param product $product
     * @param array   $additional_params - [
     *          @var int $billing_time 'billing_time', time the first bill
     * ]
     *
     * @return bool|\Stripe\Subscription
     */
    public function create_subscription(price $price, product $product, $additional_params = []){
        $subscription_params = [
            'customer' => $this->_customerid,
            'items'    => [
                [
                    'price'    => $price->priceid,
                    'quantity' => 1,
                ],
            ],
            'expand'   => ['latest_invoice', 'plan.product'],
        ];

        $coupon = static::get_coupon();
        if (!empty($coupon)){
            $subscription_params['coupon'] = $coupon;
        }

        //// START DELAYED BLOCK
        if (!empty($additional_params['billing_time'])){
            $subscription_params['trial_end'] = $additional_params['billing_time'];
        } elseif (!empty($price->dependency)) {
            $subscription_params['trial_end'] = strtotime('+1 '.$price->period);
        }

        if ($price->max_times > 0){
            $base_time = $subscription_params['trial_end'] ?? time();
            $subscription_params['cancel_at'] = strtotime('+'.$price->max_times.' '.$price->period, $base_time);
        }
        //// END DELAYED BLOCK

        $stripe_subscription = $this->_stripe->create_subscription($subscription_params);
        if ($stripe_subscription->status != 'active' && $stripe_subscription->status != 'trialing'){
            $this->cancel_subscription($stripe_subscription->id);
            throw new \moodle_exception('Not active status subscription');
        }

        if (!$this->_void_invoices()){
            return false;
        }

        if (!empty($this->_transaction)){
            return $this->update_subscription($stripe_subscription, $product);
        }

        $this->_create_moodle_invoice($stripe_subscription->latest_invoice);
        $this->_create_transaction($stripe_subscription);

        $moodle_subscription = [
            'transaction_id' => $this->_transaction->id,
            'product_id'     => $product->id,
            'customer_id'    => $this->_customer->id,
        ];

        if (!$this->_create_user_subscription($moodle_subscription)){
            throw new \moodle_exception('Not insert user_plan');
        }

        return $stripe_subscription;
    }

    public function create_one_time_checkout(price $price, product $product, $additional_params = []){
        if (!empty($price->dependency)){
            // Do not create 2 checkouts in a row
            return;
        }

        $invoice_params = [];
//        $coupon = static::get_coupon();
//        if (!empty($coupon)){
//            $invoice_params['discount'] = [
//                'coupon' => $coupon,
//            ];
//        }
        $invoice = $this->_stripe->create_invoice($this->_customerid, $invoice_params);
        $item_params = [
            'customer' => $this->_customerid,
            'invoice'  => $invoice->id,
            'price'    => $price->priceid,
        ];
        $this->_stripe->create_invoice_item($item_params);
        $invoice->pay();
        return $invoice;
    }

    /**
     * Fully update user subscription (change its product)
     *
     * @param \Stripe\Subscription $newsubsctiption
     *
     * @return false|\Stripe\Subscription
     */
    public function update_subscription($newsubsctiption, product $product = null){
        if (empty($this->_transaction->subscription_id)){
            throw new \moodle_exception('Not existing subscription');
        }

//        $updated_subscription = $this->_update_stripe_subscription($newsubsctiption);
//        if (empty($updated_subscription)){
//            throw new \moodle_exception('Cant update subscription');
//        }
//
//        if (empty($this->_user_subscription)){
//            throw new \moodle_exception('Client not exist');
//        }

        $this->_update_user_subscription($product->id);

        if (empty($this->_transaction)){
            throw new \moodle_exception('Transaction is not exists. Cannot update it');
        }

        $upcoming_invoice = $this->get_upcoming_invoice(['customer' => $this->_customerid]);
        $this->_update_transaction_by_data($upcoming_invoice->total ?? 0, $newsubsctiption->id, $newsubsctiption->items->data[0]->id);

        if (is_object($newsubsctiption->latest_invoice)){
            $invoice = $newsubsctiption->latest_invoice;
        } elseif (is_string($newsubsctiption->latest_invoice)) {
            $invoice = $this->_stripe->get_invoice($newsubsctiption->latest_invoice);
        }

        if ($this->_update_moodle_invoice($invoice)){
            return $newsubsctiption;
        }

        return false;
    }

    /**
     * Create moodle subscription record
     *
     * @param array $moodle_subscription moodle record data
     *
     * @return bool
     */
    protected function _create_user_subscription($moodle_subscription){
        global $DB;
        $moodle_subscription['id'] = $DB->insert_record(stripe_database::TABLE_SUBSCRIPTION, $moodle_subscription);
        $this->_user_subscription = (object)$moodle_subscription;
        return !empty($this->_user_subscription->id);
    }

    /**
     * Update moodle subscription record
     *
     * @param int $new_productid
     *
     * @return bool
     */
    protected function _update_user_subscription($new_productid){
        global $DB;
        $data_changed = [];
        if ($this->_user_subscription->product_id != $new_productid && !empty($new_productid)){
            $data_changed['product_id'] = $new_productid;
        }

        if (empty($data_changed)){
            return false;
        }

        $data_changed['id'] = $this->_user_subscription->id;
        return $DB->update_record(stripe_database::TABLE_SUBSCRIPTION, (object)$data_changed);
    }

    /**
     * Update stripe subscription record.
     *
     * @param array|\Stripe\Subscription $new_data - data to update subscription
     *
     * @return false|\Stripe\Subscription
     */
    public function _update_stripe_subscription($new_data){
        if (is_array($new_data)){
            try {
                $updated_subscription = $this->_stripe->update_subscription($this->_transaction->subscription_id, $new_data);
            } catch (Exception $e){
                return false;
            }
            return $updated_subscription;
        }

        $updated_subscription = $new_data;
        if ($new_data->status == 'past_due'){
            $updated_subscription = $this->cancel_subscription($new_data->id);
        }
        return $updated_subscription;
    }

    /**
     * @param price   $price
     * @param product $product
     *
     * @return \Stripe\Subscription|null
     */
    public function get_stripe_subscription(price $price, product $product){
        $subscriptions = $this->list_of_subscriptions();
        foreach ($subscriptions as $subscription){
            if ($subscription->status != 'active' && $subscription->status != 'trialing'){
                continue;
            }

            foreach ($subscription->items->data as $item){
                if ($item->price->id == $price->priceid || $item->price->product == $product->productid){
                    return $subscription;
                }
            }
        }
        return null;
    }

    public function list_of_subscriptions(){
        return $this->_stripe->list_of_subscription([
            'customer' => $this->_customerid,
        ]);
    }

    public function cancel_subscription($subscriptionid, $params = []){
        return $this->_stripe->cancel_subscription($subscriptionid, $params);
    }

    /**
     * Update stripe subscription.
     */
    public function update_stripe_subscription($subscriptionid, $params){
        $this->_stripe->update_subscription($subscriptionid, $params);
    }

    /**
     * @return \Stripe\Coupon
     */
    public function retrieve_coupon($couponid, $params = null){
        return $this->_stripe->retrieve_coupon($couponid, $params);
    }
    /**
     * @return \Stripe\PromotionCode
     */
    public function retrieve_promocode($promocodeid, $params = null){
        return $this->_stripe->retrieve_promocode($promocodeid, $params);
    }

    /// This is local transaction, not stripe. This is log records, that can help to solve issues with stripe payment.

    /**
     * Create transaction record
     *
     * @param \Stripe\Subscription $stripe_subscription
     */
    protected function _create_transaction($stripe_subscription){
        global $DB;
        $this->_transaction = new \stdClass();
        $this->_transaction->subscription_id = $stripe_subscription->id;
        if ($stripe_subscription->status == 'trialing'){
            $upcoming = $this->get_upcoming_invoice(['customer' => $this->_customerid]);
            $this->_transaction->price = $upcoming->total ?? 0;
        } else {
            $this->_transaction->price = $stripe_subscription->latest_invoice->total;
        }

        $this->_transaction->subscription_item_id = $stripe_subscription->items->data[0]->id;
        $this->_transaction->id = $DB->insert_record(stripe_database::TABLE_TRANSACTION, $this->_transaction);
    }

    /**
     * Update moodle transaction record (if data has changed).
     *
     * @param object|array $new_transaction new record
     *
     * @return bool
     */
    protected function _update_transaction($new_transaction){
        global $DB;
        $new_data = static::has_data_changed($this->_transaction, $new_transaction);
        if (empty($new_data)) return false;

        $new_data['id'] = $this->_transaction->id;
        return $DB->update_record(stripe_database::TABLE_TRANSACTION, (object)$new_data);
    }

    /**
     * Update local transaction by stripe subscription.
     *
     * @param \Stripe\Subscription $newsubsctiption
     *
     * @return bool
     */
    protected function _update_transaction_by_data($price, $subscriptionid, $itemid){
        if (empty($invoice)) return false;

        $new_transaction = array();
        $new_transaction['price'] = $price;
        $new_transaction['subscription_id'] = $subscriptionid;
        $new_transaction['subscription_item_id'] = $itemid;
        return $this->_update_transaction($new_transaction);
    }

    /**
     * Check 2 data objects and return their differences
     *
     * @param array|object $data1
     * @param array|object $data2
     *
     * @return array differences
     */
    public static function has_data_changed($data1, $data2){
        $data1 = (array)$data1;
        $data2 = (array)$data2;
        $result = [];
        foreach ($data1 as $key => $value){
            if ($data2[$key] != $value){
                $result[$key] = $data2;
            }
        }
        return $result;
    }
}
