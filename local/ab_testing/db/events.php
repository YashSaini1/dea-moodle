<?php

/**
 * Definition of local_ab_testing event observers.
 *
 * The observers defined in this file are notified when respective events are triggered. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Events API: {@link http://docs.moodle.org/dev/Event_2}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package     local_ab_testing
 * @category    event
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    /// User events
    array(
        'eventname' => '\core\event\user_created',
        'callback'  => '\local_ab_testing\observers\user_observer::process_event',
    ),
);
