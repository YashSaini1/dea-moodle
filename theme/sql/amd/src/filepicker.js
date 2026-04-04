define(['jquery'], function ($) {
    return {
        init: async () => {
            var upload_inited = false;
            const init_upload_form = () => {
                const filepickerBtn = document.querySelector('.choose-file-btn');
                const filepickerInput = document.querySelector('input[type="file"]');

                if (!filepickerInput || filepickerInput.getAttribute('inited')){
                    return;
                }

                const acceptTypes = document.querySelectorAll('input[name="accepted_types[]"]');
                let acceptTypeArr = [];

                acceptTypes.forEach(function (acceptType) {
                    acceptTypeArr.push(acceptType.value);
                });

                filepickerInput.setAttribute('accept', acceptTypeArr.join(','));
                filepickerInput.setAttribute('inited', 'true');
                filepickerBtn.addEventListener('click', () => {
                    document.getElementById('selectedFile').click();
                });

                filepickerInput.addEventListener('input', () => {
                    let filename = filepickerInput.files[0].name;

                    if (acceptTypeArr.length > 0){
                        if (acceptTypeArr.indexOf('.' + filename.split('.').pop()) === -1){
                            document.querySelector('input[type="file"]').value = '';
                            document.querySelector('.fp-upload-btn').classList.remove('has-file');
                            filepickerInput.nextElementSibling.nextElementSibling.innerHTML = 'No file choosen';
                            return false;
                        }
                    }

                    filepickerInput.nextElementSibling.nextElementSibling.innerHTML = filename;
                    document.querySelector('.fp-upload-btn').classList.add('has-file');
                });

                document.querySelector('.fp-upload-btn').addEventListener('click', () => {
                    let filepickerPreloader = document.querySelector('.container .basic-preload');
                    filepickerPreloader.style.display = 'block';
                });
            };

            const form_loaded = (filepicker_items_container, filepickerPreloader) => {
                if (filepicker_items_container.classList.contains('repository_upload')){
                    init_upload_form();
                }
                filepicker_items_container.querySelector('.file-picker').style.display='';
                filepickerPreloader.style.display = 'none';
            }

            const initFilepickerInputs = () => {
                let repositories = document.querySelector('.fp-repo.nav-item');
                let filepickerPreloader = document.querySelector('.container .basic-preload');

                if (!repositories){
                    setTimeout(() => initFilepickerInputs(), 100);
                    upload_inited = false;
                    return;
                }

                var filepicker_items_container = document.querySelector('.moodle-dialogue.filepicker .container');
                repositories = document.querySelectorAll('.fp-repo.nav-item');

                if (repositories.length === 1){
                    let area = filepicker_items_container.querySelector('.fp-repo-area');
                    area.style.display = 'none';
                }

                function mutate(mutations) {
                    form_loaded(filepicker_items_container, filepickerPreloader);
                }

                let target = filepicker_items_container.querySelector('.fp-repo-items');
                let observer = new MutationObserver(mutate);
                let config = {characterData: true, attributes: false, childList: true, subtree: true};
                observer.observe(target, config);
                if (filepicker_items_container.classList.contains('repository_upload')
                    || filepicker_items_container.classList.contains('repository_s3')){
                    form_loaded(filepicker_items_container, filepickerPreloader);
                }
            };

            $(document).ready(() => {
                var observerStop = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.attributeName === "class"){
                            var attributeValue = $(mutation.target).prop(mutation.attributeName);
                            var bodyClasses = attributeValue.split(' ');
                            if (bodyClasses[bodyClasses.length - 1] === "lockscroll"){
                                upload_inited = false;
                                let container = document.querySelector('.filepicker .container');
                                if (container){
                                    container.querySelector('.file-picker').style.display = 'none';
                                }
                                initFilepickerInputs();
                            }
                        }
                    });
                });

                observerStop.observe(document.body, {
                    attributes: true,
                });
            })
        },
    };
});