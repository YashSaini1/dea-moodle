<?php

namespace auth_stripe\model\price;

use auth_stripe\core\stripe_database;

/**
 * Moodle price_description entity
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class price_description extends price_model {

    static protected string $table = stripe_database::TABLE_PRICE_DESCRIPTION;

    public $id;
    public string $info;

    /** @var numeric local price id */
    public string $priceid;
}