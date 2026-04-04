
define(["jquery", "core/templates"], function($, Templates) {
    return {
        init: async function(popup_title, user_fullname, checkboxes) {
            let default_popup = await Templates.renderForPromise("auth_stripe/cancel_premium_popup", {
                popup_title: popup_title
            });
            let inst_popup = $(default_popup.html);

            let extended_popup = await Templates.renderForPromise("auth_stripe/extended_popup", {
                popup_title: "Select extended section",
                checkboxes: checkboxes
            });
            let inst_extended_popup = $(extended_popup.html);

            let popup_open = false;
            let controller = false;

            $(".update-subscription-buttons-wrapper .update-subscription-button").each(function(i, element) {
                let btn = $(element);
                btn.click(async function() {
                    if ("Add Extended" === btn[0].innerText) {
                        if (!popup_open) {
                            popup_open = true;
                            $(document.body).append(inst_extended_popup);

                            inst_extended_popup.find("input[type=checkbox]").each(function() {
                                var _checkboxes$find;
                                let checkbox = $(this);
                                let checkboxId = checkbox.attr("id");
                                // eslint-disable-next-line max-len
                                let isChecked = (null === (_checkboxes$find = checkboxes.find(c => c.id === checkboxId.replace("checkbox_", ""))) || void 0 === _checkboxes$find ? void 0 : _checkboxes$find.checked) || false;
                                checkbox.prop("checked", isChecked);
                            });

                            inst_extended_popup.find(".confirm_btn").click(function() {
                                if (controller) {return;}
                                controller = true;

                                let selectedCheckboxes = [];
                                inst_extended_popup.find("input[type=checkbox]:checked").each(function() {
                                    selectedCheckboxes.push($(this).val());
                                });

                                let selectedCheckboxesString = selectedCheckboxes.join(",");
                                let encodedCheckboxes = encodeURIComponent(selectedCheckboxesString);
                                let baseUrl = btn.attr("data-link");
                                let newUrl = baseUrl + (baseUrl.includes("?") ? "&" : "?") + "list=" + encodedCheckboxes;
                                window.location.href = newUrl;
                            });

                            inst_extended_popup.find(".close_extended_popup_btn").click(function() {
                                popup_open = false;
                                inst_extended_popup.hide();
                            });

                            inst_extended_popup.show();
                        }
                    } else {
                        if (!popup_open) {
                            popup_open = true;
                            $(document.body).append(inst_popup);

                            inst_popup.find(".close_popup_btn").click(function() {
                                popup_open = false;
                                inst_popup.hide();
                                inst_popup.remove();
                            });

                            inst_popup.find(".confirm_btn").click(function() {
                                if (controller) {return;}
                                controller = true;
                                window.location.href = btn.attr("data-link");
                            });

                            $("#update-tier-popup-action").text(element.textContent + ' for user "' + user_fullname + '"');
                            inst_popup.show();
                        }
                    }
                });
            });
        }
    };
});