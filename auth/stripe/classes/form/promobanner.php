<?php

namespace auth_stripe\form;

use auth_stripe\core;
use auth_stripe\model\coupon;
use auth_stripe\model\dto\promobanner_config;
use auth_stripe\model\price;
use auth_stripe\model\user_promo_banner;
use auth_stripe\processor\coupon\coupon_validator;

require_once($CFG->libdir.'/formslib.php');

class promobanner extends \moodleform {

    /**
     * @var promobanner_config
     */
    protected $_config;

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;
        $this->_config = $this->_customdata['config'];

        $mform->addElement('selectyesno', 'enabled', core::str('promobanner:enabled'));
        $mform->setType('enabled', PARAM_BOOL);

        $mform->addElement('selectyesno', 'blackfriday', core::str('promobanner:event:blackfriday'));
        $mform->setType('blackfriday', PARAM_BOOL);

        $mform->addElement('text', 'couponname', core::str('promobanner:coupon'));
        $mform->setType('couponname', PARAM_TEXT);

        $mform->addElement('text', 'banner_text', core::str('promobanner:banner_text'), [
            'size' => 40
        ]);
        $mform->setType('banner_text', PARAM_TEXT);

        $mform->addElement('text', 'banner_text_short', core::str('promobanner:banner_text_short'),[
            'size' => 40
        ]);
        $mform->setType('banner_text_short', PARAM_TEXT);

        $mform->addElement('text', 'duration', core::str('promobanner:banner_duration'));
        $mform->setType('duration', PARAM_TEXT);

        $mform->addElement('select', 'duration_period', core::str('promobanner:banner_duration_period'), user_promo_banner::PERIODS);
        $mform->setType('duration_period', PARAM_INT);

        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechanges'), $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);
        if ($data['enabled'] == 0){
            return $errors;
        }

        if (empty($data['couponname'])){
            $errors['couponname'] = 'Coupon cannot be empty';
        } else {
            $coupon = coupon::get([
                'name' => $data['couponname'],
            ]);
            if (!$coupon){
                $errors['couponname'] = 'Coupon is not exists';
            } elseif (!$coupon->enabled){
                $errors['couponname'] = 'Coupon is disabled';
            }
        }

        if (empty($data['banner_text'])){
            $errors['banner_text'] = 'Banner text cannot be empty';
        }
        if (empty($data['banner_text_short'])){
            $errors['banner_text_short'] = 'Short banner text cannot be empty';
        }

        return $errors;
    }
}