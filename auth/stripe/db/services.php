<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Auth email webservice definitions.
 *
 * @package     auth_stripe
 * @copyright   2021 Kirill Slyusar
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'auth_stripe_get_signup_settings' => array(
        'classname'     => 'auth_stripe_external',
        'classpath'     => 'auth/stripe/classes/external.php',
        'methodname'    => 'get_signup_settings',
        'description'   => 'Get the signup required settings and profile fields.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ),
    'auth_stripe_validate_coupon'     => array(
        'classname'     => '\auth_stripe\auth_stripe_external',
        'classpath'     => 'auth/stripe/classes/external.php',
        'methodname'    => 'validate_coupon',
        'description'   => 'Validate inputted coupon code',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ),
    'auth_stripe_update_coupon'       => array(
        'classname'     => '\auth_stripe\auth_stripe_external',
        'classpath'     => 'auth/stripe/classes/external.php',
        'methodname'    => 'update_coupon',
        'description'   => 'Disable/enable coupons endpoint',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'auth/stripe:edit_coupon',
    ),

    'auth_stripe_validate_promocode'     => array(
        'classname'     => '\auth_stripe\auth_stripe_external',
        'classpath'     => 'auth/stripe/classes/external.php',
        'methodname'    => 'validate_promocode',
        'description'   => 'Validate inputted promocode code',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ),
    'auth_stripe_update_promocode'       => array(
        'classname'     => '\auth_stripe\auth_stripe_external',
        'classpath'     => 'auth/stripe/classes/external.php',
        'methodname'    => 'update_promocode',
        'description'   => 'Disable/enable promocode endpoint',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'auth/stripe:edit_coupon',
    ),
);
