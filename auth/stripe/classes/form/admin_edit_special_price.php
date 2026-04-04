<?php

namespace auth_stripe\form;

use auth_stripe\core;
use auth_stripe\model\price;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/auth/stripe/lib.php');

class admin_edit_special_price extends admin_add_price {

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;

        $mform->addElement('hidden', 'productid', $this->_customdata['productid']);
        $mform->setType('productid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        if (!empty($this->_customdata['price'])){
            $count = count($this->_customdata['price']);
            $this->_repeats = $count > 0 ? $count : admin_price::DEFAULT_REPEATED;
        }
        $this->_add_multiple_fields_fields($mform);
        if (!empty($this->_customdata['price'])){
            $mform->removeElement(static::ADD_MORE_CHOICES_BUTTON);
        }

        $mform->addElement('editor', 'description', get_string('description'));
        $mform->setType('description', PARAM_RAW_TRIMMED);
        $mform->addRule('description', core::str('error_required'), 'required', null, 'client');

        $mform->addElement('editor', 'email_text', get_string('email'));
        $mform->setType('email_text', PARAM_RAW);
        $mform->addRule('email_text', core::str('error_required'), 'required', null, 'client');

        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);

        if (empty($data['description'])){
            $errors['description'] = core::str('error_required');
        }

        if ($data['id'] != -1){
            // Do not validate price details during update
            return $errors;
        }

        $valid_periods = false;
        foreach ($data['period'] as $key => $period){
            if (price::PERIODS[$period] != core::PERIOD_ONE_TIME || $data['price'][$key] == 0){
                $valid_periods = true;
                break;
            }
        }
        if (!$valid_periods){
            foreach ($data['period'] as $key => $value){
                $errors['period['.$key.']'] = 'You cannot set two one time periods';
            }
        }
        return $errors;
    }

    /**
     * @param \MoodleQuickForm $mform
     *
     * @return array
     */
    protected function _get_repeated_elements($mform): array{
        [$elements, $options] = parent::_get_repeated_elements($mform);
        $disabledifconditions = [
            'id',
            'neq',
            -1,
        ];
        $options['period']['disabledif'] = $disabledifconditions;
        $options['price']['disabledif'] = $disabledifconditions;
        $options['dependency']['disabledif'] = $disabledifconditions;

        return [$elements, $options];
    }
}