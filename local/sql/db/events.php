<?php

/**
 * Definition of local_sql event observers.
 *
 * The observers defined in this file are notified when respective events are triggered. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Events API: {@link http://docs.moodle.org/dev/Event_2}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package     local_sql
 * @category    event
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    /// Question events
    array(
        'eventname' => '\core\event\question_created',
        'callback'  => '\local_sql\observers\question_observer::save_question',
    ),
    array(
        'eventname' => '\mod_quiz\event\slot_created',
        'callback'  => '\local_sql\observers\question_observer::slot_created',
    ),
    array(
        'eventname' => '\mod_quiz\event\slot_deleted',
        'callback'  => '\local_sql\observers\question_observer::slot_deleted',
    ),
    array(
        'eventname' => '\mod_quiz\event\slot_moved',
        'callback'  => '\local_sql\observers\question_observer::slot_moved',
    ),
    /// User events
    array(
        'eventname' => '\core\event\user_created',
        'callback'  => '\local_sql\observers\user_observer::user_created',
    ),
    /// Course events
    array(
        'eventname' => '\core\event\course_created',
        'callback'  => '\local_sql\observers\course_observer::course_created',
    ),
    array(
        'eventname' => '\core\event\course_updated',
        'callback'  => '\local_sql\observers\course_observer::course_updated',
    ),
    /// Course modules events
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => '\local_sql\observers\cm_observer::cm_deleted',
    ),
    array(
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\local_sql\observers\cm_observer::cm_created',
    ),
);
