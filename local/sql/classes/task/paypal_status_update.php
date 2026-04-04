<?php
namespace local_sql\task;

defined('MOODLE_INTERNAL') || die();


require_once('/var/www/html/config.php');
require_once($CFG->dirroot."/local/sql/vendor/autoload.php");

use Exception;
use local_sql\core\model\paypal;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

use PayPal\Api\Payout;

use core\task\scheduled_task;

class paypal_status_update extends scheduled_task {

    public function get_name() {
        return get_string('task:paypal_status_update', 'local_sql');
    }

    public function execute() {
        $config = get_config('local_sql');

        $client_id = $config->paypal_client_id ?? '';
        $client_secret = $config->paypal_client_secret ?? '';

        $api_context = null;

        $all_wait = paypal::get_all_wait_transaction();

        if (!empty($all_wait)) {
            $auth_token = new OAuthTokenCredential($client_id, $client_secret);
            $api_context = new ApiContext($auth_token);
        }

        foreach ($all_wait as $transaction) {
            $uniqueid = $transaction->uniqueid;
            $paypal = paypal::get(['uniqueid' => $uniqueid]);
            try {

                if ($paypal->batchid == "empty") {
                    \local_sql\core::log_message("[PAYPAL_CRON] empty batch. paypal_id: $paypal->id.");
                    $paypal->set_status(0);
                    continue;
                }

                $payout_batch = Payout::get($paypal->batchid, $api_context);
                $batch_status = $payout_batch->getBatchHeader()->getBatchStatus();

                \local_sql\core::log_message("[PAYPAL_CRON] [BATCH: $batch_status] Start processing batch_id: $paypal->batchid.");

                if (in_array($batch_status, ['SUCCESS', 'PENDING'])) {
                    $payout_items = $payout_batch->getItems();
                    $completed = true;

                    foreach ($payout_items as $item) {
                        $status = $item->getTransactionStatus();
                        $payout_item_id = $item->getPayoutItemId();

                        if ($status === 'SUCCESS') {
                            \local_sql\core::log_message("[PAYPAL_CRON] [TRANSACTION: SUCCESS] transaction: $payout_item_id. batch_id: $paypal->batchid.");
                        } elseif ($status === 'PENDING') {
                            \local_sql\core::log_message("[PAYPAL_CRON] [TRANSACTION: PENDING] transaction: $payout_item_id. batch_id: $paypal->batchid.");
                            $completed = false;
                        } elseif ($status === 'RETURNED') {
                            \local_sql\core::log_message("[PAYPAL_CRON] [TRANSACTION: RETURNED] transaction: $payout_item_id. batch_id: $paypal->batchid. is_refund: Starting...");
                            $paypal->set_status(0);
                            $is_refund = $paypal->make_refund();
                            \local_sql\core::log_message("[PAYPAL_CRON] [TRANSACTION: RETURNED] transaction: $payout_item_id. batch_id: $paypal->batchid. is_refund: $is_refund");
                            $completed = false;
                        } else {
                            \local_sql\core::log_message("[PAYPAL_CRON] [TRANSACTION: $status] transaction: $payout_item_id. batch_id: $paypal->batchid.");
                            $completed = false;
                        }
                    }

                    if ($completed) {
                        \local_sql\core::log_message("[PAYPAL_CRON] [BATCH: $batch_status] batch_id: $paypal->batchid. OK");
                        $paypal->set_status(1);
                    } else {
                        \local_sql\core::log_message("[PAYPAL_CRON] Some transactions in batch are not successful. batch_id: $paypal->batchid.");
                    }
                } else {
                    \local_sql\core::log_message("[PAYPAL_CRON] [BATCH: $batch_status] batch_id: $paypal->batchid. is_refund: Starting...");
                    $paypal->set_status(0);
                    $is_refund = $paypal->make_refund();
                    \local_sql\core::log_message("[PAYPAL_CRON] [BATCH: $batch_status] batch_id: $paypal->batchid. is_refund: $is_refund");
                }


            } catch (Exception $e) {
                \local_sql\core::log_message("[PAYPAL_CRON] ".$e->getMessage());
            }
        }
    }
}
