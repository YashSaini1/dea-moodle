define([], function() {
    return {
        init: () => {
            function init_buttons() {
                const question_button = document.querySelector('.tabs_buttons_container .button_question');
                const output_button = document.querySelector('.tabs_buttons_container .button_output');
                const video_button = document.querySelector('.tabs_buttons_container .button_video');
                const formulation_area = document.querySelector('.formulation-area');
                const question_area = document.querySelector('.question-area');
                const editor_slider = document.querySelector('.editor_slider');
                const show_solution_button = document.querySelector('#mod_quiz-show-solution');
                const close_button = document.querySelector('.sql-close-button');
                if (!question_button || !output_button){
                    return;
                }

                const question_tab = document.querySelector('.tabs_info_container .tab.tab-question');
                const output_tab = document.querySelector('.tabs_info_container .tab.tab-output');
                const video_tab = document.querySelector('.tabs_info_container .tab.tab-video');
                if (!question_tab || !output_tab){
                    return;
                }

                question_button.addEventListener('click', () => {
                    if (question_tab.classList.contains('active')){
                        return;
                    }
                    editor_slider.classList.remove('d-none');
                    formulation_area.classList.remove('closed');
                    question_area.classList.remove('full');
                    show_solution_button.classList.remove('d-none');
                    if(close_button) {
                        close_button.classList.remove('d-none');
                    }

                    question_button.classList.add('active');
                    output_button.classList.remove('active');
                    output_tab.classList.remove('active');
                    question_tab.classList.add('active');

                    if(video_button) {
                        video_button.classList.remove('active');
                    }
                    if(video_tab) {
                        video_tab.classList.remove('active');
                    }
                });

                output_button.addEventListener('click', () => {
                    if (output_tab.classList.contains('active')){
                        return;
                    }
                    formulation_area.classList.remove('closed');
                    question_area.classList.remove('full');
                    show_solution_button.classList.remove('d-none');
                    if(close_button) {
                        close_button.classList.remove('d-none');
                    }

                    question_button.classList.remove('active');
                    output_button.classList.add('active');
                    editor_slider.classList.remove('d-none');
                    question_tab.classList.remove('active');
                    output_tab.classList.add('active');
                    if(video_button) {
                        video_button.classList.remove('active');
                    }
                    if(video_tab) {
                        video_tab.classList.remove('active');
                    }
                });

                if(video_button) {
                    video_button.addEventListener('click', () => {
                        if (video_tab.classList.contains('active')) {
                            return;
                        }

                        editor_slider.classList.add('d-none');
                        formulation_area.classList.add('closed');
                        question_area.classList.add('full');

                        show_solution_button.classList.add('d-none');
                        question_button.classList.remove('active');
                        output_button.classList.remove('active');
                        video_button.classList.add('active');
                        if(close_button) {
                            close_button.classList.add('d-none');
                        }
                        question_tab.classList.remove('active');
                        output_tab.classList.remove('active');
                        video_tab.classList.add('active');

                        video_button.classList.remove('pulse');
                    });
                }

                if (output_tab.classList.contains('active')) {
                    question_button.classList.remove('active');
                    output_button.classList.add('active');
                } else {
                    question_button.classList.add('active');
                    output_button.classList.remove('active');
                }
            }

            if (document.readyState !== 'loading'){
                init_buttons();
                return;
            }

            document.addEventListener('DOMContentLoaded', function () {
                init_buttons();
            });
        }
    };
});