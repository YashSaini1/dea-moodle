define(['jquery'], function($) {
    return {
        init: () => {
            const formulation_wrapper = document.querySelector('.formulation-wrapper');
            const open_editor = document.querySelector('.formulation-wrapper .open_editor_button_container');
            const close_editor = document.querySelector('.formulation-wrapper .sql-close-button');
            let editorArea = document.querySelector(".formulation-area");
            let slider = document.querySelector(".editor_slider");

            open_editor.addEventListener('click', (e) => {
                if (!formulation_wrapper.classList.contains('editor_opened')){
                    formulation_wrapper.classList.remove('editor_closed');
                    formulation_wrapper.classList.add('editor_opened');
                    slider.style.display = 'flex';
                    editorArea.style.flex = '1';
                }
            });
            close_editor.addEventListener('click', (e) => {
                if (!formulation_wrapper.classList.contains('editor_closed')){
                    formulation_wrapper.classList.remove('editor_opened');
                    formulation_wrapper.classList.add('editor_closed');
                    editorArea.style.width = '100%';
                    editorArea.style.flex = 'initial';
                    slider.style.display = 'none';
                }
            });
        }
    };
});