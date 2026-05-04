<?php

$functions = [
    'local_myapi_get_course_full_data' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'get_course_full_data',
        'classpath'   => '', // IMPORTANT: leave empty
        'description' => 'Get full course data',
        'type'        => 'read',
    ],
    'local_coursebuilder_create_sections' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'create_sections',
        'classpath'   => '',
        'description' => 'Create sections inside a course',
        'type'        => 'write',
    ],
    'local_coursebuilder_create_module' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'create_module',
        'classpath'   => '',
        'description' => 'Create a module inside a course section',
        'type'        => 'write',
    ],
    'local_myapi_update_module' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'update_module',
        'classpath'   => '',
        'description' => 'Update a module visibility or availability settings',
        'type'        => 'write',
    ],
    'local_myapi_update_section' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'update_section',
        'classpath'   => '',
        'description' => 'Update a course section name, visibility, summary, or availability',
        'type'        => 'write',
    ],
    'local_myapi_get_users' => [
        'classname'   => 'local_myapi\\external\\user_external',
        'methodname'  => 'get_users',
        'classpath'   => '',
        'description' => 'Get users with pagination',
        'type'        => 'read',
    ],
    'local_myapi_save_course_image' => [
        'classname'   => 'local_myapi\\external\\course_external',
        'methodname'  => 'save_course_image',
        'classpath'   => '',
        'description' => 'Save course overview image from draft files',
        'type'        => 'write',
    ],
];
