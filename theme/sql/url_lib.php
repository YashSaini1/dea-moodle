<?php

use local_sql\mod_actions;

require_once("$CFG->dirroot/mod/url/locallib.php");

function sql_url_display_embed($url, $cm, $course){
    global $PAGE, $OUTPUT, $USER;
    $fullurl = url_get_full_url($url, $cm, $course);
    if (stripos($fullurl, 'vimeo.com') === false){
        return url_display_embed($url, $cm, $course);
    }

    $PAGE->set_pagelayout('modurl');
    $mimetype = resourcelib_guess_url_mimetype($url->externalurl);
    $fullurl  = url_get_full_url($url, $cm, $course);
    $title    = $url->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'url', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($url->externalurl);

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = theme_sql_resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }

    $is_split_page = !empty(mod_actions::get_by_cmid($cm->id));
    $template = 'theme_sql/mod/url_vimeo_single_video';
    if ($is_split_page){
        $PAGE->add_body_class('url_vimeo_split');
    } else {
        $PAGE->add_body_class('url_vimeo_single');
    }

    url_print_header($url, $cm, $course);

    $template_ctx = [
        'embeded_code' => $code,
        'output'       => $OUTPUT,
        'video_name'   => $OUTPUT->heading($url->name, '3', 'video_name'),
    ];

    if ($is_split_page){
        $template = 'theme_sql/mod/url_vimeo_split_video';
        $template_ctx['actions'] = mod_actions::render_elements($cm->id);
    }

    echo $OUTPUT->render_from_template($template, $template_ctx);

    echo $OUTPUT->footer();
    die;
}


/**
 * Returns general link or file embedding html.
 * @param string $fullurl
 * @param string $title
 * @param string $clicktoopen
 * @param string $mimetype
 * @return string html
 */
function theme_sql_resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype) {
    global $CFG, $PAGE;

    if ($fullurl instanceof moodle_url) {
        $fullurl = $fullurl->out();
    }

    $param = '<param name="src" value="'.$fullurl.'" />';

    // Always use iframe embedding because object tag does not work much,
    // this is ok in HTML5.
    $code = <<<EOT
<div class="resourcecontent resourcegeneral">
  <iframe id="resourceobject" src="$fullurl" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
    $clicktoopen
  </iframe>
</div>
EOT;

    // the size is hardcoded in the boject obove intentionally because it is adjusted by the following function on-the-fly
    $PAGE->requires->js_init_call('M.util.init_maximised_embed', array('resourceobject'), true);

    return $code;
}
