<?php

namespace local_crm\core\close;

use Exception;
use local_crm\core;
use local_crm\core\manager;

require_once($CFG->dirroot.'/local/crm/lib.php');

class close extends manager {

    const endpoint = "https://api.close.com/api/v1/lead/";

    /**
     * @throws Exception
     */
    public static function execute($user): bool {
        core::log_message("Starting close sender");

        if (!self::validate_user_data($user)) {
            core::log_message("Skipping Close CRM: firstname and lastname equal to email.");
            return false;
        }

        $config = get_config('local_crm');

        if (empty($config->closecrm_is_send) || empty($config->closecrm_api_key))
            return false;

        $http_header = [
            'Authorization: Basic ' . base64_encode($config->closecrm_api_key . ':'),
            'Content-Type: application/json',
        ];

        $email = $user->email;
        $phone = add_plus($user->phone1);

        $existing_lead = (new static())->find_existing_email($email, $http_header, self::endpoint);

        $data = [
            "name" => $user->firstname . ' ' . $user->lastname,
            "contacts" => [
                [
                    "name" => $user->firstname . ' ' . $user->lastname,
                    "emails" => [
                        [
                            "type" => "direct",
                            "email" => $email
                        ]
                    ],
                ],
            ],
        ];

        if (!empty($phone)) {
            $data['contacts'][0]['phones'] = [
                [
                    "type" => "direct",
                    "phone" => $phone
                ],
            ];
        }

        $instance = new static();
        $instance->set_http_header($http_header);

        if ($existing_lead) {
            $instance->set_endpoint(static::endpoint . $existing_lead->id . '/');
            $instance->set_data($data);
            $instance->send_data('PUT');
        } else {
            $instance->set_endpoint(static::endpoint);
            $instance->set_data($data);
            $instance->send_data();
        }

        return true;
    }

    protected function build_search_query(string $email): string {
        $query_params = [];
        $query_params[] = 'email:"' . $email . '"';

        return '?query=' . urlencode(implode(' or ', $query_params));
    }

}
