<?php
namespace local_referral\core\referrals;

use local_referral\core;
use local_referral\core\tables;
use auth_stripe\model\product;

require_once($CFG->dirroot.'/local/referral/lib.php');

class referrals_manager {
    private $user;
    private $price;
    private $ownerid;
    private $coupon;
    private $array_coupon;

    private static function get_coupons_from_config(): array {
        $config = get_config('local_referral');
        if (!empty($config->referral_coupons)) {
            $coupons_array = json_decode($config->referral_coupons, true);

            if (is_array($coupons_array))
                return $coupons_array;
        }
        return [];
    }

    public static function coupon_checker(): array {
        $coupons_array = self::get_coupons_from_config();
        $valid_coupons = [];

        if (empty($coupons_array))
            return $valid_coupons;

        foreach ($coupons_array as $coupon_data) {
            foreach ($coupon_data as $coupon_code => $coupon_details) {
                if (!empty($coupon_details['id']))
                    $valid_coupons[] = [$coupon_code => $coupon_details];
            }
        }
        return $valid_coupons;
    }

    public function __construct($user, $price, $coupon) {
        core::log_message("[REFERRAL] Start constructor");
        if (empty($user) || empty($price))
            return;

        $this->array_coupon = self::coupon_checker();

        if (empty($this->array_coupon)) {
            core::log_message("[REFERRAL] No valid coupons found in configuration (missing IDs).");
            return;
        }

        $this->user = $user;
        $this->price = $price;
        $this->coupon = $coupon;
        $this->processing();
    }

    public function processing() {
        $ownerid = self::who_user_referral($this->user->id);

        if (empty($ownerid))
            return;

        core::log_message("[REFERRAL] Coupon: $this->coupon");

        if (!self::is_valid_coupon($this->coupon))
            return;

        $this->ownerid = $ownerid;

        $product = product::get_by_id($this->price->productid);

        if (empty($product))
            return;

        $is_coaching = $product->is_coaching_page();

        core::log_message("[REFERRAL] Start process gratuity, is_coaching: $is_coaching");

        $this->process_gratuity($is_coaching);
        $this->add_payment_referral($is_coaching);
        $this->remove_bonus();
    }

    public function add_payment_referral($is_coaching) {
        global $DB;

        $DB->insert_record(tables::TABLE_REFERRAL_PAYMENT, array('userid' => $this->user->id, 'ownerid' => $this->ownerid, 'is_coaching' => $is_coaching, 'timecreated' => time()));

        core::log_message("[REFERRAL] Add payment referral userid: {$this->user->id} for the owner: $this->ownerid");
    }

    public function remove_bonus() {
        global $DB;

        $DB->set_field(tables::TABLE_REFERRAL_INVITED, 'is_bonus_allow', 0, ['userid' => $this->user->id]);

        core::log_message("[REFERRAL] Bonus removed userid: {$this->user->id}");
    }

    public function process_gratuity($is_coaching) {
        $rewards = $is_coaching
            ? [ // Coaching
                [0, 0.1],
                [1, 0.12],
                [2, 0.14],
                [5, 0.16],
                [10, 0.2],
            ]
            : [ // Premium
                [0, 250.0],
                [1, 300.0],
                [2, 350.0],
            ];

        $ref_count = $this->referral_count($is_coaching);

        foreach ($rewards as [$min_refs, $reward]) {
            if ($ref_count >= $min_refs) {
                $selected_reward = $reward;
            } else {
                break;
            }
        }

        $this->add_reward($selected_reward ?? 0, $is_coaching);
    }

    public function referral_count($is_coaching): int {
        global $DB;

        return $DB->count_records(tables::TABLE_REFERRAL_PAYMENT, array('ownerid' => $this->ownerid, 'is_coaching' => $is_coaching));
    }

    public function add_reward($value, $is_coaching) {
        global $DB;

        $increase_by = $is_coaching ? $this->price->price * $value : $value;

        $sql = "UPDATE {".tables::TABLE_REFERRAL_OWNER."} 
            SET balance = balance + :increase_by 
            WHERE id = :userid";

        $DB->execute($sql, ['increase_by' => $increase_by, 'userid' => $this->ownerid]);

        core::log_message("[REFERRAL] Balance is increased by: $increase_by from userid: {$this->user->id} for the ownerid: $this->ownerid");
    }

    public static function create_link($userid) {
        if (empty($userid))
            return;

        if (!self::is_user_owner($userid)) {
            $code = gen_safe_string(16);

            core::log_message("[REFERRAL] Create referral code: $code for userid: $userid");

            self::create_owner($userid, $code);
        }
    }


    //STATIC PART
    public static function is_user_bonus_allow($userid): bool {
        global $DB;

        if (empty($userid))
            return false;

        return $DB->record_exists(tables::TABLE_REFERRAL_INVITED, array('userid' => $userid, 'is_bonus_allow' => 1));
    }

    public static function is_user_owner($userid): bool {
        global $DB;

        if (empty($userid))
            return false;

        return $DB->record_exists(tables::TABLE_REFERRAL_OWNER, array('userid' => $userid));
    }

    public static function is_user_referral($userid): bool {
        global $DB;

        if (empty($userid))
            return false;

        return $DB->record_exists(tables::TABLE_REFERRAL_INVITED, array('userid' => $userid));
    }

    public static function who_user_referral($userid) {
        global $DB;

        if (empty($userid))
            return false;

        if (!self::is_user_referral($userid))
            return false;

        $owner = $DB->get_record(tables::TABLE_REFERRAL_INVITED, array('userid' => $userid));

        return $owner->ownerid;
    }

    public static function create_owner($userid, $code) {
        global $DB;

        if (empty($userid) || empty($code))
            return false;

        return $DB->insert_record(tables::TABLE_REFERRAL_OWNER, array('userid' => $userid, 'code' => $code, 'timecreated' => time(), 'balance' => 0.0));
    }

    public static function is_code_valid($code): bool {
        global $DB;

        if (empty($code))
            return false;

        return $DB->record_exists(tables::TABLE_REFERRAL_OWNER, array('code' => $code));
    }

    public static function set_referral($userid, $code) {
        global $DB;

        if (empty($userid) || empty($code))
            return false;

        $owner = self::get_owner_by_code($code);

        if (empty($owner))
            return false;

        core::log_message("[REFERRAL] Invite referral userid: $userid by ownerid: $owner->id with code: $code");

        return $DB->insert_record(tables::TABLE_REFERRAL_INVITED, array('userid' => $userid, 'ownerid' => $owner->id, 'is_bonus_allow' => 1, 'timecreated' => time()));
    }

    public static function get_owner_by_code($code) {
        global $DB;

        if (empty($code))
            return false;

        return $DB->get_record(tables::TABLE_REFERRAL_OWNER, array('code' => $code));
    }

    public static function get_owner_by_userid($userid) {
        global $DB;

        if (empty($userid))
            return false;

        return $DB->get_record(tables::TABLE_REFERRAL_OWNER, array('userid' => $userid));
    }

    public static function get_code($userid) {
        global $DB;

        if (empty($userid))
            return null;

        $owner = $DB->get_record(tables::TABLE_REFERRAL_OWNER, array('userid' => $userid));

        return $owner->code;
    }

    public static function get_balance($userid) {
        if (empty($userid))
            return 0.0;

        $owner = self::get_owner_by_userid($userid);

        if (empty($owner))
            return 0.0;

        return $owner->balance;
    }

    public static function update_balance($userid, $sum): bool {
        global $DB;

        if (empty($userid) || empty($sum))
            return false;

        $owner = self::get_owner_by_userid($userid);

        if (empty($owner))
            return false;

        $new_balance = $owner->balance + $sum;

        return $DB->set_field(tables::TABLE_REFERRAL_OWNER, 'balance', $new_balance, ['userid' => $userid]);
    }

    public static function get_friends($userid): int {
        global $DB;

        if (empty($userid))
            return 0;

        $sql = "SELECT COUNT(r.id)
        FROM {".tables::TABLE_REFERRAL_INVITED."} r
        JOIN {".tables::TABLE_REFERRAL_OWNER."} o ON r.ownerid = o.id
        WHERE o.userid = :userid";

        return $DB->count_records_sql($sql, ['userid' => $userid]);
    }

    public static function get_purchased($userid): int {
        global $DB;

        if (empty($userid))
            return 0;

        $sql = "SELECT COUNT(p.id)
        FROM {".tables::TABLE_REFERRAL_PAYMENT."} p
        JOIN {".tables::TABLE_REFERRAL_OWNER."} o ON p.ownerid = o.id
        WHERE o.userid = :userid";

        return $DB->count_records_sql($sql, ['userid' => $userid]);
    }

    public static function is_valid_coupon($input): bool {
        $coupons = self::coupon_checker();

        if (empty($coupons))
            return false;

        foreach ($coupons as $coupon) {
            foreach ($coupon as $key => $data) {
                if ($key === $input)
                    return true;

                if (isset($data['id']) && $data['id'] === $input)
                    return true;
            }
        }
        return false;
    }

    public static function get_coupon($is_id, $is_coaching) {
        $coupons = self::coupon_checker();
        if (empty($coupons))
            return false;

        foreach ($coupons as $coupon) {
            foreach ($coupon as $key => $data) {
                if (isset($data['is_coaching']) && $data['is_coaching'] === $is_coaching)
                    return $is_id ? ($data['id'] ?? null) : $key;
            }
        }
        return false;
    }
}
