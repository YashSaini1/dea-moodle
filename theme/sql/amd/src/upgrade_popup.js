define("theme_sql/upgrade_popup", ["jquery"], (function($) {
    return {
        init: async() => {
            let closePopup = document.querySelector(".close_popup img");
            let upgradePopup = document.querySelector(".upgrade_popup_wrapper");
            let upgradeToPremiumBtns = document.querySelectorAll(".upgrade_plan");

            $(document).ready(() => {
                upgradeToPremiumBtns.forEach((button) => {
                    if (button) {
                        button.addEventListener("click", () => {
                            if (!button.innerHTML.trim().includes("Onboarding")) {
                                upgradePopup.classList.add("active");
                            }
                        });
                    }
                });

                if (closePopup) {
                    closePopup.addEventListener("click", () => {
                        upgradePopup.classList.remove("active");
                    });
                }
            });
        },

        showPopup: async() => {
            document.querySelector(".upgrade_popup_wrapper").classList.add("active");
        }
    };
}));
