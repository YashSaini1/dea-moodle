<?php

namespace local_sql\core;

trait moodle {

    public static function get_cache($area){
        return \cache::make(static::PLUGIN_NAME, $area);
    }

    public static function str($name, $a = null, $component = null){
        $component = $component ?? static::PLUGIN_NAME;
        return get_string($name, $component, $a);
    }

    public static function get_config($name, $component = null){
        $component = $component ?? static::PLUGIN_NAME;
        return get_config($component, $name);
    }

    public static function has_capability($cap, $context = null, $user = null){
        if (strpos($cap, '/') === false){
            $component = static::get_component();
            $cap = $component['type'].'/'.$component['plugin'].':'.$cap;
        }

        $context = $context ?? \context_system::instance();
        return has_capability($cap, $context, $user);
    }

    /**
     * @param string|null $component
     *
     * @return array{type:string, plugin:string}
     */
    public static function get_component(string $component = null){
        static $plugin_data = [];

        if (!array_key_exists($component, $plugin_data)){
            $component = $component ?? static::PLUGIN_NAME;
            [$type, $plugin] = \core_component::normalize_component($component);
            $plugin_data[$component] = compact('type', 'plugin');
        }
        return $plugin_data[$component];
    }

    public static function render_from_template($name, $context){
        global $OUTPUT;
        return $OUTPUT->render_from_template(static::PLUGIN_NAME.'/'.$name, $context);
    }

    public static function call_js_amd($name, $func = null, $params = array()){
        global $PAGE;
        $PAGE->requires->js_call_amd(static::PLUGIN_NAME.'/'.$name, $func, $params);
    }

    public static function js($url, $inhead = false){
        global $PAGE;
        $PAGE->requires->js($url, $inhead);
    }
}