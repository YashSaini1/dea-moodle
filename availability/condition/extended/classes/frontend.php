<?php

/**
 * Front-end class.
 *
 * @package availability_extended
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

namespace availability_extended;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 * Init yii js script parameters
 *
 * @package availability_extended
 * @copyright 2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */
class frontend extends \core_availability\frontend {

    protected function get_javascript_strings() {
        return ['option_access_must_have','option_access_must_not_have', 'have_access_desc','label_cm', 'label_access_must'];
    }
}
