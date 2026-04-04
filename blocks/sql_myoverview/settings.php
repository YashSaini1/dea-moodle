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
 * Settings for the sql_myoverview block
 *
 * @package    block_sql_myoverview
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/sql_myoverview/lib.php');

    // Presentation options heading.
    $settings->add(new admin_setting_heading('block_sql_myoverview/appearance',
            get_string('appearance', 'admin'),
            ''));

    // Display Course Categories on Dashboard course items (cards, lists, summary items).
    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaycategories',
            get_string('displaycategories', 'block_sql_myoverview'),
            get_string('displaycategories_help', 'block_sql_myoverview'),
            1));

    // Enable / Disable available layouts.
    $choices = array(BLOCK_SQL_MYOVERVIEW_VIEW_CARD => get_string('card', 'block_sql_myoverview'),
            BLOCK_SQL_MYOVERVIEW_VIEW_LIST => get_string('list', 'block_sql_myoverview'),
            BLOCK_SQL_MYOVERVIEW_VIEW_SUMMARY => get_string('summary', 'block_sql_myoverview'));
    $settings->add(new admin_setting_configmulticheckbox(
            'block_sql_myoverview/layouts',
            get_string('layouts', 'block_sql_myoverview'),
            get_string('layouts_help', 'block_sql_myoverview'),
            $choices,
            $choices));
    unset ($choices);

    // Enable / Disable course filter items.
    $settings->add(new admin_setting_heading('block_sql_myoverview/availablegroupings',
            get_string('availablegroupings', 'block_sql_myoverview'),
            get_string('availablegroupings_desc', 'block_sql_myoverview')));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingallincludinghidden',
            get_string('allincludinghidden', 'block_sql_myoverview'),
            '',
            0));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingall',
            get_string('all', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupinginprogress',
            get_string('inprogress', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingpast',
            get_string('past', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingfuture',
            get_string('future', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingcustomfield',
            get_string('customfield', 'block_sql_myoverview'),
            '',
            0));

    $choices = \core_customfield\api::get_fields_supporting_course_grouping();
    if ($choices) {
        $choices  = ['' => get_string('choosedots')] + $choices;
        $settings->add(new admin_setting_configselect(
                'block_sql_myoverview/customfiltergrouping',
                get_string('customfiltergrouping', 'block_sql_myoverview'),
                '',
                '',
                $choices));
    } else {
        $settings->add(new admin_setting_configempty(
                'block_sql_myoverview/customfiltergrouping',
                get_string('customfiltergrouping', 'block_sql_myoverview'),
                get_string('customfiltergrouping_nofields', 'block_sql_myoverview')));
    }
    $settings->hide_if('block_sql_myoverview/customfiltergrouping', 'block_sql_myoverview/displaygroupingcustomfield');

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupingfavourites',
            get_string('favourites', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configcheckbox(
            'block_sql_myoverview/displaygroupinghidden',
            get_string('hiddencourses', 'block_sql_myoverview'),
            '',
            1));

    $settings->add(new admin_setting_configtext('block_sql_myoverview/disabled_coaching_link',
        get_string('disabled_coaching_link', 'block_sql_myoverview'),
            '',''));
}
