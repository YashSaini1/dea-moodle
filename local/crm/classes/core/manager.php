<?php

namespace local_crm\core;

use Exception;
use local_crm\core;

abstract class manager
{
    private array $data;
    private array $http_header;
    private string $endpoint;

    public function set_data($data) {
        $this->data = $data;
    }

    public function set_endpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    public function set_http_header($http_header) {
        $this->http_header = $http_header;
    }

    protected static function validate_user_data($user): bool {
        $email = mb_strtolower(trim($user->email ?? ''));
        $firstname = mb_strtolower(trim($user->firstname ?? ''));
        $lastname = mb_strtolower(trim($user->lastname ?? ''));

        if ($firstname === $email && $lastname === $email) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function send_data(string $method = 'POST') {
        if (empty($this->data) || empty($this->endpoint) || empty($this->http_header))
            return;

        $ch = curl_init($this->endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->http_header);

        core::log_message("[CRM] [$this->endpoint] Sending data (Method: $method): " . print_r($this->data, true));

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            core::log_message("[CRM] [$this->endpoint] cURL error: $error");
            return;
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code < 200 || $code >= 300)
            core::log_message("[CRM] [$this->endpoint] Error response: " . print_r($result, true));

        curl_close($ch);

        core::log_message("[CRM] [$this->endpoint] Complete with code $code");
    }

    /**
     * @throws Exception
     */
    protected function find_existing_email(string $email, array $http_header, string $endpoint): ?object {
        if (empty($email))
            return null;

        $url = $endpoint . $this->build_search_query($email);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            core::log_message("[CRM] [$url] cURL error: $error");
            return null;
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code >= 200 && $code < 300) {
            $response = json_decode($result);
            if (!empty($response->data)) {
                core::log_message("[CRM] [$url] User already exists");
                return $response->data[0];
            }
        } else {
            core::log_message("[CRM] [$url] Error response: " . print_r($result, true));
        }

        curl_close($ch);
        core::log_message("[CRM] [$url] User not found");
        return null;
    }

    abstract protected function build_search_query(string $email): string;
}
