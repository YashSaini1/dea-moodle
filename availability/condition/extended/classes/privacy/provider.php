<?php

/**
 * Privacy Subsystem implementation for availability_extended.
 *
 * @package   availability_extended
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

namespace availability_extended\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for availability_extended implementing null_provider.
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