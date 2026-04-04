define(['jquery', 'core/ajax', 'local_sql/vimeo'], ($, ajax, Vimeo) => {
  return {
    init: (cmid) => {
      let sended = false;
      const videoIframe = document.querySelector('#resourceobject');
      const player = new Vimeo(videoIframe);
      player.on('ended', () => {
        if (sended) {
          return;
        }

        const req = ajax.call([{
          methodname: 'local_sql_track_hvp_video',
          args: {
            cmid: cmid,
          }
        }], true);

        req[0].done(function(data) {
          console.log(data);
          window.location.reload();
        }).fail(Notification.exception);
      });
    }
  };
});
