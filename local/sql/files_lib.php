<?php
/**
 * Files lib
 *
 * @package     local_sql
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_sql_get_file_url($context_or_id, $component, $filearea, $itemid){
    if (is_numeric($context_or_id)){
        $ctxid = $context_or_id;
    } else {
        $ctxid = $context_or_id->id;
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($ctxid, $component, $filearea, $itemid, 'id', false);
    if (empty($files)){
        return '';
    }

    /**
     * @var $file stored_file
     */
    foreach ($files as $file){
        if ($file->get_filepath() == '/images/'){
            break;
        }
    }

    $url = \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
        $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    return $url->out();
}

function local_sql_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $sendfileoptions){
    global $CFG, $DB, $USER;

    require_once($CFG->libdir.'/filelib.php');
    $fs = get_file_storage();

    $itemid = 0;
    if (count($args) > 1){
        $itemid = array_shift($args);
    }
    $pathnamehash = $fs->get_pathname_hash($context->id, 'local_sql', $filearea, $itemid, '/', $args[0]);

    if (!$file = $fs->get_file_by_hash($pathnamehash) or $file->is_directory()){
        return false;
    }
    return send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}

function get_mod_hvp_poster_url($cmid_or_id,$itemid = 1, $context_or_id = null){
    if (empty($cmid_or_id)){
        return '';
    }

    if (is_numeric($cmid_or_id)){
        $cmid = $cmid_or_id;
    } else {
        $cmid = $cmid_or_id->id;
    }

    if (empty($context_or_id)){
        $ctx = \context_module::instance($cmid);
    }
    return local_sql_get_file_url($ctx, 'mod_hvp', 'content', $itemid);
}