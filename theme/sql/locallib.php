<?php

// Other local functions

function get_user_time_on_platform($user){
    return time() - (($user->firstaccess > 0) ? $user->firstaccess : $user->timecreated);
}

function print_time_on_platform($timestamp_on_platform){
    return build_string_time_on_platform(\local_sql\util\DateFormatter::format_time($timestamp_on_platform));
}

function build_string_time_on_platform($time){
    return get_string('time_on_platform', 'theme_sql', $time);
}

/**
 * Check that string starts with $needle if it's string, or any of the $needle if it's array
 *
 * @param string       $haystack
 * @param string|array $needle
 * @param bool         $case_sensitive true by default
 *
 * @return bool
 */
function theme_sql_str_starts_with($haystack, $needle, $case_sensitive = true){
    if (!isset($haystack[0])) return false;

    if (is_string($needle)){
        $needle_len = strlen($needle);
        $haystack_len = strlen($haystack);
        if (!$needle_len || $haystack_len < $needle_len) return false;

        $check_str = substr($haystack, 0, $needle_len);
        if ($case_sensitive){
            return strpos($check_str, $needle) === 0;
        } else {
            return stripos($check_str, $needle) === 0;
        }
    } elseif (is_array($needle)) {
        foreach ($needle as $item){
            if (theme_sql_str_starts_with($haystack, $item, $case_sensitive)){
                return true;
            }
        }
    }

    return false;
}

/**
 * Check that string ends with $needle if it's string, or any of the $needle if it's array
 *
 * @param string       $haystack
 * @param string|array $needle
 * @param bool         $case_sensitive true by default
 *
 * @return bool
 */
function theme_sql_str_ends_with($haystack, $needle, $case_sensitive = true){
    if (!isset($haystack[0])) return false;

    if (is_string($needle)){
        $needle_len = strlen($needle);
        $haystack_len = strlen($haystack);
        if (!$needle_len || $haystack_len < $needle_len) return false;

        $check_str = substr($haystack, -$needle_len);
        if ($case_sensitive){
            return strpos($check_str, $needle) === 0;
        } else {
            return stripos($check_str, $needle) === 0;
        }
    } elseif (is_array($needle)) {
        foreach ($needle as $item){
            if (theme_sql_str_ends_with($haystack, $item, $case_sensitive)){
                return true;
            }
        }
    }

    return false;
}

/**
 * Return path for file
 *
 * @param      $path
 *
 * @return false|string
 */
function theme_sql_path($path){
    global $CFG;
    $prefix = $CFG->dirroot;

    if (theme_sql_str_starts_with($path, $prefix)){
        // we have absolute path yet
        return $path;
    }

    return $prefix.$path;
}

function sql_mod_view($mod, $course, $cm, $context){
    // Trigger course_module_viewed event.
    $params = array(
        'context'  => $context,
        'objectid' => $mod->id,
    );

    $event = \mod_url\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot($cm->modname, $mod);
    $event->trigger();
    // Completion.
//    $completion = new completion_info($course);
//    $completion->set_module_viewed($cm);
}