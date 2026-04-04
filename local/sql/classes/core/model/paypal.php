<?php

namespace local_sql\core\model;
use local_referral\core\referrals\referrals_manager;
use local_sql\core\sql_database;

class paypal extends base_object {
    public static string $table = sql_database::TABLE_PAYPAL;

    public $userid;
    public $amount;
    public $email;
    public $uniqueid;
    public $success;
    public $refund;
    public $batchid;
    public $timecreated;

    public function set_status($status) {
        global $DB;
        $DB->set_field(self::$table, 'success', $status, ['uniqueid' => $this->uniqueid]);
    }
    public function set_batch_id($batch_id) {
        global $DB;
        $DB->set_field(self::$table, 'batchid', $batch_id, ['uniqueid' => $this->uniqueid]);
    }
    public static function get_all_by_userid(int $userid): array {
        global $DB;
        return $DB->get_records(self::$table, ['userid' => $userid]);
    }

    public static function get_all_wait_transaction(): array {
        global $DB;
        return $DB->get_records(self::$table, ['success' => 2]);
    }

    public function make_refund() {
        global $DB;
        if ($this->refund == 0) {
            $DB->set_field(self::$table, 'refund', 1, ['uniqueid' => $this->uniqueid]);
            referrals_manager::update_balance($this->userid, $this->amount);
            return true;
        }
        return false;
    }
}