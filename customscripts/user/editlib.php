<?php

require_once($CFG->dirroot.'/user/editlib.php');
use local_sql\moodle\role_manager;

/**
 * Powerful function that is used by edit and editadvanced to add common form elements/rules/etc.
 *
 * @param MoodleQuickForm $mform
 * @param array $editoroptions
 * @param array $filemanageroptions
 * @param stdClass $user
 */
function custom_useredit_shared_definition(&$mform, $editoroptions, $filemanageroptions, $user) {
    global $CFG, $USER, $DB;

    if ($user->id > 0) {
        useredit_load_preferences($user, false);
    }

    $strrequired = get_string('required');
    $stringman = get_string_manager();

    $mform->addElement('html', '<div class="profile-fields-group">');
    $mform->addElement('html', '<div class="edit-profile-name-fields">');

    // Add the necessary names.
    foreach (useredit_get_required_name_fields() as $fullname) {
        $purpose = user_edit_map_field_purpose($user->id, $fullname);

        $component = '';
        if ($stringman->string_exists($fullname, 'theme_sql')){
            $component = 'theme_sql';
        }
        $mform->addElement('text', $fullname,  get_string($fullname, $component),  'maxlength="40" size="30"' . $purpose);
        if ($stringman->string_exists('missing'.$fullname, 'theme_sql')){
            $strmissingfield = get_string('missing'.$fullname, 'theme_sql');
        } elseif ($stringman->string_exists('missing'.$fullname, 'core')) {
            $strmissingfield = get_string('missing'.$fullname, 'core');
        } else {
            $strmissingfield = $strrequired;
        }
        $mform->addRule($fullname, $strmissingfield, 'required', null, 'client');
        $mform->setType($fullname, PARAM_NOTAGS);
    }

    $enabledusernamefields = useredit_get_enabled_name_fields();
    // Add the enabled additional name fields.
    foreach ($enabledusernamefields as $addname) {
        $purpose = user_edit_map_field_purpose($user->id, $addname);
        $mform->addElement('text', $addname,  get_string($addname), 'maxlength="100" size="30"' . $purpose);
        $mform->setType($addname, PARAM_NOTAGS);
    }
    $mform->addElement('html', '</div>');

    $field = 'phone';
    $purpose = user_edit_map_field_purpose($user->id, 'phone1');
    $component = '';
    if ($stringman->string_exists($field, 'theme_sql')){
        $component = 'theme_sql';
    }
    $mform->addElement('text', $field,  get_string($field, $component),  'id="'.$field.'" maxlength="20" size="30"' . $purpose);
    if ($stringman->string_exists('missing'.$field, 'theme_sql')){
        $strmissingfield = get_string('missing'.$field, 'theme_sql');
    } elseif ($stringman->string_exists('missing'.$field, 'core')) {
        $strmissingfield = get_string('missing'.$field, 'core');
    } else {
        $strmissingfield = $strrequired;
    }
    $mform->addRule($field, $strmissingfield, 'required', null, 'client');
    $mform->setType($field, \core_user::get_property_type('phone1'));
    $mform->addHelpButton($field, 'phone_field', 'auth_stripe');

    // Data field. Phone1 will contains phone data with code
    \auth_stripe\util\PhoneNumber::init_for_form($mform, 'phone', 'phone1', $user->phone1);

    // Do not show email field if change confirmation is pending.
    if ($user->id > 0 and !empty($CFG->emailchangeconfirmation) and !empty($user->preference_newemail)) {
        $notice = get_string('emailchangepending', 'auth', $user);
        $notice .= '<br /><a href="edit.php?cancelemailchange=1&amp;id='.$user->id.'">'
                . get_string('emailchangecancel', 'auth') . '</a>';
        $mform->addElement('static', 'emailpending', get_string('email'), $notice);
    } else {
        $purpose = user_edit_map_field_purpose($user->id, 'email');
        $mform->addElement('text', 'email', get_string('email_field', 'theme_sql'), 'maxlength="100" size="30"' . $purpose);
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_RAW_TRIMMED);
    }

    $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="30"' . $purpose);
    $mform->addRule('username', $strrequired, 'required', null, 'client');
    $mform->setType('username', PARAM_RAW_TRIMMED);
    $mform->addHelpButton('username', 'username', 'theme_sql');

    if (role_manager::is_local_admin()) {
        $purpose = user_edit_map_field_purpose($user->id, 'password');
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"' . $purpose);
        $mform->setType('newpassword', core_user::get_property_type('password'));
        if ($user->auth == 'oauth2') {
            $mform->addElement('html', '<p>The user is created via SSO. Moodle password is not used.</p>');
        } else {
            $mform->addElement('html', '<p>' . get_string('user') . ' ' . get_string('authentication') . ': ' . $user->auth . '</p>');
        }

    }

    $mform->addElement('html', '</div>');

    $mform->addElement('static', 'moodle_picture', get_string('pictureofuser'));

    if (!empty($CFG->enablegravatar)) {
        $mform->addElement('html', html_writer::tag('p', get_string('gravatarenabled')));
    }

    $mform->addElement('html', '<div class="edit-profile-picture-wrapper">');

    $mform->addElement('html', '<div class="edit-profile-picture">');

    $mform->addElement('static', 'currentpicture','');

    $mform->addElement('checkbox', 'deletepicture', get_string('deletepicture'));
    $mform->setDefault('deletepicture', 0);

    $mform->addElement('html', '</div>');

    $mform->addElement('filemanager', 'imagefile', '', '', $filemanageroptions);

    $mform->addElement('html', '</div>');
}