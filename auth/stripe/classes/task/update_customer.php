<?php

namespace auth_stripe\task;

use auth_stripe\core;
use auth_stripe\stripe;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Created course task
 */
class update_customer extends \core\task\adhoc_task {

    public function get_name(){
        return core::str('task:update_customer');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(){
        global $DB;
        $customdata = (array)$this->get_custom_data();

        $user = $DB->get_record('user', ['id' => $customdata['userid']]);
        $stripe = new stripe($user);
        $stripe->update_customer((array)$customdata['updated_fields']);
    }
}