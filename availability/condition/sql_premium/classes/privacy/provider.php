<?php

/**
 * Privacy Subsystem implementation for availability_sql_premium.
 *
 * @package   availability_sql_premium
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

namespace availability_sql_premium\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for availability_sql_premium implementing null_provider.
 *
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}