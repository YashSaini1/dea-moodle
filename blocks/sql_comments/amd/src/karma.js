define(['jquery', 'core/ajax', 'core/str'], function ($, Ajax, str) {
    return {
        init: async () => {
            $('body').on('click', '.block_wr_sql_comments .comment-vote .up', function () {
                send_karma_ajax($(this).closest("li").attr('id'), 1, $(this).siblings('.vote'));
            }).on('click', '.block_wr_sql_comments .comment-vote .down', function () {
                send_karma_ajax($(this).closest("li").attr('id'), 0, $(this).siblings('.vote'));
            }).on('click', '.block_wr_sql_comments .comment-delete a', function () {
                $(this).addClass('sql-send-request');
            });

            let getStr = async (name, component, a) => await str.get_string(name, component, a);

            let show_msg = await getStr('show', 'block_sql_comments');
            let hide_msg = await getStr('hide', 'block_sql_comments');

            let wrapper = document.querySelector('.block_wr_sql_comments');
            let showBtn = wrapper.querySelector('.sql_dropdown_btn');
            let comments = wrapper.querySelector('.mdl-left');

            showBtn.addEventListener('click', () => {
                showBtn.classList.toggle('show');
                comments.classList.toggle('show_comments');
                if (showBtn.classList.contains('show')) {
                    showBtn.innerHTML = hide_msg;
                } else {
                    showBtn.innerHTML = show_msg;
                }
            });

            /**
             * send
             * @param {string} id
             * @param {integer} karma
             * @param {integer} vote
             */
            function send_karma_ajax(id, karma, vote) {
                var up, down, wrapper;
                var args = {
                    id: id,
                    karma: karma
                };
                up = vote.siblings('.up');
                down = vote.siblings('.down');
                wrapper = vote.closest("li");
                wrapper.addClass('sending');
                Ajax.call([{
                    methodname: 'block_sql_comments_set_karma',
                    args: args
                }], true)[0].then((res) => {
                    wrapper.removeClass('sending');
                    up.removeClass('voting');
                    down.removeClass('voting');
                    if (res.error) {
                        alert(res.error);
                    }
                    vote.html(res.karma);
                    if (res.class.up) {
                        up.addClass(res.class.up);
                    }
                    if (res.class.down) {
                        down.addClass('voting');
                    }
                    $('.spinner-border').remove();
                }).fail((responseData) => {
                    wrapper.removeClass('sending');
                    vote.html(responseData);
                });
            }
        }
    };
});