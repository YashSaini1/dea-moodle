<?php

$functions = [
    'local_myapi_get_course_full_data' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'get_course_full_data',
        'classpath'   => '', // IMPORTANT: leave empty
        'description' => 'Get full course data',
        'type'        => 'read',
    ],
    'local_myapi_get_users' => [
        'classname'   => 'local_myapi\\external\\user_external',
        'methodname'  => 'get_users',
        'classpath'   => '',
        'description' => 'Get users with pagination',
        'type'        => 'read',
    ],
];
