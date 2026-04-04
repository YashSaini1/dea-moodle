<?php
namespace local_crm\event;

use Exception;
use local_crm\core;
use local_crm\core\close\close;
use local_crm\core\ontraport\ontraport;

class observer {

    public static function observer(\core\event\user_created $event) {
        $user = \core_user::get_user($event->objectid);
        if ($user) {
            try {
                close::execute($user);
                ontraport::execute($user);
            } catch (Exception $e) {
                core::log_message("Error in crm {$e->getMessage()}");
            }
        }
        return true;
    }
}