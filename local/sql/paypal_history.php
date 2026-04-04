<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/sql/lib.php');


use local_sql\core\model\paypal;

$p_sesskey = optional_param('sesskey', 'null', PARAM_TEXT);

$config = get_config('local_sql');

$test_mode = $config->paypal_test_mode ?? true;

require_login();

$userid = $USER->id ?? null;

if (!confirm_sesskey($p_sesskey)) {
    $res = (object)[
        'status' => false,
        'message' => 'For security reasons the request without a valid session key is rejected.',
    ];

    \local_sql\core::log_message("[PAYPAL_HISTORY] user_id: $userid. $res->message");
    json_die($res, 403);
}

$arr_paypal = paypal::get_all_by_userid($userid);

$result['status'] = true;
$result['message'] = 'Get all paypal history.';
$result['history'] = [];

foreach ($arr_paypal as $record) {
    $email_parts = explode('@', $record->email);
    $local_part = substr($email_parts[0], 0, 2) . '***';
    $domain_part = $email_parts[1];
    $encrypted_email = $local_part . '@' . $domain_part;

    $result['history'][] = [
        'id' => $record->uniqueid,
        'email' => $encrypted_email,
        'amount' => $record->amount,
        'data' => date('Y-m-d H:i:s', $record->timecreated),
        'success' => $record->success
    ];
}

json_die($result, 200);

