<?php

namespace auth_stripe\processor\coupon;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\user_promo_banner;
use auth_stripe\processor\promobanner\user_promo_banner_renderer;

class coupon_validator {

    public const STATE_SUCCESS = 1;
    public const STATE_NOT_FOUND = 2;
    public const STATE_DISABLED = 3;
    public const STATE_NOT_ALLOWED = 4;

    public function validate($coupon_name, $user = null){
        if (empty($coupon_name)){
            return static::STATE_NOT_FOUND;
        }

        $coupon = coupon::get([
            'name' => $coupon_name,
        ]);

        if (user_promo_banner_renderer::black_friday() && $coupon_name == user_promo_banner_renderer::$black_friday_coupon) { // dubious code
            return $coupon;
        }

        $result = $this->_validate_coupon($coupon, $user);
        if ($result != static::STATE_SUCCESS){
            return null;
        }

        return $coupon;
    }

    protected function _validate_coupon($coupon, $user = null){
        if (!$coupon){
            return static::STATE_NOT_FOUND;
        }

        if (!$coupon->enabled){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use disabled coupon '.$coupon->name.' with id '.$coupon->id);
            return static::STATE_DISABLED;
        }

        $error = $this->coupon_allowed_for_user($coupon, $user);
        if ($error !== true){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use not allowed for him coupon '.$coupon->name.' with id '
                .$coupon->id.'. Not allowed reason is '.$error);
            return static::STATE_NOT_ALLOWED;
        }

        return static::STATE_SUCCESS;
    }

    protected function coupon_allowed_for_user(coupon $coupon, $user = null){
        // if promobanner disabled, return
        $promobanner_config = promobanner_config::get_instance();
        if (!$promobanner_config->enabled){
            return true;
        }

        // if $coupon is not promobanner, return
        if ($promobanner_config->couponname != $coupon->name){
            return true;
        }

        global $USER;
        $user = $user ?? $USER;
        $promobanner = $user->{user_promo_banner::USER_FIELD} ?? null;
        // if $user is equals to $USER. In this case, promobanner record should should exists
        if ($promobanner){
            return $promobanner->timedue > time();
        }

        // just load record, because method user_promo_banner::get_by_user() already have timedue validation
        return !empty(user_promo_banner::get_by_user($user));
    }
}