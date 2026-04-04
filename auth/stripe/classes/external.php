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

namespace auth_stripe;

use auth_stripe\model\coupon;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\promocode;
use auth_stripe\processor\coupon\coupon_price_processor;
use auth_stripe\processor\coupon\coupon_validator;
use auth_stripe\processor\promocode\promocode_validator;
use context_system;
use external_api;
use external_format_value;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use moodle_exception;

/**
 * Auth stripe external API
 *
 * @package    auth_stripe
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Auth stripe external functions
 *
 * @package    auth_stripe
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 */
class auth_stripe_external extends external_api {

    /**
     * Check if registration is enabled in this site.
     *
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    protected static function check_signup_enabled()
    {
        global $CFG;

        if (empty($CFG->registerauth) or $CFG->registerauth != 'email') {
            throw new moodle_exception('registrationdisabled', 'error');
        }
    }

    /**
     * Describes the parameters for validate_promocode.
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function validate_promocode_parameters(){
        return new external_function_parameters(array(
            'promocode' => new external_value(PARAM_ALPHANUM, 'Promocode name'),
        ));
    }

    /**
     * Validate inputted coupon
     *
     * @return array settings and possible warnings
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    public static function validate_promocode($promocode){
        global $PAGE;

        $context = context_system::instance();
        // We need this to make work the format text functions.
        $PAGE->set_context($context);

        $promocode_validator = new promocode_validator();
        $result = $promocode_validator->validate($promocode);
        $result['error'] = '';

        return $result;
    }

    /**
     * Describes the validate_promocode return value.
     *
     * @return external_single_structure
     */
    public static function validate_promocode_returns(){
        return new external_single_structure([
            'valid'       => new external_value(PARAM_BOOL, 'Is promocode valid', VALUE_REQUIRED),
            'error'       => new external_value(PARAM_RAW, 'Validation error message', VALUE_OPTIONAL),
            'percent_off' => new external_value(PARAM_FLOAT, 'Promocode percent off from price', VALUE_OPTIONAL),
            'amount_off'  => new external_value(PARAM_FLOAT, 'Promocode fixed amount off', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for validate_coupon.
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function validate_coupon_parameters(){
        return new external_function_parameters(array(
            'coupon' => new external_value(PARAM_RAW_TRIMMED, 'Coupon name'),
            'prices' => new external_multiple_structure(new external_value(PARAM_INT),
                'Array of price ids for which needs to recalculate pricesum'),
        ));
    }

    /**
     * Validate inputted coupon
     *
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    public static function validate_coupon($coupon, $prices){
        global $PAGE;

        $context = context_system::instance();
        // We need this to make work the format text functions.
        $PAGE->set_context($context);

        $coupon_validator = new coupon_validator();
        $coupon = $coupon_validator->validate($coupon);
        if (!$coupon) {
            return [
                'valid' => false,
                'error' => core::str('coupon:error:invalid_coupon'),
            ];
        }

        $price_processor = new coupon_price_processor($coupon);
        $result = [
            'valid'  => true,
            'coupon_description' => $price_processor->generate_coupon_user_message(),
            'price_info' => $price_processor->generate_new_prices_name($prices)
        ];

        return $result;
    }

    /**
     * Describes the validate_coupon return value.
     *
     * @return external_single_structure
     */
    public static function validate_coupon_returns(){
        return new external_single_structure([
            'valid'       => new external_value(PARAM_BOOL, 'Is coupon valid', VALUE_REQUIRED),
            'error'       => new external_value(PARAM_RAW, 'Coupon validation error message', VALUE_OPTIONAL),
            'coupon_description' => new external_value(PARAM_RAW, 'Applied coupon user message', VALUE_OPTIONAL),
            'price_info'  => new external_multiple_structure(
                new external_single_structure([
                    'price_id' => new external_value(PARAM_INT, 'Price id', VALUE_REQUIRED),
                    'price_discount' => new external_value(PARAM_RAW, 'New price sum html after coupon applying',VALUE_REQUIRED),
                ]),
                'Updated priced information', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for validate_coupon.
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function update_coupon_parameters(){
        return new external_function_parameters(array(
            'couponid' => new external_value(PARAM_INT, 'Moodle coupon id'),
            'state'    => new external_value(PARAM_INT, 'if 1, need to enable coupon. If 0 - disable'),
        ));
    }

    /**
     * Validate inputted coupon
     */
    public static function update_coupon($couponid, $state){
        $result = [
            'result' => true,
            'error'  => '',
        ];

        $coupon = coupon::get_by_id($couponid);
        if (!$coupon){
            $result['result'] = false;
            $result['error'] = 'Invalid coupon!';
            return $result;
        }

        $promobanner_settings = promobanner_config::get_instance();
        if ($promobanner_settings->enabled && $coupon->name == $promobanner_settings->couponname && $state == 0){
            $result['result'] = false;
            $result['error'] = 'You cannot disable promobanner coupon! Please, disable promobanner first.';
            return $result;
        }

        if ($coupon->enabled == $state){
            // nothing to do
            return $result;
        }

        if ($state > 1){
            $state = 1;
        } elseif ($state < 0) {
            $state = 0;
        }

        $coupon->enabled = (int)$state;
        $coupon->save();

        core::info('Update coupon '.$coupon->id.' to state '.$state);
        return $result;
    }

    /**
     * Describes the validate_coupon return value.
     *
     * @return external_single_structure
     */
    public static function update_coupon_returns(){
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Is coupon updated', VALUE_REQUIRED),
            'error'  => new external_value(PARAM_RAW, 'Coupon update error message', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for validate_coupon.
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function update_promocode_parameters(){
        return new external_function_parameters(array(
            'promocodeid' => new external_value(PARAM_INT, 'Moodle promocode id'),
            'state'    => new external_value(PARAM_INT, 'if 1, need to enable promocode. If 0 - disable'),
        ));
    }

    /**
     * Validate inputted coupon
     */
    public static function update_promocode($promocodeid, $state){
        $result = [
            'result' => true,
            'error'  => '',
        ];

        $promocode = promocode::get_by_id($promocodeid);
        if (!$promocode){
            $result['result'] = false;
            $result['error'] = 'Invalid promocode!';
            return $result;
        }

        if ($promocode->enabled == $state){
            // nothing to do
            return $result;
        }

        if ($state > 1){
            $state = 1;
        } elseif ($state < 0) {
            $state = 0;
        }

        $promocode->enabled = (int)$state;
        $promocode->save();

        core::info('Update promocode '.$promocode->id.' to state '.$state);
        return $result;
    }

    /**
     * Describes the validate_coupon return value.
     *
     * @return external_single_structure
     */
    public static function update_promocode_returns(){
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'Is promocode updated', VALUE_REQUIRED),
            'error'  => new external_value(PARAM_RAW, 'Promocode update error message', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for get_signup_settings.
     *
     * @return external_function_parameters
     * @since Moodle 3.2
     */
    public static function get_signup_settings_parameters()
    {
        return new external_function_parameters(array());
    }

    /**
     * Get the signup required settings and profile fields.
     *
     * @return array settings and possible warnings
     * @since Moodle 3.2
     * @throws moodle_exception
     */
    public static function get_signup_settings()
    {
        global $CFG, $PAGE;

        $context = context_system::instance();
        // We need this to make work the format text functions.
        $PAGE->set_context($context);

        self::check_signup_enabled();

        $result = array();
        $result['namefields'] = useredit_get_required_name_fields();

        if (!empty($CFG->passwordpolicy)) {
            $result['passwordpolicy'] = print_password_policy();
        }
        $manager = new \core_privacy\local\sitepolicy\manager();
        if ($sitepolicy = $manager->get_embed_url()) {
            $result['sitepolicy'] = $sitepolicy->out(false);
        }
        if (!empty($CFG->sitepolicyhandler)) {
            $result['sitepolicyhandler'] = $CFG->sitepolicyhandler;
        }
        if (!empty($CFG->defaultcity)) {
            $result['defaultcity'] = $CFG->defaultcity;
        }
        if (!empty($CFG->country)) {
            $result['country'] = $CFG->country;
        }

        if ($fields = profile_get_signup_fields()) {
            $result['profilefields'] = array();
            foreach ($fields as $field) {
                $fielddata = $field->object->get_field_config_for_external();
                $fielddata['categoryname'] = external_format_string($field->categoryname, $context->id);
                $fielddata['name'] = external_format_string($fielddata['name'], $context->id);
                list($fielddata['defaultdata'], $fielddata['defaultdataformat']) =
                    external_format_text($fielddata['defaultdata'], $fielddata['defaultdataformat'], $context->id);

                $result['profilefields'][] = $fielddata;
            }
        }

        if (signup_captcha_enabled()) {
            // With reCAPTCHA v2 the captcha will be rendered by the mobile client using just the publickey.
            $result['recaptchapublickey'] = $CFG->recaptchapublickey;
        }

        $result['warnings'] = array();
        return $result;
    }

    /**
     * Describes the get_signup_settings return value.
     *
     * @return external_single_structure
     * @since Moodle 3.2
     */
    public static function get_signup_settings_returns()
    {

        return new external_single_structure(
            array(
                'namefields' => new external_multiple_structure(
                    new external_value(PARAM_NOTAGS, 'The order of the name fields')
                ),
                'passwordpolicy' => new external_value(PARAM_RAW, 'Password policy', VALUE_OPTIONAL),
                'sitepolicy' => new external_value(PARAM_RAW, 'Site policy', VALUE_OPTIONAL),
                'sitepolicyhandler' => new external_value(PARAM_PLUGIN, 'Site policy handler', VALUE_OPTIONAL),
                'defaultcity' => new external_value(PARAM_NOTAGS, 'Default city', VALUE_OPTIONAL),
                'country' => new external_value(PARAM_ALPHA, 'Default country', VALUE_OPTIONAL),
                'profilefields' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Profile field id', VALUE_OPTIONAL),
                            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Profile field shortname', VALUE_OPTIONAL),
                            'name' => new external_value(PARAM_RAW, 'Profield field name', VALUE_OPTIONAL),
                            'datatype' => new external_value(PARAM_ALPHANUMEXT, 'Profield field datatype', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_RAW, 'Profield field description', VALUE_OPTIONAL),
                            'descriptionformat' => new external_format_value('description'),
                            'categoryid' => new external_value(PARAM_INT, 'Profield field category id', VALUE_OPTIONAL),
                            'categoryname' => new external_value(PARAM_RAW, 'Profield field category name', VALUE_OPTIONAL),
                            'sortorder' => new external_value(PARAM_INT, 'Profield field sort order', VALUE_OPTIONAL),
                            'required' => new external_value(PARAM_INT, 'Profield field required', VALUE_OPTIONAL),
                            'locked' => new external_value(PARAM_INT, 'Profield field locked', VALUE_OPTIONAL),
                            'visible' => new external_value(PARAM_INT, 'Profield field visible', VALUE_OPTIONAL),
                            'forceunique' => new external_value(PARAM_INT, 'Profield field unique', VALUE_OPTIONAL),
                            'signup' => new external_value(PARAM_INT, 'Profield field in signup form', VALUE_OPTIONAL),
                            'defaultdata' => new external_value(PARAM_RAW, 'Profield field default data', VALUE_OPTIONAL),
                            'defaultdataformat' => new external_format_value('defaultdata'),
                            'param1' => new external_value(PARAM_RAW, 'Profield field settings', VALUE_OPTIONAL),
                            'param2' => new external_value(PARAM_RAW, 'Profield field settings', VALUE_OPTIONAL),
                            'param3' => new external_value(PARAM_RAW, 'Profield field settings', VALUE_OPTIONAL),
                            'param4' => new external_value(PARAM_RAW, 'Profield field settings', VALUE_OPTIONAL),
                            'param5' => new external_value(PARAM_RAW, 'Profield field settings', VALUE_OPTIONAL),
                        )
                    ),
                    'Required profile fields',
                    VALUE_OPTIONAL
                ),
                'recaptchapublickey' => new external_value(PARAM_RAW, 'Recaptcha public key', VALUE_OPTIONAL),
                'recaptchachallengehash' => new external_value(PARAM_RAW, 'Recaptcha challenge hash', VALUE_OPTIONAL),
                'recaptchachallengeimage' => new external_value(PARAM_URL, 'Recaptcha challenge noscript image', VALUE_OPTIONAL),
                'recaptchachallengejs' => new external_value(PARAM_URL, 'Recaptcha challenge js url', VALUE_OPTIONAL),
                'warnings'  => new external_warnings(),
            )
        );
    }
}
