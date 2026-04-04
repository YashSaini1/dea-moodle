define([], function() {
    return {
        init: async () => {
            let questionArea = document.querySelector(".question-area");
            let editorArea = document.querySelector(".formulation-area");
            let slider = document.querySelector(".editor_slider");

            questionArea.style.width = localStorage.getItem('question_width');
            editorArea.style.width = localStorage.getItem('editor_width');

            localStorage.clear();

            slider.onmousedown = function dragMouseDown(e) {
                let dragX = e.clientX;

                document.onmousemove = function onMouseMove(e) {
                    questionArea.style.width = questionArea.offsetWidth + e.clientX - dragX + "px";
                    dragX = e.clientX;

                    localStorage.setItem('question_width', questionArea.style.width);
                    localStorage.setItem('editor_width', editorArea.style.width);
                }
                // remove mouse-move listener on mouse-up
                document.onmouseup = () => document.onmousemove = document.onmouseup = null;
            }

            slider.addEventListener('mousedown', () => {
                slider.classList.add('active');
            });

            slider.addEventListener('mouseup', () => {
                slider.classList.remove('active');
            });
        }
    };
});