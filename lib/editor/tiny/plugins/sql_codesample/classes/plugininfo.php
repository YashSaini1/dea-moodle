<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tiny Sql Code Sample plugin plugin for Moodle.
 *
 * @package     tiny_sql_codesample
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_sql_codesample;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_configuration;

class plugininfo extends plugin implements plugin_with_configuration {

    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array{
        global $PAGE;
        static $added_parameters = false;

        if (!$added_parameters){
            define('THEME_SQL_CONNECT_PRISM', true);

            $languages = static::get_languages();
            $js = \js_writer::set_variable('window.tiny_sql_codesample', [], false);
            $js .= \js_writer::set_variable('window.tiny_sql_codesample.languages', $languages, false);

            if (headers_sent()){
                echo \html_writer::script($js);
            } else {
                $PAGE->requires->js_amd_inline($js);
            }
            $added_parameters = true;
        }

        return [];
    }

    public static function get_languages(){
        $config_langs = core::get_config('languages');
        if (!empty($config_langs)){
            // decode json because \js_writer::set_variable() method will encode this data again
            $config_langs = json_decode($config_langs);
            if ($config_langs){
                return $config_langs;
            }
        }

        return static::get_default_languages();
    }

    public static function get_default_languages(): array{
        return [
            [
                'text'  => 'HTML/XML',
                'value' => 'markup',
            ],
            [
                'text'  => 'JavaScript',
                'value' => 'javascript',
            ],
            [
                'text'  => 'CSS',
                'value' => 'css',
            ],
            [
                'text'  => 'PHP',
                'value' => 'php',
            ],
            [
                'text'  => 'Ruby',
                'value' => 'ruby',
            ],
            [
                'text'  => 'Python',
                'value' => 'python',
            ],
            [
                'text'  => 'Java',
                'value' => 'java',
            ],
            [
                'text'  => 'C',
                'value' => 'c',
            ],
            [
                'text'  => 'C#',
                'value' => 'csharp',
            ],
            [
                'text'  => 'C++',
                'value' => 'cpp',
            ],
            [
                'text'  => 'SQL',
                'value' => 'sql',
            ],
        ];
    }
}
