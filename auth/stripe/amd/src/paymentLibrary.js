/* eslint-disable */
define(['jquery'], function($) {
    return {
        createStripeFunc,
        createPayForm,
        apply_coupon,
        reset_coupon,
    };

    /**
     *
     * @param func
     * @param apiKey
     */
    function createStripeFunc(func, apiKey) {
        var stripe = Stripe(apiKey);
        var elements = stripe.elements();

        var card = elements.create('card', {
            iconStyle: 'solid',
            style: {
                base: {
                    iconColor: '#8898AA',
                    lineHeight: '36px',
                    fontWeight: 300,
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSize: '15px',
                    color: '#201617',
                    '::placeholder': {
                        color: '#8898AA',
                    },
                },
                invalid: {
                    iconColor: '#e63346',
                    color: '#e63346',
                }
            },
            classes: {
                focus: 'is-focused',
                empty: 'is-empty',
            },
        });
        card.mount('#card-element');

        var inputs = document.querySelectorAll('input.field');
        Array.prototype.forEach.call(inputs, function (input) {
            input.addEventListener('focus', function () {
                input.classList.add('is-focused');
            });
            input.addEventListener('blur', function () {
                input.classList.remove('is-focused');
            });
            input.addEventListener('keyup', function () {
                if (input.value.length === 0) {
                    input.classList.add('is-empty');
                } else {
                    input.classList.remove('is-empty');
                }
            });
        });
        func(stripe, card, getData);
    }

    /**
     *
     * @param paymentMethod
     * @param param
     * @returns {{tier: *, name: *, email: *, payment_method: *, username: *}}
     */
    function getData(paymentMethod, param){
        let email = param['useremail'];
        if (!email){
            let email_field = $('#customer_email');
            if (email_field){
                email = email_field.val();
            } else {
                email = null;
            }
        }

        let name = $('input[name="cardholder-name"]')[0].value;
        let price = $('input[name=price]:checked').val();

        return {
            'price': price,
            'email': email,
            'name': name,
            'payment_method': paymentMethod,
        };
    }

    function createPayForm(){
        $('.invalid-feedback').css('display', 'none');
        $('.mform').css('display', 'none');
        $('#stripe-payment-form').css('display', '');
        $('#payment').css('display', '');

        $('#prev-activity-link').click(function(){
            window.history.back();
            window.location.href = M.cfg.wwwroot;

            $('.mform').css('display', '');
            $('#payment').css('display', 'none');
            $('#stripe-payment-form').css('display', 'none');
        });
    }

    /**
     * @param response server response {coupon_description:'', price_info: [ {price_id, price_discount} ]}
     */
    function apply_coupon(response){
        reset_coupon();
        if (response.coupon_description){
            $('.discount_information').html(response.coupon_description);
        }

        for (const price_details of response.price_info){
            $('.plan_container .plan-' + price_details.price_id + ' .price').before(price_details.price_discount);
        }
    }

    function reset_coupon() {
        $('.discount_information').html('');
        $('.discounted-price').remove();
    }
});