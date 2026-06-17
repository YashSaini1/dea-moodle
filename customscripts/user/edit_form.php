<?php

/**
 * Form to edit a users profile
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

use auth_stripe\util\PhoneNumber;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Class user_edit_form.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_edit_form extends moodleform
{

    /**
     * Define the form.
     */
    public function definition()
    {
        global $CFG, $COURSE, $USER, $SESSION;

        $mform = $this->_form;
        $usernotfullysetup = user_not_fully_set_up($USER);

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for user_edit_form');
        }
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $user = $this->_customdata['user'];
        $userid = $user->id;

        if (empty($user->country)) {
            // We must unset the value here so $CFG->country can be used as default one.
            unset($user->country);
        }

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        // Print the required moodle fields first.
        $mform->addElement('static', 'moodle', $strgeneral);

        // Shared fields.
        custom_useredit_shared_definition($mform, $editoroptions, $filemanageroptions, $user);

        // Extra settigs.
        if (!empty($CFG->disableuserimages) || $usernotfullysetup) {
            if (isset($mform->_elements['deletepicture'])) {
                $mform->removeElement('deletepicture');
            }
            if (isset($mform->_elements['imagefile'])) {
                $mform->removeElement('imagefile');
            }
            if (isset($mform->_elements['imagealt'])) {
                $mform->removeElement('imagealt');
            }
        }

        // If the user isn't fully set up, let them know that they will be able to change
        // their profile picture once their profile is complete.
        if ($usernotfullysetup) {
            $userpicturewarning = $mform->createElement('warning', 'userpicturewarning', 'notifymessage', get_string('newpictureusernotsetup'));
            $enabledusernamefields = useredit_get_enabled_name_fields();
            if ($mform->elementExists('moodle_additional_names')) {
                $mform->insertElementBefore($userpicturewarning, 'moodle_additional_names');
            } else if ($mform->elementExists('moodle_interests')) {
                $mform->insertElementBefore($userpicturewarning, 'moodle_interests');
            } else if ($mform->elementExists('moodle_optional')) {
                $mform->insertElementBefore($userpicturewarning, 'moodle_optional');
            }

            // This is expected to exist when the form is submitted.
            $imagefile = $mform->createElement('hidden', 'imagefile');
            if (isset($mform->_elements['userpicturewarning'])) {
                $mform->insertElementBefore($imagefile, 'userpicturewarning');
            }
        }

        // Render custom profile fields (core behavior) so user-defined fields appear in edit profile.
        profile_definition($mform, $userid);

        $this->add_action_buttons(true, get_string('updatemyprofile'));

        $this->set_data($user);
        if (empty($SESSION->opened) && !empty($SESSION->profile_redirect_field)) {
            if ($SESSION->profile_redirect_field == 'phone1') {
                $mform->setElementError('phone', \auth_stripe\core::str('error_required'));
            } else {
                $mform->setElementError($SESSION->profile_redirect_field, \auth_stripe\core::str('error_required'));
            }
            $SESSION->opened = true;
        }
    }

    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data()
    {
        global $CFG, $DB, $OUTPUT;

        $mform = $this->_form;
        $userid = $mform->getElementValue('id');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        if ($user = $DB->get_record('user', array('id' => $userid))) {
            // Print picture.
            $context = context_user::instance($user->id, MUST_EXIST);
            $fs = get_file_storage();
            $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));

            // always show user picture
            $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64));
            $imageelement = $mform->getElement('currentpicture');
            $imageelement->setValue($imagevalue);

            if ($mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->freeze('deletepicture');
            }

            // Finalise custom profile field state (visibility, defaults, dynamic rules).
            profile_definition_after_data($mform, $user->id);
        } else {
            profile_definition_after_data($mform, 0);
        }
    }

    /**
     * Validate incoming form data.
     * @param array $usernew
     * @param array $files
     * @return array
     */
    public function validation($usernew, $files)
    {
        global $CFG, $DB;

        $errors = parent::validation($usernew, $files);

        $usernew = (object)$usernew;
        $user    = $DB->get_record('user', array('id' => $usernew->id));

        // Validate email.
        if (!isset($usernew->email)) {
            // Mail not confirmed yet.
        } else if (!validate_email($usernew->email)) {
            $errors['email'] = get_string('invalidemail');
        } else if (($usernew->email !== $user->email) && empty($CFG->allowaccountssameemail)) {
            // Make a case-insensitive query for the given email address.
            $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
            $params = array(
                'email' => $usernew->email,
                'mnethostid' => $CFG->mnet_localhost_id,
                'userid' => $usernew->id
            );
            // If there are other user(s) that already have the same email, show an error.
            if ($DB->record_exists_select('user', $select, $params)) {
                $errors['email'] = get_string('emailexists');
            }
        }

        if (isset($usernew->email) and $usernew->email === $user->email and over_bounce_threshold($user)) {
            $errors['email'] = get_string('toomanybounces');
        }

        if (isset($usernew->email) and !empty($CFG->verifychangedemail) and !isset($errors['email']) and !has_capability('moodle/user:update', context_system::instance())) {
            $errorstr = email_is_not_allowed($usernew->email);
            if ($errorstr !== false) {
                $errors['email'] = $errorstr;
            }
        }

        // Validate hidden 'phone1' field. but show error to the input 'phone' field
        $usernew->phone1 = PhoneNumber::parse($usernew->phone1);
        if ($validation_error = PhoneNumber::validate($usernew->phone1)) {
            $errors['phone'] = $validation_error;
        }

        // Validate username
        if (($usernew->username !== $user->username)) {
            // The fastest solution to find symbols
            // Do not save email as username
            if ((str_replace(['.', '@'], '', $usernew->username) != $usernew->username)) {
                $errors['username'] = get_string('invalidusernameupload');
            } else {
                // Make a case-insensitive query for the given email address.
                $select = $DB->sql_equal('username', ':username', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = array(
                    'username'   => $usernew->username,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid'     => $usernew->id
                );
                // If there are other user(s) that already have the same email, show an error.
                if ($DB->record_exists_select('user', $select, $params)) {
                    $errors['username'] = get_string('usernameexists', 'theme_sql');
                }
            }
        }

        // Next the customisable profile fields.
        $errors += profile_validation($usernew, $files);

        return $errors;
    }

    /**
     * Render phone field here because we need to save submitted value (if validation failed)
     */
    function display()
    {
        $phone_value = $this->_form->getElementValue('phone1');
        PhoneNumber::render_for_form($this->_form, $phone_value);
        parent::display();
    }
}
