<?php

namespace local_myapi\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class user_external extends \external_api
{

    /**
     * Parameters
     */
    public static function get_users_parameters()
    {
        return new \external_function_parameters([
            'search' => new \external_value(PARAM_TEXT, 'Search text', VALUE_DEFAULT, ''),
            'page' => new \external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new \external_value(PARAM_INT, 'Users per page', VALUE_DEFAULT, 10)
        ]);
    }

    /**
     * Main Function
     */
    public static function get_users($search = '', $page = 0, $perpage = 10)
    {
        global $DB;

        // Validate params
        $params = self::validate_parameters(
            self::get_users_parameters(),
            [
                'search' => $search,
                'page' => $page,
                'perpage' => $perpage
            ]
        );

        $offset = $params['page'] * $params['perpage'];

        // Base query
        $sql = "SELECT id, username, firstname, lastname, email, auth, city, country, institution,
                       department, phone1, phone2, address, confirmed, suspended, deleted,
                       firstaccess, lastaccess, lastlogin, timecreated, timemodified, lang,
                       timezone, description
                FROM {user}
                WHERE deleted = 0 AND suspended = 0";

        $sqlparams = [];

        // Search filter
        if (!empty($params['search'])) {
            $sql .= " AND (
                firstname LIKE :searchfirstname OR
                lastname LIKE :searchlastname OR
                email LIKE :searchemail OR
                username LIKE :searchusername
            )";
            $searchvalue = '%' . $params['search'] . '%';
            $sqlparams['searchfirstname'] = $searchvalue;
            $sqlparams['searchlastname'] = $searchvalue;
            $sqlparams['searchemail'] = $searchvalue;
            $sqlparams['searchusername'] = $searchvalue;
        }

        $sql .= " ORDER BY id DESC";

        // Get users
        $users = $DB->get_records_sql($sql, $sqlparams, $offset, $params['perpage']);

        foreach ($users as $user) {
            $user->fullname = fullname($user);
            $user->profile = self::format_profile_fields($user->id);
            $user->tiers = self::format_user_tiers($user->id);
        }

        // Count total
        $countsql = "SELECT COUNT(id)
                     FROM {user}
                     WHERE deleted = 0 AND suspended = 0";

        if (!empty($params['search'])) {
            $countsql .= " AND (
                firstname LIKE :searchfirstname OR
                lastname LIKE :searchlastname OR
                email LIKE :searchemail OR
                username LIKE :searchusername
            )";
        }

        $total = $DB->count_records_sql($countsql, $sqlparams);

        return [
            'users' => array_values($users),
            'total' => $total,
            'page' => $params['page'],
            'perpage' => $params['perpage']
        ];
    }

    /**
     * Format custom profile fields for the API response.
     *
     * @param int $userid
     *
     * @return array<int, array{shortname:string, value:mixed}>
     */
    private static function format_profile_fields(int $userid): array
    {
        $profilefields = profile_user_record($userid, false);
        $result = [];

        foreach (get_object_vars($profilefields) as $shortname => $value) {
            $result[] = [
                'shortname' => $shortname,
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * Format subscription tiers for the API response.
     *
     * @param int $userid
     *
     * @return array<int, array<string, mixed>>
     */
    private static function format_user_tiers(int $userid): array
    {
        $tiers = \auth_stripe\subscription\tier_price_loader::get_all_by_user($userid);
        $result = [];

        foreach ($tiers as $tierentity) {
            if (empty($tierentity->tier)) {
                continue;
            }

            $tier = $tierentity->tier;
            $product = \auth_stripe\model\user_tier::get_product($tier->tier);

            $result[] = [
                'id' => (int) $tier->id,
                'tier' => (int) $tier->tier,
                'product_name' => $product ? (string) $product->name : '',
                'product_page' => $product ? (string) $product->sql_page : '',
                'can_cancel' => (int) !empty($tier->can_cancel),
                'time_created' => (int) ($tier->time_created ?? 0),
                'time_cancelled' => (int) ($tier->time_cancelled ?? 0),
                'current_period_start' => (int) ($tier->current_period_start ?? 0),
                'current_period_end' => (int) ($tier->current_period_end ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Return structure
     */
    public static function get_users_returns()
    {
        return new \external_single_structure([
            'users' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT),
                    'username' => new \external_value(PARAM_TEXT),
                    'firstname' => new \external_value(PARAM_TEXT),
                    'lastname' => new \external_value(PARAM_TEXT),
                    'email' => new \external_value(PARAM_TEXT),
                    'auth' => new \external_value(PARAM_TEXT),
                    'city' => new \external_value(PARAM_TEXT),
                    'country' => new \external_value(PARAM_TEXT),
                    'institution' => new \external_value(PARAM_TEXT),
                    'department' => new \external_value(PARAM_TEXT),
                    'phone1' => new \external_value(PARAM_TEXT),
                    'phone2' => new \external_value(PARAM_TEXT),
                    'address' => new \external_value(PARAM_TEXT),
                    'confirmed' => new \external_value(PARAM_INT),
                    'suspended' => new \external_value(PARAM_INT),
                    'deleted' => new \external_value(PARAM_INT),
                    'firstaccess' => new \external_value(PARAM_INT),
                    'lastaccess' => new \external_value(PARAM_INT),
                    'lastlogin' => new \external_value(PARAM_INT),
                    'timecreated' => new \external_value(PARAM_INT),
                    'timemodified' => new \external_value(PARAM_INT),
                    'lang' => new \external_value(PARAM_TEXT),
                    'timezone' => new \external_value(PARAM_TEXT),
                    'description' => new \external_value(PARAM_RAW),
                    'fullname' => new \external_value(PARAM_TEXT),
                    'profile' => new \external_multiple_structure(
                        new \external_single_structure([
                            'shortname' => new \external_value(PARAM_TEXT),
                            'value' => new \external_value(PARAM_RAW, 'Custom profile field value'),
                        ])
                    ),
                    'tiers' => new \external_multiple_structure(
                        new \external_single_structure([
                            'id' => new \external_value(PARAM_INT),
                            'tier' => new \external_value(PARAM_INT),
                            'product_name' => new \external_value(PARAM_TEXT),
                            'product_page' => new \external_value(PARAM_TEXT),
                            'can_cancel' => new \external_value(PARAM_INT),
                            'time_created' => new \external_value(PARAM_INT),
                            'time_cancelled' => new \external_value(PARAM_INT),
                            'current_period_start' => new \external_value(PARAM_INT),
                            'current_period_end' => new \external_value(PARAM_INT),
                        ])
                    )
                ])
            ),
            'total' => new \external_value(PARAM_INT),
            'page' => new \external_value(PARAM_INT),
            'perpage' => new \external_value(PARAM_INT)
        ]);
    }
}
