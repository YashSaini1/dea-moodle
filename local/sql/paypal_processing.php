<?php

use local_sql\core\model\paypal;
use local_referral\core\referrals\referrals_manager;
use PayPal\Api\Currency;
use PayPal\Api\Payout;
use PayPal\Api\PayoutItem;
use PayPal\Api\PayoutSenderBatchHeader;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

require_once('../../config.php');
require_once($CFG->dirroot.'/local/sql/lib.php');

require_once("vendor/autoload.php");

$uniqueid = "-1";
$userid = "-1";

try {
    $p_email = optional_param('email', null, PARAM_TEXT);
    $p_amount = clean_param(optional_param('amount', null, PARAM_FLOAT), PARAM_FLOAT);
    $p_sesskey = optional_param('sesskey', 'null', PARAM_TEXT);


    // Config load
    $config = get_config('local_sql');

    $test_mode = (bool)$config->paypal_test_mode ?? true;
    $max_sum = floatval($config->paypal_max_transaction) ?? 100.0;
    $min_sum = floatval($config->paypal_min_transaction) ?? 10000.0;
    $client_id = $config->paypal_client_id ?? '';
    $client_secret = $config->paypal_client_secret ?? '';
    $uniqueid = security_gen_str(12);


    \local_sql\core::log_message("[PAYPAL] [$uniqueid] Start session. test_mode: $test_mode, max_sum: $max_sum, min_sum: $min_sum.");

    require_login();

    $userid = $USER->id ?? null;

    if (!confirm_sesskey($p_sesskey)) {
        $res = (object)[
            'status' => false,
            'message' => 'For security reasons, the request without a valid session key is rejected.',
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 403);
    }

    if (!isset($p_amount) || $p_amount <= 0.0 || filter_var($p_amount, FILTER_VALIDATE_FLOAT) === false) {
        $res = (object)[
            'status' => false,
            'message' => 'Amount invalid.',
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 400);
    }



    if (!isset($p_email) || filter_var($p_email, FILTER_VALIDATE_EMAIL) === false) {
        $res = (object)[
            'status' => false,
            'message' => 'Email invalid.',
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 400);
    }


    $balance = referrals_manager::get_balance($userid);
    $balance = floatval($balance);
    $balance = round($balance, 2);
    $p_amount = round($p_amount, 2);

    if ($p_amount < $min_sum) {
        $res = (object)[
            'status' => false,
            'message' => "Minimum withdrawal amount $min_sum$.",
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 400);
    }


    if ($p_amount > $max_sum) {
        $res = (object)[
            'status' => false,
            'message' => "Maximum withdrawal amount $max_sum$.",
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 400);
    }


    if ($p_amount > $balance) {
        $res = (object)[
            'status' => false,
            'message' => "There are not enough funds in your balance",
        ];

        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");
        json_die($res, 400);
    }


    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. Passed all inspections. paypal_email: $p_email, amount: $p_amount, balance: $balance.");

    $paypal_instance = paypal::create([
        'userid' => $userid,
        'amount' => $p_amount,
        'email' => $p_email,
        'uniqueid' => $uniqueid,
        'batchid' => 'empty',
        'success' => 2,
        'refund' => 0,
        'timecreated' => time(),
    ]);

    $oAuthTokenCredential = new OAuthTokenCredential($client_id, $client_secret);

    $apiContext = new ApiContext($oAuthTokenCredential);

    $apiContext->setConfig(
        [
            'mode' => ($test_mode ? 'sandbox' : 'live'), // live on production
            'log.LogEnabled' => true,
            'log.FileName' => 'logs/PayPal.log',
            'log.LogLevel' => ($test_mode ? 'DEBUG' : 'INFO'), // INFO on production
        ]
    );


    $senderBatchHeader = new PayoutSenderBatchHeader();
    $senderBatchHeader->setSenderBatchId(uniqid())
        ->setEmailSubject("Referral program fee");

    $payoutItem = new PayoutItem();
    $payoutItem->setRecipientType('EMAIL')
        ->setReceiver($p_email)
        ->setAmount(new Currency([
            'value' => $p_amount,
            'currency' => 'USD'
        ]))
        ->setSenderItemId(uniqid());

    $payout = new Payout();
    $payout->setSenderBatchHeader($senderBatchHeader)
        ->addItem($payoutItem);

    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. Setup successfully. Ready for payment.");

    $transaction = $DB->start_delegated_transaction();

    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. DB transaction starting.");

    referrals_manager::update_balance($userid, -$p_amount);

    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. Balance updated.");

    $payoutBatch = $payout->create(null, $apiContext);
    $batchId = $payoutBatch->getBatchHeader()->getPayoutBatchId();

    $paypal_instance->set_batch_id($batchId);

    $transaction->allow_commit();

    $res = (object)[
        'status' => true,
        'message' => "Payment sent successfully.",
    ];

    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message batch_id: $batchId");

    json_die($res, 200);

} catch (Exception $e) {
    \local_sql\core::log_message("[PAYPAL] [$uniqueid] Fatal. user_id: $userid. Message: " . $e->getMessage() . ".");

    if (isset($transaction)) {
        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. Rollback...");
        try {
            $transaction->rollback($e);
        } catch (Exception $rollback_e) {
            \local_sql\core::log_message("[PAYPAL] [ROLLBACK] ". $rollback_e->getMessage());
        }
        \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. Rollback OK");
    }

    if (!empty($paypal_instance))
        $paypal_instance->set_status(0);

    $res = (object)[
        'status' => false,
        'message' => "At the moment, we are unable to process the payout.",
    ];

    \local_sql\core::log_message("[PAYPAL] [$uniqueid] user_id: $userid. $res->message");

    json_die($res, 409);
}