define(['jquery'], function ($) {
    return {
        init: async () => {
            let editorPopup = document.querySelector('.course_popup_wrapper');
            let closeEditorPopup = document.querySelector('.course_popup_close');
            let courseLink = document.querySelector('.quizstartbuttondiv form');
            let editorBtn = document.querySelector('.course_popup_content a');
            let firstPage = document.querySelector('#page-navbar .breadcrumb-item:first-child');
            let sidebarBtn = document.querySelector('.drawer-toggler .btn');
            let quizQuestion = document.querySelectorAll('table tbody td a');
            let awsQuestion = document.querySelectorAll('.activityname a');

            if (sidebarBtn){
                sidebarBtn.onmouseover = function () {
                    sidebarBtn.removeAttribute('title');
                };
                sidebarBtn.onmouseout = function () {
                    sidebarBtn.removeAttribute('title');
                };
            }

            $(document).ready(() => {
                if (document.body.id === 'page-mod-quiz-view') {
                    if (document.body.clientWidth < 768) {
                        courseLink.addEventListener('submit', (e) => {
                            e.preventDefault();
                            editorPopup.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        });

                        if (closeEditorPopup) {
                            closeEditorPopup.addEventListener('click', () => {
                                editorPopup.classList.remove('active');
                                document.body.style.overflow = 'initial';
                            });
                            editorBtn.addEventListener('click', () => {
                                location.href = '/';
                                document.body.style.overflow = 'initial';
                            });
                        }

                        quizQuestion.forEach((question) => {
                            question.addEventListener('click', (e) => {
                                e.preventDefault();
                                editorPopup.classList.add('active');
                                document.body.style.overflow = 'hidden';
                            });
                        });
                    }
                }
                if (document.body.id === 'page-mod-quiz-attempt') {
                    let previousPage = document.querySelector('#page-navbar .breadcrumb-item:first-child').nextElementSibling;
                    if (document.body.clientWidth < 768) {
                        editorPopup.classList.add('active');
                        document.body.style.overflow = 'hidden';
                        closeEditorPopup.addEventListener('click', () => {
                            previousPage.querySelector('a').click();
                            document.body.style.overflow = 'initial';
                        });
                        editorBtn.addEventListener('click', () => {
                            previousPage.querySelector('a').click();
                            document.body.style.overflow = 'initial';
                        });
                    }
                }
                if (document.body.id === 'page-mod-hvp-view') {
                    if (document.body.clientWidth < 991) {
                        editorPopup.classList.add('active');
                        document.body.style.overflow = 'hidden';
                        closeEditorPopup.addEventListener('click', () => {
                            firstPage.querySelector('a').click();
                            document.body.style.overflow = 'initial';
                        });
                        editorBtn.addEventListener('click', () => {
                            firstPage.querySelector('a').click();
                            document.body.style.overflow = 'initial';
                        });
                    }
                }
                if (document.body.id === 'page-course-view-topics') {
                    if (document.body.clientWidth < 991) {
                        if (closeEditorPopup) {
                            closeEditorPopup.addEventListener('click', () => {
                                editorPopup.classList.remove('active');
                                document.body.style.overflow = 'initial';
                            });
                            editorBtn.addEventListener('click', () => {
                                location.href = '/';
                                document.body.style.overflow = 'initial';
                            });
                        }

                        awsQuestion.forEach((question) => {
                            question.addEventListener('click', (e) => {
                                e.preventDefault();
                                editorPopup.classList.add('active');
                                document.body.style.overflow = 'hidden';
                            });
                        });
                    }
                }
            });
        }
    };
});