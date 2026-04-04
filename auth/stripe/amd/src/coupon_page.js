define(['jquery', 'core/ajax'], function ($, ajax) {
    return {
        init: (strings, type) => {
            const init_callback = () => {
                let disable_buttons = document.querySelectorAll('.disable_btn');
                disable_buttons.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        let entity_id = button.getAttribute('data-id');
                        let new_state = button.getAttribute('data-newstate');

                        var requests;
                        if(type === 'coupon'){
                            requests = ajax.call([{
                                methodname: 'auth_stripe_update_coupon',
                                args: {
                                    couponid: entity_id,
                                    state: new_state,
                                },
                            }]);
                        } else if (type === 'promocode'){
                            requests = ajax.call([{
                                methodname: 'auth_stripe_update_promocode',
                                args: {
                                    promocodeid: entity_id,
                                    state: new_state,
                                },
                            }]);
                        }

                        requests[0]
                            .done(function (response) {
                                if (response.result === true){
                                    window.location.reload();
                                } else {
                                    if (new_state === 1){
                                        btn_text = strings.disable;
                                    } else {
                                        btn_text = strings.enable;
                                    }
                                    button.innerText = btn_text;
                                    alert('Error: ' + response.error);
                                }
                            })
                            .fail(() => {
                                alert('Something went wrong!');
                                let btn_text = '';
                                if (new_state === 1){
                                    btn_text = strings.disable;
                                } else {
                                    btn_text = strings.enable;
                                }
                                button.innerText = btn_text;
                            });
                        button.innerText = strings.updating;
                    });
                });
            };
            $(document).ready(init_callback);
        },
    };
});