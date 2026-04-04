<?php

namespace auth_stripe\task;

use auth_stripe\core;
use auth_stripe\model\price;
use auth_stripe\model\product;
use auth_stripe\util\price_display_util;
use local_sql\moodle\role_manager;
use local_sql\moodle\user;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Created course task
 */
class send_seller_email extends \core\task\adhoc_task {

    public function get_name(){
        return core::str('task:send_seller_email');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $customdata = (array)$this->get_custom_data();

        $product = new product($customdata['product']);
        $price = new price($customdata['price']);
        $paid_user = \core_user::get_user($customdata['userid']);
        $supportuser = \core_user::get_support_user();

        $user_name = ($paid_user->firstname == $paid_user->email && $paid_user->lastname == $paid_user->email)
            ? $paid_user->email
            : fullname($paid_user);
        $data = [
            'fullname'    => $user_name,
            'productname' => $product->name,
            'pricename'   => $price->plan_name,
            'amount'      => price_display_util::format_price($price->get_full_amount()),
            'currency'    => strtoupper($price->currency),
        ];

        mtrace('Email data: '.print_r($data, 1));

        $subject = core::str('email:user_paid_seller_subject', $data);
        $message = core::str('email:user_paid_seller_message', $data);

        $roles = [
            role_manager::ADMIN_ROLE,
            role_manager::SELLER_ROLE,
        ];
        $sellers = user::get_users_with_role($roles, null, true);
        mtrace('Will send payment message to '.count($sellers).' users');

        foreach ($sellers as $seller){
            if (email_to_user($seller, $supportuser, $subject, $message, null, null, null, null)){
                mtrace('Send payment message to user '.$seller->id.' with email '.$seller->email);
            } else {
                mtrace('Cannot send payment message to user '.$seller->id.' with email '.$seller->email);
            }
        }
    }
}