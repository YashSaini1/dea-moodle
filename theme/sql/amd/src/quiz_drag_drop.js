define(['jquery'], function ($) {
    return {
        init: () => {
            $(document).ready(() => {
                const initSettingsDragDropIcons = () => {
                    let dragDropBtn = document.querySelectorAll('span.editing_move');
                    if (dragDropBtn && dragDropBtn.length !== 0) {
                        dragDropBtn.forEach((button) => {
                            button.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                            });
                        });
                    } else {
                        setTimeout(() => initSettingsDragDropIcons(),100);
                    }
                };
                initSettingsDragDropIcons();
            });
        }
    };
});