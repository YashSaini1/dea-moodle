<?php

namespace local_crm\core;

trait moodle {

    public static function str($name, $a = null, $component = null){
        $component = $component ?? static::PLUGIN_NAME;
        return get_string($name, $component, $a);
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

}