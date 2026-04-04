<?php

namespace auth_stripe\task;

use auth_stripe\core;
use auth_stripe\model\user_promo_banner;

/**
 * Created course task
 */
class cleanup_expired_user_promobanner extends \core\task\scheduled_task {

    public function get_name(){
        return core::str('task:cleanup_expired_user_promobanner');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        $time = time();
        $banners_to_delete = user_promo_banner::get_all([
            'type' => user_promo_banner::TYPE_NEW_USER,
            'sql'  => 'timedue <= '.$time,
        ], '', 'id');

        if (empty($banners_to_delete)){
            mtrace('Nothing to delete');
            return;
        }

        mtrace('Will be deleted '.count($banners_to_delete).' promobanners');
        mtrace('Data:'.PHP_EOL.print_r($banners_to_delete, 1));
        $ids = array_keys($banners_to_delete);

        user_promo_banner::delete_records([
            'id' => $ids,
        ]);
    }
}