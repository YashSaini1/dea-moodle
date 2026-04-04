<?php

namespace auth_stripe;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'../../vendor/autoload.php');

/**
 * Class contains all API logic
 *
 * @package     auth_stripe
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class StripeAPI {

    /**
     * @var \Stripe\StripeClient
     */
    public $stripe;

    public $currency;

    public function __construct(){
        $this->_init_stripe();
    }

    protected function _init_stripe(){
        $config = get_config('auth_stripe');

        // Creating stripe client has api key validation and throws an Exception, if it missing
        $this->stripe = new \Stripe\StripeClient($config->secret_key);
        $this->currency = strtolower(((!empty($config->currency)) ? $config->currency : 'usd'));
    }

    /**
     * @param $name
     *
     * @return \Stripe\Product
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create_product($name): \Stripe\Product{
        return $this->stripe->products->create([
            'name' => $name,
        ]);
    }

    public function delete_product($productid): \Stripe\Product{
        return $this->stripe->products->delete($productid);
    }

    /**
     *
     * Month price
     * 'nickname' => $name."_price",
     * "currency" => "usd",
     * 'unit_amount_decimal' => 100,
     * 'product' => $productid,
     * 'recurring[interval]' => 'month',
     * 'billing_scheme' => 'per_unit
     *
     * @return \Stripe\Price
     */
    public function create_price($params): ?\Stripe\Price{
        if (empty($params)){
            return null;
        }

        if (empty($params['currency'])){
            $params['currency'] = $this->currency;
        }

        return $this->stripe->prices->create($params);
    }

    /**
     * @param $id
     *
     * @return \Stripe\Price
     */
    public function get_price($id){
        return $this->stripe->prices->retrieve($id);
    }

    /**
     * @param $id
     *
     * @return \Stripe\Price
     */
    public function update_price($id, $params){
        return $this->stripe->prices->update($id, $params);
    }

    /**
     * @param $user
     *
     * @return \Stripe\Customer
     */
    public function create_customer($user): \Stripe\Customer{
        return $this->stripe->customers->create([
            'email' => $user->email,
            'name'  => fullname($user),
            'phone' => !empty($user->phone1) ? '+'.$user->phone1 : '',
        ]);
    }

    /**
     * @return \Stripe\Invoice
     */
    public function create_invoice($customerid, $params = []): \Stripe\Invoice{
        $default_params = [
            'customer'          => $customerid,
            'collection_method' => 'charge_automatically',
        ];
        $default_params = array_merge($default_params, $params);
        return $this->stripe->invoices->create($default_params);
    }

    /**
     * @return \Stripe\InvoiceItem
     */
    public function create_invoice_item($params): \Stripe\InvoiceItem{
        return $this->stripe->invoiceItems->create($params);
    }

    public function get_checkout($customerid, $success_url, $cancel_url, $priceid, $name, $desc, $coupon, $set_coupon, $is_onetime, $imageurl, $meta){

        $price = $this->stripe->prices->retrieve($priceid);
        $productId = $price->product;

        $this->stripe->products->update(
            $productId,
            [
                'name' => strip_tags($name),
                'description' => strip_tags($desc),
                'images' => [$imageurl],
            ]
        );

        $session_params = [
            'customer'             => $customerid,
            'payment_method_types' => ['card'],
            'success_url'          => $success_url->out(),
            'cancel_url'           => $cancel_url->out(),
            'mode'                 => ($is_onetime ? 'payment' : 'subscription'),
            'line_items'           => [
                [
                    'price'    => $priceid,
                    'quantity' => 1,
                ],
            ],
            'metadata'             => $meta,
        ];

        if ($is_onetime) {
            $session_params['payment_intent_data'] = [
                'setup_future_usage' => 'off_session',
            ];
        }

        if ($coupon) {
            $session_params['allow_promotion_codes'] = true;
        }

        if (!empty($set_coupon)) {
            $session_params['discounts'] = [
                ['coupon' => $set_coupon]
            ];
        }

        return $this->stripe->checkout->sessions->create($session_params);
    }

    public function get_payment_methods($customerId, $type = 'card') {
        $paymentMethods = $this->stripe->paymentMethods->all([
            'customer' => $customerId,
            'type' => $type,
        ]);

        return $paymentMethods;
    }

    public function get_subscriptions($session){
        return $this->stripe->subscriptions->retrieve($session->subscription);
    }
    public function get_payment_intent($session){
        return $this->stripe->paymentIntents->retrieve($session->payment_intent);
    }

    /**
     * @param array $params
     *
     * @return void
     */
    public function update_customer($customerid, array $params){
        $this->stripe->customers->update($customerid, $params);
    }

    public function delete_customer($customerid, array $params = []){
        $this->stripe->customers->delete($customerid, $params);
    }

    public function void_invoice($invoice_id){
        return $this->stripe->invoices->voidInvoice($invoice_id);
    }

    public function get_invoice($invoice_id){
        return $this->stripe->invoices->retrieve($invoice_id);
    }
    public function get_subscription($subscription_id, $params){
        return $this->stripe->subscriptions->retrieve($subscription_id, $params);
    }
    public function update_product($productid, $params){
        return $this->stripe->products->update($productid, $params);
    }

    public function create_subscription($params){
        return $this->stripe->subscriptions->create($params);
    }
    public function update_subscription($subscriptionid, $params){
        return $this->stripe->subscriptions->update($subscriptionid,$params);
    }
    public function cancel_subscription($subscriptionid, $params = []){
        return $this->stripe->subscriptions->cancel($subscriptionid, $params);
    }

    /**
     * @param $params
     *
     * @return \Stripe\Collection
     */
    public function list_of_subscription($params){
        return $this->stripe->subscriptions->all($params);
    }

    public function payment_methods(){
        return $this->stripe->paymentMethods;
    }
    public function retrieve_payment_method($id, $params = null){
        return $this->stripe->paymentMethods->retrieve($id, $params);
    }
    public function retrieve_coupon($id, $params = null){
        return $this->stripe->coupons->retrieve($id, $params);
    }
    public function retrieve_promocode($id, $params = null){
        return $this->stripe->promotionCodes->retrieve($id, $params);
    }
}