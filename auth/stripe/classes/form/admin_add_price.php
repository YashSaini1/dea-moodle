<?php

namespace auth_stripe\form;

use auth_stripe\core;
use auth_stripe\model\price;

require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->dirroot . '/auth/stripe/lib.php');

class admin_add_price extends \moodleform {

    protected const ADD_MORE_CHOICES_BUTTON = 'addmorechoices';

    protected $_repeats = admin_price::DEFAULT_REPEATED;

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;

        $mform->addElement('hidden', 'productid', $this->_customdata['productid']);
        $mform->setType('productid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['productid']);
        $mform->setType('id', PARAM_INT);

        if (!empty($this->_customdata['price'])){
            $count = count($this->_customdata['price']);
            $this->_repeats = $count > 0 ? $count : admin_price::DEFAULT_REPEATED;
        }
        $this->_add_multiple_fields_fields($mform);
        if (!empty($this->_customdata['price'])){
            $mform->removeElement(static::ADD_MORE_CHOICES_BUTTON);
        }

        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    protected function _add_multiple_fields_fields(&$mform){
        [$elements, $options] = $this->_get_repeated_elements($mform);
        $this->repeat_elements($elements, $this->_repeats, $options, admin_price::REPEAT_NUMBER_FIELD,
            static::ADD_MORE_CHOICES_BUTTON, false, $this->_get_more_choices_string(), false);
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function _get_more_choices_string(){
        return get_string('addmorechoiceblanks', 'question');
    }

    /**
     * @param \MoodleQuickForm $mform
     *
     * @return array
     */
    protected function _get_repeated_elements($mform): array{
        $elements = [
            $mform->createElement('static', 'label', '<h3>Price {no}</h3>'),
            $mform->createElement('select', 'period', core::str('price:period'), price::PERIODS),
            $mform->createElement('text', 'price', core::str('price:price'), ['size' => 40]),
            $mform->createElement('text', 'max_times', core::str('price:max_times'), ['size' => 40]),
            $mform->createElement('text', 'plan_name', core::str('price:plan_name'), ['size' => 40]),
            $mform->createElement('selectyesno', 'dependency', core::str('price:dependency')),
            $mform->createElement('selectyesno', 'is_coupon_allowed', core::str('price:coupon_allow')),
            $mform->createElement('selectyesno', 'is_checkout', core::str('price:is_checkout')),
            $mform->createElement('hidden', 'base_price', 0),
            $mform->createElement('html', '<hr>'),
        ];

        $is_creating = (!isset($this->_customdata['id']) || $this->_customdata['id'] == -1);

        $defaultValues = [
            'is_coupon_allowed' => PARAM_BOOL,
            'is_checkout' => PARAM_BOOL
        ];

        foreach ($defaultValues as $key => $type) {
            ${$key} = ['type' => $type];
            if ($is_creating) {
                ${$key}['default'] = 1;
            }
        }

        $options = [
            'period'     => [
                'type' => PARAM_TEXT,
            ],
            'price'      => [
                'type' => PARAM_FLOAT,
            ],
            'base_price' => [
                'type' => PARAM_INT,
            ],
            'plan_name'  => [
                'type'       => PARAM_RAW,
                'disabledif' => [
                    'dependency',
                    'eq',
                    1,
                ],
            ],
            'max_times'  => [
                'type'       => PARAM_TEXT,
                'disabledif' => [
                    'period',
                    'eq',
                    admin_price::get_period_index(core::PERIOD_ONE_TIME),
                ],
            ],
            'is_checkout' => $is_checkout,
            'is_coupon_allowed' => $is_coupon_allowed,
        ];
        return [$elements, $options];
    }
}
