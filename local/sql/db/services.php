<?php

/**
 * Local SQL external functions and service definitions.
 *
 * The functions and services defined on this file are
 * processed and registered into the Moodle DB after any
 * install or upgrade operation. All plugins support this.
 *
 * For more information, take a look to the documentation available:
 *     - Webservices API: {@link http://docs.moodle.org/dev/Web_services_API}
 *     - External API: {@link http://docs.moodle.org/dev/External_functions_API}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package    local_sql
 * @category   webservice
 * @copyright  2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

$functions = array(
    'local_sql_track_hvp_video' => array(
        'classname'   => '\local_sql\external',
        'methodname'  => 'track_hvp_video',
        'classpath'   => 'local/sql/classes/external.php',
        'description' => 'Track video (module hvp)',
        'type'        => 'read',
        'ajax'        => true,
    ),
);