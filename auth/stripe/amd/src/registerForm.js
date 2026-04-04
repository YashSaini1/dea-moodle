/* eslint-disable */
define(['jquery', 'auth_stripe/paymentLibrary', 'core/ajax'], function ($, libPayment, ajax) {
    return {
        init: function (params) {
            libPayment.createPayForm();
            libPayment.createStripeFunc(thenActionSubmitSubForm, params.publish_key);
            let preloader = $('.basic-preload');
            let next_btn = $('#next-activity-link');
            let applied_coupon = false;

            function thenActionSubmitSubForm(stripe, card, getData) {
                document.querySelector('#stripe-payment-form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    let price = $('input[name=price]:checked').val();
                    if (!price){
                        let error = document.createElement('div');
                        error.classList.add('alert', 'alert-danger','w-100','sql-alert');
                        error.innerHTML = 'Select payment plan!';
                        $('#stripe-payment-form_links').before(error);
                        return;
                    }

                    $('.sql-alert').remove();
                    let terms = $('#agreeterms');
                    if (!terms[0].checked){
                        terms.click(() => {
                            if (terms.has('invalid-check')){
                                $('#agreeterms').removeClass('invalid-check');
                                $('.invalid-checkbox').css('display', 'none');
                            }
                        });
                        $('#agreeterms').addClass('invalid-check');
                        $('.invalid-checkbox').css('display', '');
                        return;
                    }

                    preloader.css('display', 'flex');
                    $('#next-activity-link .lds-default').css('display', 'inline-block');
                    $('#next-activity-link span').css('display', 'none');

                    stripe.createPaymentMethod({
                        type: 'card',
                        card: card,
                        // eslint-disable-next-line camelcase
                        billing_details: {
                            name: $('input[name="cardholder-name"]')[0].value,
                        },
                    }).then((result) => {
                        if (!result.paymentMethod){
                            next_btn.removeClass('disabled');
                            preloader.css('display', 'none');
                            let error = document.createElement('div');
                            error.classList.add('alert', 'alert-danger','w-100','sql-alert');
                            error.innerHTML = result.error.message;
                            $('#stripe-payment-form_links').before(error);
                            return;
                        }

                        let data = getData(result.paymentMethod.id, params);
                        if (params['coupon_allowed'] && applied_coupon !== false){
                            data['coupon'] = applied_coupon;
                        }

                        $.ajax({
                            type: 'POST',
                            url: M.cfg.wwwroot + '/auth/stripe/payment/stripe_pay.php',
                            data,
                        }).done((request) => {
                            if (!request){
                                preloader.css('display', 'none');
                                next_btn.removeClass('disabled');
                                alert(params.fail_str);
                                return;
                            }

                            let data = {};
                            if (typeof request === 'string'){
                                data = JSON.parse(request);
                            } else {
                                data = request;
                            }

                            let status = data.status;
                            if (status === 'error'){
                                preloader.css('display', 'none');
                                next_btn.removeClass('disabled');
                                let message = '';
                                if (data.message === 'logged'){
                                    message = params.already_logged_str;
                                } else if (data.message === 'manyrequest'){
                                    message = params.many_request;
                                } else {
                                    message = data.message;
                                }
                                alert(message);
                                return;
                            }

                            // if 'ok'
                            window.onbeforeunload = null;
                            if (data.redirect_url){
                                window.location.href = data.redirect_url;
                            }

                            $('.mform').submit();
                        }).fail((request) => {
                            preloader.css('display', 'none');
                            next_btn.removeClass('disabled');
                            alert(params.fail_str);
                        });
                    });
                });
            }

            const backBtn = document.querySelector('#prev-activity-link');
            if (backBtn){
                const steps = document.querySelectorAll('.steps-list li');

                backBtn.addEventListener('click', function () {
                    steps[0].classList.remove('complete');
                    steps[1].classList.remove('active');
                });
            }

            let coupon_validate_button = $('#coupon-button');
            if (!$.isEmptyObject(coupon_validate_button)){
                let coupon = $('#coupon');
                let price_ids = [];
                $('.plan_container').each((i, element) => {
                    element = $(element);
                    price_ids.push(element.find('input[name=price]').val());
                });

                coupon_validate_button.on('click', (e) => {
                    e.preventDefault();
                    if (applied_coupon !== false){
                        coupon_validate_button.addClass('btn-blue');
                        coupon_validate_button.removeClass('btn-secondary');
                        coupon_validate_button.text(params['coupon:apply']);
                        applied_coupon = false;
                        coupon.removeAttr('disabled');
                        libPayment.reset_coupon();
                        return;
                    }

                    let coupon_value = coupon.val();
                    if (coupon_value === ''){
                        alert('Empty coupon!');
                        return;
                    }
                    coupon_validate_button.attr('disabled', 'disabled');

                    var requests = ajax.call([{
                        methodname: 'auth_stripe_validate_coupon',
                        args: {
                            coupon: coupon_value,
                            prices: price_ids,
                        },
                    }]);

                    requests[0]
                        .done(function (response) {
                            if (response.valid !== true){
                                coupon_validate_button.removeAttr('disabled');
                                alert('Error: ' + response.error);
                                return;
                            }

                            /// TBD
                            libPayment.apply_coupon(response);
                            coupon_validate_button.removeClass('btn-blue');
                            coupon_validate_button.addClass('btn-secondary');
                            coupon_validate_button.text(params['coupon:remove']);
                            coupon_validate_button.blur();

                            coupon.attr('disabled', 'disabled');
                            applied_coupon = coupon_value;

                            coupon_validate_button.removeAttr('disabled');
                        })
                        .fail(() => {
                            alert('Something went wrong!');
                            coupon_validate_button.removeAttr('disabled');
                        });
                });

                if (params['applied_coupon'] && params['applied_coupon'] !== ''){
                    coupon.val(params['applied_coupon']);
                    coupon_validate_button.click();
                }
            }
        }
    };
});
