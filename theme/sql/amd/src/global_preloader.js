define(['jquery'], function ($) {
    return {
        init: async () => {
            $(document).ready(() => {
                document.body.querySelector('.basic-preload').style.display = 'none';
                document.body.style.pointerEvents = 'initial';
            });
        }
    };
});