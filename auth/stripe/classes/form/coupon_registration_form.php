<?php

namespace auth_stripe\form;

use auth_stripe\core;

require_once ($CFG->libdir . '/formslib.php');

class coupon_registration_form extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;
        $oldclass = $mform->getAttribute('class');
        if (!empty($oldclass)){
            $mform->updateAttributes(array('class' => $oldclass.' mform coupon_register_form'));
        } else {
            $mform->updateAttributes(array('class' => 'mform'));
        }

        $mform->addElement('html', '<div class="d-flex row coupon_form_wrapper inline_form">');
        $mform->addElement('text', 'couponid', '', ['placeholder' => core::str('coupon:stripeid')]);
        $mform->addElement('submit', 'submitbutton', core::str('coupon:register'), [], false);
        $mform->setType('couponid', PARAM_TEXT);
        $mform->addElement('html', '</div>');
    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);
        if (empty($data['couponid'])){
            $errors['coupon_group'] = 'Please enter the coupon!';
        }

        return $errors;
    }

    function setElementError($element, $message){
        $this->_form->setElementError($element, $message);
    }
}