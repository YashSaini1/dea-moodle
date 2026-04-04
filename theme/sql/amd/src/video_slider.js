define(['jquery', 'theme_sql/swiper'], function ($, Swiper) {
    return {
        init: () => {
            let slideId = document.querySelector('.swiper-slide.current').getAttribute('slide-id');

            const slider = new Swiper('.swiper', {
                slidesPerView: 5,
                spaceBetween: 16,
                lazy: true,
                initialSlide: slideId - 1,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    1100: {
                        slidesPerView: 2,
                    },
                    1200: {
                        slidesPerView: 3,
                    },
                    1300: {
                        slidesPerView: 4,
                    },
                    1440: {
                        slidesPerView: 5,
                    }
                }
            });
        }
    };
});
