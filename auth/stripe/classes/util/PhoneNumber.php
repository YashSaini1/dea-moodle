<?php

namespace auth_stripe\util;

use auth_stripe\core;

/**
 * Class for tel-input PhoneNumber field
 */
class PhoneNumber {

    /** @var PhoneNumberFormDTO[] for multiple form usage */
    protected static $_forms = [];

    /**
     * Validate phone number
     *
     * @param string $phone_number
     *
     * @return false|\lang_string|string
     */
    public static function validate($phone_number){
        $phone = static::parse($phone_number);

        $count_numbers = strlen($phone);
        if ($count_numbers < 9 || !is_numeric($phone)){
            return core::str('validation_error:phone_number_invalid');
        }
        return false;
    }

    public static function parse($phone) : string{
        return preg_replace('/\D/', '', $phone);
    }

    /**
     * Add hidden field to the form (to get its value) and register form
     *
     * @param \MoodleQuickForm $mform
     * @param string $fieldid
     * @param string $hiddenname
     * @param string $value
     */
    public static function init_for_form($mform, $fieldid, $hiddenname = null, $value = null){
        if ($hiddenname){
            $mform->addElement('hidden', $hiddenname);
            $mform->setType($hiddenname, PARAM_TEXT);
        }
        static::register_form($mform, $fieldid, $hiddenname, $value);

        // Call init lib while form is building because we cannot connect css files if $OUTPUT->header() already called
        static::init_lib();
    }

    /**
     * Register form and save its data for tel-input lib
     *
     * @param \MoodleQuickForm $mform
     * @param string $fieldid
     * @param string $hiddenname
     * @param string $value
     */
    public static function register_form($mform, $fieldid, $hiddenname = null, $value = null){
        static::$_forms[$mform->_formName] = new PhoneNumberFormDTO($fieldid, $hiddenname, $value);
    }

    /**
     * Load tel-input lib for current form
     *
     * Also, allow to override first value here
     *
     * @param \MoodleQuickForm $mform
     * @param string $value
     */
    public static function render_for_form($mform, $value = null){
        $form_dto = static::$_forms[$mform->_formName] ?? null;
        if (empty($form_dto)){
            return;
        }

        if (!empty($value)){
            $form_dto->set_value($value);
        }
        static::init_field($form_dto->get_fieldid(),$form_dto->get_hidden(), $form_dto->get_value());
    }

    /**
     * Initialize phone field. Set up its value and hidden field
     *
     * @param string $field_id
     * @param string $hiddenname
     * @param string $value
     */
    public static function init_field($field_id, $hiddenname = null, $value = null){
        global $PAGE, $CFG;

        // Always load lib here for more scalability
        static::init_lib();
        $hiddenjs = $valuejs = '';
        if ($hiddenname){
            $hiddenjs =
                'input.form.addEventListener("submit", (e) => {
        if (iti.hiddenInput.value[0] === "+"){
            return;
        }
        iti.hiddenInput.value = "+" + iti.getSelectedCountryData().dialCode + iti.hiddenInput.value;
    });';
        }

        if ($value){
            if ($value[0] !== '+'){
                $value = '+'.$value;
            }
            $valuejs = 'iti.setNumber("'.$value.'");
            input.value = input.value.substring(iti.getSelectedCountryData().dialCode.length + 1)';
        }

        $PAGE->requires->js_init_code('const input = document.getElementById("'.$field_id.'");
    input.setAttribute("type", "tel");
    input.addEventListener("input", () => {
        input.value = input.value.replace(/\D/g, "");
    });
    input.setAttribute("type", "tel");
    let iti = window.intlTelInput(input, {'.
            ($hiddenname ? 'hiddenInput:"'.$hiddenname.'",' : '').
            'utilsScript: "'.$CFG->wwwroot.'/auth/stripe/tel-input/utils.min.js",
    });'.$hiddenjs. $valuejs
        );
//        $PAGE->requires->js_init_code("let phone_field = document.getElementById('".$field_id."')
//        phone_field.addEventListener('input', () => {
//            phone_field.value = phone_field.value.replace(/\D/g, '');
//        });");
    }

    /**
     * Load tel-input lib
     * Make sure, that this method must call before $OUTPUT->header function call
     */
    public static function init_lib(){
        static $scripts_connected = false;

        if (!$scripts_connected){
            global $PAGE;

            $PAGE->requires->css('/auth/stripe/tel-input/intlTelInput.min.css');
            $PAGE->requires->js('/auth/stripe/tel-input/intlTelInput.min.js');
            $scripts_connected = true;
        }
    }
}