<?php
/**
 * Plugin lib
 *
 * @package     local_ab_testing
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

use local_ab_testing\core;

if (core::is_ab_enabled()){
    function local_ab_testing_before_http_headers(){
        \local_ab_testing\test\base\base_test::trigger_hook('page_open');
    }
}