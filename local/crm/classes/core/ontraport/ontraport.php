<?php

namespace local_crm\core\ontraport;

use Exception;
use local_crm\core;
use local_crm\core\manager;

require_once($CFG->dirroot.'/local/crm/lib.php');

class ontraport extends manager {

    const endpoint = "https://api.ontraport.com/1/Contacts";

    /**
     * @throws Exception
     */
    public static function execute($user): bool {
        core::log_message("Starting ontaport sender");

        if (!self::validate_user_data($user)) {
            core::log_message("Skipping Ontraport CRM: firstname and lastname equal to email.");
            return false;
        }

        $config = get_config('local_crm');

        if (empty($config->ontraport_is_send) || empty($config->ontraport_api_key) || empty($config->ontraport_app_id))
            return false;

        $http_header = [
            'Api-Key: ' . $config->ontraport_api_key,
            'Api-Appid: ' . $config->ontraport_app_id,
            'Content-Type: application/json',
        ];

        $email = $user->email;
        $phone = add_plus($user->phone1);

        $existing_contact = (new static())->find_existing_email($email, $http_header, self::endpoint);

        if ($existing_contact)
            return true;

        $data = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $email,
        ];

        if (!empty($phone)) {
            $data['sms_number'] = $phone;
        }

        $instance = new static();
        $instance->set_http_header($http_header);
        $instance->set_endpoint(static::endpoint);
        $instance->set_data($data);
        $instance->send_data();

        return true;
    }

    protected function build_search_query(string $email): string {
        $query_params = [];
        $query_params[] = '{"field":{"field":"email"},"op":"=","value":{"value":"' . $email . '"}}';

        $condition = '[' . implode(',"OR",', $query_params) . ']';
        return '?range=50&condition=' . urlencode($condition);
    }

}
