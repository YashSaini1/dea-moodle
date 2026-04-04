<?php

namespace auth_stripe\form;

use auth_stripe\model\product;

require_once ($CFG->libdir . '/formslib.php');

class admin_stripe extends \moodleform {

    public static function get_page_index($page){
        foreach (product::PAGES as $position => $p){
            if ($p == $page){
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

        $products = product::get_all_sorted_by_tier();
        foreach ($this->_customdata['tiers'] as $tier){
            $mform->addElement('text', 'tier_'.$tier.'_name', 'Tier '.$tier.' name');
            $mform->setType('tier_'.$tier.'_name', PARAM_TEXT);

            $mform->addElement('select', 'tier_'.$tier.'_page', 'Payment page '.$tier, product::PAGES);
            $mform->setType('tier_'.$tier.'_page', PARAM_TEXT);

            if (!empty($products[$tier])){
                $edit_url = new \moodle_url(PRICE_URL, [
                    'id' => $products[$tier]->id,
                ]);
                $mform->addElement('link', 'edit_prices'.$tier, '', $edit_url->out(false), 'Edit prices');
            }
            $mform->addElement('html', '<hr>');
        }

        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}