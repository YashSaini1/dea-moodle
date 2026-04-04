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
 * Web service for block_sql_comments
 *
 * @package    block_sql_comments
 * @subpackage db
 * @since      Moodle 2.4
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'block_sql_comments_set_karma' => array(
        'classname'   => 'block_sql_comments_external',
        'methodname'  => 'set_karma',
        'classpath'   => 'blocks/sql_comments/externallib.php',
        'description' => 'Set karma',
        'type'        => 'read',
        'ajax'        => true
    ),
    'block_sql_comments_delete_comment' => array(
        'classname'   => 'block_sql_comments_external',
        'methodname'  => 'delete_comment',
        'classpath'   => 'blocks/sql_comments/externallib.php',
        'description' => 'Delete comment',
        'type'        => 'write',
        'ajax'        => true
    )
);