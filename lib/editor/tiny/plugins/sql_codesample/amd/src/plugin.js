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
 * Tiny tiny_sql_codesample for Moodle.
 *
 * @module      tiny_sql_codesample/plugin
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';

import {component, pluginName} from './common';

// Setup the tiny_sql_codesample Plugin.
export default new Promise(async(resolve) => {
    // Note: The PluginManager.add function does not support asynchronous configuration.
    // Perform any asynchronous configuration here, and then call the PluginManager.add function.
    const [
        tinyMCE,
        pluginMetadata,
    ] = await Promise.all([
        getTinyMCE(),
        getPluginMetadata(component, pluginName),
    ]);

    let custom_languages = window?.tiny_sql_codesample?.languages;
    if (!custom_languages || typeof custom_languages !== 'object' || custom_languages.length < 1){
        custom_languages = get_default_languages();
    }

    resolve(pluginName);

    tinyMCE.overrideDefaults({
        'codesample_global_prismjs': true,
        'codesample_languages': custom_languages
    })

    // Reminder: Any asynchronous code must be run before this point.
    tinyMCE.PluginManager.add(pluginName, (editor) => {
        // Return the pluginMetadata object. This is used by TinyMCE to display a help link for your plugin.
        return pluginMetadata;
    });
});

function get_default_languages() {
    return [
        {
            text: 'HTML/XML',
            value: 'markup'
        },
        {
            text: 'JavaScript',
            value: 'javascript'
        },
        {
            text: 'CSS',
            value: 'css'
        },
        {
            text: 'PHP',
            value: 'php'
        },
        {
            text: 'Ruby',
            value: 'ruby'
        },
        {
            text: 'Python',
            value: 'python'
        },
        {
            text: 'Java',
            value: 'java'
        },
        {
            text: 'C',
            value: 'c'
        },
        {
            text: 'C#',
            value: 'csharp'
        },
        {
            text: 'SQL',
            value: 'sql'
        },
        {
            text: 'C++',
            value: 'cpp'
        }
    ];
}