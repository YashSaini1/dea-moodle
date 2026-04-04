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
 * Code that is executed before the tables and data are dropped during the plugin uninstallation.
 *
 * @package     local_ab_testing
 * @category    uninstall
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use local_ab_testing\core;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstallation procedure.
 */
function xmldb_local_ab_testing_uninstall() {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/user/profile/definelib.php');

    $profile_field = $DB->get_record('user_info_field', ['shortname' => core::PROFILE_FIELD_NAME]);
    if (!empty($profile_field)){
        profile_delete_field($profile_field->id);
    }

    return true;
}
