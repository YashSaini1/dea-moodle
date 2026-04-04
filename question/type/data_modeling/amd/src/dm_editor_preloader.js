define(['jquery'], function ($) {
    return {
        init: async () => {
            let submitAnswer = document.querySelector('#mod_quiz-next-nav');
            let submitWrapper = document.querySelector('.submit_answer');
            // check the case in which someone upload the same structure in the question text
            let editorWrapper = document.querySelector('.content .question-area > .tabs_info_container > .editor_container');

            submitAnswer.addEventListener('click', () => {
                submitAnswer.value = 'Loading...';
                submitWrapper.classList.add('show_preloader');
            });

            $(document).ready(() => {
                editorWrapper.classList.add('show_editor');
            });
        }
    };
});