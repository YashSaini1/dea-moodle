define(['jquery', 'core/templates'], function ($, Templates) {
    return {
        init: async (cancel_tier_url, popup_title) => {
            let popup_result = await Templates.renderForPromise('auth_stripe/cancel_premium_popup', {
                popup_title: popup_title,
            });
            let popup = $(popup_result.html);
            let popup_appended = false;
            let current_tier = false;

            let init_popup = () => {
                popup.find('.close_popup_btn').each((i, element) => {
                    $(element).click(() => {
                        current_tier = false;
                        popup.hide();
                    });
                });
                popup.find('.confirm_btn').each((i, element) => {
                    $(element).click(() => {
                        if (!current_tier){
                            alert('No available tier');
                            return;
                        }
                        window.location.href = cancel_tier_url + '?tier=' + current_tier;
                    });
                });
            };

            $('.tier_card_button.cancel_tier').each((i, element) => {
                let btn = $(element);
                btn.click(() => {
                    if (!popup_appended){
                        $(document.body).append(popup);
                        popup_appended = true;
                        init_popup(popup);
                    }
                    current_tier = btn.attr('data-id');
                    popup.show();
                });
            });
        },
    };
});