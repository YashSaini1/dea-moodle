<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 *
 * @package    block_sql_comments
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @throws coding_exception
 * @throws dml_exception
 */
function xmldb_block_sql_comments_install() {
    global $DB;
    if (empty($DB->get_record('user_info_field', ['shortname' => 'carma_points']))) {
        $row = new stdClass();
        $row->shortname = 'carma_points';
        $row->name = 'Carma points';
        $row->datatype = 'text';
        $row->description = '';
        $row->descriptionformat = 1;
        $row->categoryid = 1;
        $row->sortorder = 1;
        $row->required = 0;
        $row->locked = 1;
        $row->visible = 0;
        $row->forceunique = 0;
        $row->signup = 0;
        $row->defaultdata = '0';
        $row->defaultdataformat = 0;
        $row->param1 = 30;
        $row->param2 = 2048;
        $row->param3 = 0;
        $row->param4 = '';
        $row->param5 = '';
        $DB->insert_record('user_info_field', $row);
    }
}
