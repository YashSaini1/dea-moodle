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


$functions = array(
    'block_sql_myoverview_get_enrolled_courses_by_timeline_classification' => array(
        'classname' => 'block_sql_myoverview_external',
        'methodname' => 'get_enrolled_courses_by_timeline_classification',
        'classpath' => 'block/sql_myoverview/classes/external.php',
        'description' => 'List of enrolled courses for the given timeline classification (past, inprogress, or future).',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    )
);
