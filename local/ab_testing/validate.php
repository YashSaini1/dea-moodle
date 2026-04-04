<?php

require "../../config.php";

if (!is_siteadmin()){
    redirect('/');
}

$settings = \local_ab_testing\ab_test_configurator::get_settings();


if (empty($settings)){
    \core\notification::error('Cannot open and parse config');
} else {
    \core\notification::success('Config was opened correctly');
}

echo $OUTPUT->header();
if (!empty($settings)){
    echo '<pre>'.print_r($settings, 1).'</pre>';
}
echo $OUTPUT->footer();
