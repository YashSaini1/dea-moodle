<?php

/**
 * Definition of auth_stripe event observers.
 *
 * The observers defined in this file are notified when respective events are triggered. All plugins
 * support this.
 *
 * For more information, take a look to the documentation available:
 *     - Events API: {@link http://docs.moodle.org/dev/Event_2}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package     auth_stripe
 * @category    event
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    /// User events
    array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => '\auth_stripe\observers\user_observer::user_deleted',
    ),
    array(
        'eventname' => '\core\event\user_created',
        'callback'  => '\auth_stripe\observers\user_observer::user_created',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\auth_stripe\observers\user_observer::user_loggedin',
    ),
    array(
        'eventname' => '\auth_stripe\event\payment_created',
        'callback'  => '\auth_stripe\observers\payment_observer::payment_created',
    ),
);
