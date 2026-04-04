<?php

require_once($CFG->dirroot.'/mod/hvp/locallib.php');

/**
 * Get assets (scripts and styles) for hvp core.
 *
 * @param \context_course|\context_module $context
 * @return array
 */
function sql_hvp_get_core_assets($context) {
    global $PAGE;

    // Get core settings.
    $settings = \hvp_get_core_settings($context);
    $settings['core'] = array(
        'styles' => array(),
        'scripts' => array()
    );
    $settings['loadedJs'] = array();
    $settings['loadedCss'] = array();

    // Make sure files are reloaded for each plugin update.
    $cachebuster = \hvp_get_cache_buster();

    // Use relative URL to support both http and https.
    $liburl = \mod_hvp\view_assets::getsiteroot() . '/mod/hvp/library/';
    $relpath = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $liburl);

    // Add core stylesheets.
    foreach (\H5PCore::$styles as $style) {
        $settings['core']['styles'][] = $relpath . $style . $cachebuster;
        $PAGE->requires->css(new moodle_url($liburl . $style . $cachebuster));
    }

    // Add core JavaScript.
    foreach (\H5PCore::$scripts as $script){
        $settings['core']['scripts'][] = $relpath.$script.$cachebuster;
        $PAGE->requires->js(new moodle_url($liburl.$script.$cachebuster), true);
    }
    $settings['core']['scripts'][] = \theme_sql\mod_hvp\sql_view_assets::getsiteroot() . '/local/sql/amd/iframe_js_tracker.js';
    return $settings;
}