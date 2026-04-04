var full_screen = false;
var calculated_heght = -1;
window.addEventListener('resize', (e) => {
    if (window.H5P.isFullscreen != full_screen){
        update_height();
        full_screen = window.H5P.isFullscreen;
    }
});

function update_height() {
    let video = document.querySelector('.h5p-video-wrapper video');
    if (!video){
        return;
    }

    if (window.H5P.isFullscreen){
        video.style.height = '';
        return;
    }

    if (calculated_heght == -1){
        var content_wrapper = window.parent.document.querySelector('#topofscroll');
        var content_header = content_wrapper.querySelector('#page-header');
        var content_data = content_wrapper.querySelector("#page-content");
        var iframe = content_data.querySelector('iframe');

        calculated_heght = content_wrapper.offsetHeight - content_header.offsetHeight - content_data.offsetHeight +
            iframe.offsetHeight - 55;
        // do not change video height if it smaller (this is enough space for video)
        if (video.offsetHeight < calculated_heght){
            calculated_heght = video.offsetHeight;
        }
    }
    video.style.height = calculated_heght + 'px';
}

document.addEventListener('DOMContentLoaded', function () {
    const h5p_container = document.querySelector('.h5p-content');
    let inited = false;
    var initedObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (inited || mutation.attributeName !== "class"){
                return;
            }

            if (document.querySelector('.h5p-content.h5p-initialized')){
                inited = true;
                const video = document.querySelector('.h5p-video-wrapper video');
                if (!video){
                    return;
                }

                video.addEventListener('ended', (e) => {
                    if (!window.parent){
                        return;
                    }

                    let event = new Event('video_viewed');
                    window.parent.document.dispatchEvent(event);
                });
                document.querySelector('.h5p-actions').remove();
                update_height();
            }
        });
    });

    initedObserver.observe(h5p_container, {
        subtree: true,
        childList: true,
        attributes: true,
        characterData: true,
    });
}, false);