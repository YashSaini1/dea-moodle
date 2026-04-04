define(['jquery'], function($) {
    return {
        init: (strings) => {
            const init_callback = () => {
                let copy_buttons = document.querySelectorAll('.copy_btn');
                copy_buttons.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        let copy_data = button.getAttribute('data-copy');
                        if (copy_data){
                            navigator.clipboard.writeText(copy_data);
                        } else {
                            alert('empty data to copy');
                        }
                        button.innerText = strings.copied;
                    });
                    button.addEventListener('blur', (event) => {
                        button.innerText = strings.copy;
                    });
                });
            };
            $(document).ready(init_callback);
        }
    };
});