<?php

use auth_stripe\core;
use auth_stripe\output\subscription\manual_subscription_renderer;
use auth_stripe\processor\user_tier\subscription\manual_subscription_manager;

require "../../config.php";

$userid = required_param('userid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$action = required_param('action', PARAM_TEXT);
$list = optional_param('list', '', PARAM_TEXT);

core::info('Manual update user subscription', [
    'userid' => $userid,
    'type'   => $type,
    'action' => $action,
]);

if (!\local_sql\moodle\role_manager::is_admin()){
    redirect('/');
}

$referer = $_SERVER['HTTP_REFERER'];
if (!$referer){
    redirect('/');
}

$PAGE->set_url(manual_subscription_renderer::MANUAL_SUBSCRIPTION_MANAGER_URL, [
    'userid' => $userid,
    'type'   => $type,
    'action' => $action,
]);
$PAGE->set_context(\context_system::instance());

$user = core_user::get_user($userid);
$manual_subscription_manager = new manual_subscription_manager();

$notification_type = \core\notification::ERROR;
$msg = 'Failed to '.$action.' '.ucfirst($type);
$result = false;
$kill_session = true;
if ($action == manual_subscription_renderer::ACTION_ADD){
    if ($type != 'seller') {
        $manual_subscription_manager->block_unblock($type, $user, 'block');
    }
    $result = $manual_subscription_manager->apply($type, $user);
} elseif ($action == manual_subscription_renderer::ACTION_REMOVE) {
    $result = $manual_subscription_manager->remove($type, $user);
} elseif ($action == manual_subscription_renderer::ACTION_BLOCK || $action == manual_subscription_renderer::ACTION_UNBLOCK) {
    $result = $manual_subscription_manager->block_unblock($type, $user, $action);
} elseif ($action == manual_subscription_renderer::ACTION_ADD_EXTENDED) {
    $result = $manual_subscription_manager->add_extended($type, $user, $action, $list);
    $kill_session = false;
} else {
    redirect('/');
}

if ($result) {
    $notification_type = \core\notification::SUCCESS;
    $msg = 'Successfully '.$action.' '.ucfirst($type);
}

if ($kill_session) {
    \core\session\manager::kill_user_sessions($userid);
}
redirect($referer, $msg, 0, $notification_type);