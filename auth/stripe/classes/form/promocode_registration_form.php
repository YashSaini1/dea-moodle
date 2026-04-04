<?php

namespace auth_stripe\form;

use auth_stripe\core;

require_once ($CFG->libdir . '/formslib.php');

class promocode_registration_form extends \moodleform {

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;
        $oldclass = $mform->getAttribute('class');
        if (!empty($oldclass)){
            $mform->updateAttributes(array('class' => $oldclass.' mform promocode_register_form'));
        } else {
            $mform->updateAttributes(array('class' => 'mform'));
        }

        $mform->addElement('html', '<div class="d-flex row promocode_form_wrapper inline_form">');
        $mform->addElement('text', 'promocodeid', '');
        $mform->addElement('submit', 'submitbutton', 'Register promocode', [], false);
        $mform->setType('promocodeid', PARAM_TEXT);
        $mform->addElement('html', '</div>');
    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);
        if (empty($data['promocodeid'])){
            $errors['promocodeid'] = 'Please enter promocode!';
        }

        return $errors;
    }

    function setElementError($element, $message){
        $this->_form->setElementError($element, $message);
    }
}