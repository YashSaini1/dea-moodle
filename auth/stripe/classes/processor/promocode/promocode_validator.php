<?php

namespace auth_stripe\processor\promocode;

use auth_stripe\core;
use auth_stripe\loaders\coupon_promocode_loader;
use auth_stripe\model\promocode;

class promocode_validator {

    public function validate($code, $user = null){
        if (empty($code)){
            return $this->invalid();
        }

        $coupon_promocode = coupon_promocode_loader::get($code);
        if (!$coupon_promocode){
            return $this->invalid();
        }

        $coupon = $coupon_promocode->coupon;
        $promocode = $coupon_promocode->promocode;

        if (!$coupon || !$promocode){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use undefined promocode '.$code.' . No such coupon or promocode');
            return $this->invalid();
        }

        if (!$coupon->enabled){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use disabled coupon promocode '.$code.' with id '.$coupon->id);
            return $this->invalid();
        }

        if (!$promocode->enabled){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use disabled promocode '.$code.' with id '.$coupon->id);
            return $this->invalid();
        }

        $error = $this->promocode_allowed_for_user($promocode);
        if ($error !== true){
            global $USER;
            core::debug('User '.fullname($USER).' with id '.$USER->id.' trying to use not allowed for him coupon '.$code.' with id '
                .$coupon->id.'. Not allowed reason is '.$error);
            return $this->invalid();
        }

        return [
            'valid'       => true,
            'id'          => $promocode->stripeid,
            'amount_off'  => $coupon->amount_off,
            'percent_off' => $coupon->percent_off,
        ];
    }

    protected function promocode_allowed_for_user(promocode $coupon, $user = null){
        return true;
        // TODO: user validation staff here
//        if (empty($user)){
//            global $USER;
//            $user = $USER;
//        }
    }

    protected function invalid(){
        return [
            'valid' => false,
            'error' => core::str('promocode:error:invalid_promocode'),
        ];
    }
}