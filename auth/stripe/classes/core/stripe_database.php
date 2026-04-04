<?php

namespace auth_stripe\core;

class stripe_database {

    const TABLE_CHECKOUT = 'auth_stripe_checkout';
    const TABLE_CUSTOMER = 'auth_stripe_customer';
    const TABLE_PRODUCT = 'auth_stripe_product';
    const TABLE_PAYMENT_METHOD = 'auth_stripe_payment_method';

    const TABLE_PRICE = 'auth_stripe_price';
    const TABLE_PRICE_DESCRIPTION = 'auth_stripe_price_desc';
    const TABLE_PRICE_TOKEN = 'auth_stripe_price_token';
    const TABLE_PRICE_EMAIL = 'auth_stripe_price_email';

    const TABLE_USER_TIER = 'auth_stripe_user_tier';
    const TABLE_USER_TIER_PRICE = 'auth_stripe_user_tier_price';

    const TABLE_COUPON = 'auth_stripe_coupon';
    const TABLE_PROMOCODE = 'auth_stripe_promocode';
    const TABLE_USER_PROMO_BANNER = 'auth_stripe_user_promobanner';

    const TABLE_INVOICE = 'auth_stripe_invoices';
    const TABLE_SUBSCRIPTION = 'auth_stripe_user_sub';
    const TABLE_TRANSACTION = 'auth_stripe_transaction';

    const TABLE_SEND_INVOICES = 'auth_stripe_send_invoices';
}