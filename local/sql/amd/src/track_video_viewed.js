/* eslint-disable */
define(['jquery', 'core/ajax'], ($, Ajax) => {
    return {
        init: (cmid) => {
            let sended = false;
            console.log('inited');
            document.addEventListener('video_viewed', (e) => {
                if (sended){
                    return;
                }

                sended = true;
                console.log('ended');
                const req = Ajax.call([{
                    methodname: 'local_sql_track_hvp_video',
                    args: {
                        cmid: cmid,
                    },
                }], true);
                let currentSlide = document.querySelector('.swiper-slide.current');
                req[0].done(function(data) {
                    if (!currentSlide.classList.contains('done')){
                        currentSlide.classList.add('done');
                    }
                    if (data.available_next) {
                        let nextSlideId = parseInt(currentSlide.getAttribute('slide-id')) + 1;
                        let nextSlide = document.querySelector(".swiper-slide[slide-id='" + nextSlideId + "'].disabled");
                        if (typeof (nextSlide) != 'undefined' && nextSlide != null) {
                            nextSlide.classList.remove('disabled');
                            nextSlide.querySelector('a').setAttribute('href', data.available_next);
                        }
                    }
                }.bind(this)).fail(Notification.exception);
            });
        },
    };
});