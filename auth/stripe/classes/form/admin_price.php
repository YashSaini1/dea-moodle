<?php

namespace auth_stripe\form;

use auth_stripe\core;
use auth_stripe\model\price;

require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->dirroot . '/auth/stripe/lib.php');

class admin_price extends \moodleform {

    const DEFAULT_REPEATED = DEFAULT_TIER_COUNT;

    const REPEAT_NUMBER_FIELD = 'repeated_prices';

    protected $_repeats = 0;

    public static function get_period_index($period){
        foreach (price::PERIODS as $position => $p){
            if ($p == $period){
                return $position;
            }
        }
        return -1;
    }

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;

        $mform->addElement('hidden', 'productid', $this->_customdata['productid']);
        $mform->setType('productid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata['productid']);
        $mform->setType('id', PARAM_INT);

        $this->_repeats = static::DEFAULT_REPEATED;
        if (!empty($this->_customdata['price'])){
            $count = count($this->_customdata['price']);
            $this->_repeats = $count > 0 ? $count : static::DEFAULT_REPEATED;
        }
        $this->_add_multiple_fields_fields($mform);

        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    protected function _add_multiple_fields_fields(&$mform){
        [$elements, $options] = $this->_get_repeated_elements($mform);
        $this->repeat_elements($elements, $this->_repeats, $options, static::REPEAT_NUMBER_FIELD,
            'addfield', static::DEFAULT_REPEATED, $this->_get_more_choices_string(), true);
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
            $mform->createElement('static', 'label', 'Price {no}'),
            $mform->createElement('select', 'period', core::str('price:period'), price::PERIODS),
            $mform->createElement('text', 'price', core::str('price:price'), ['size' => 40]),
            $mform->createElement('text', 'max_times', core::str('price:max_times'), ['size' => 40]),
            $mform->createElement('text', 'plan_name', core::str('price:plan_name'), ['size' => 40]),
            $mform->createElement('selectyesno', 'dependency', core::str('price:dependency')),
            $mform->createElement('text', 'ab_info', core::str('price:ab_info')),
            $mform->createElement('selectyesno', 'enabled', core::str('price:enabled')),
            $mform->createElement('html', '<hr>'),
        ];

        $options = [
            'plan_name' => ['type' => PARAM_RAW],
            'period'    => [
                'type' => PARAM_TEXT,
            ],
            'enabled' => [
                'type' => PARAM_INT,
//                'default' => 1
            ],
            'price'     => ['type' => PARAM_TEXT],
            'ab_info'   => ['type' => PARAM_TEXT],
            'max_times' => [
                'type'       => PARAM_TEXT,
                'disabledif' => [
                    'period',
                    'eq',
                    static::get_period_index(core::PERIOD_ONE_TIME),
                ],
            ],
        ];
        return [$elements, $options];
    }
}