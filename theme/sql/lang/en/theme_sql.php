<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language file.
 *
 * @package   theme_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// The name of our plugin.
$string['pluginname'] = 'Theme SQL';

// The name of the second tab in the theme settings.
$string['advancedsettings'] = 'Advanced settings';
// The backgrounds tab name.
$string['backgrounds'] = 'Backgrounds';
// The brand colour setting.
$string['brandcolor'] = 'Brand colour';
// The brand colour setting description.
$string['brandcolor_desc'] = 'The accent colour.';
// A description shown in the admin theme selector.
$string['choosereadme'] = 'Theme photo is a child theme of Boost. theme_sql.';
// Name of the settings pages.
$string['configtitle'] = 'Photo settings';

// Background image for default page.
$string['defaultbackgroundimage'] = 'Default page background image';
// Background image for default page.
$string['defaultbackgroundimage_desc'] = 'An image that will be stretched to fill the background of all pages without a more specific background image.';
// Background image for front page.
$string['frontpagebackgroundimage'] = 'Front page background image';
// Background image for front page.
$string['frontpagebackgroundimage_desc'] = 'An image that will be stretched to fill the background of the front page.';
// Name of the first settings tab.
$string['generalsettings'] = 'General settings';
// Background image for login page.
$string['loginbackgroundimage'] = 'Login page background image';
// Background image for login page.
$string['loginbackgroundimage_desc'] = 'An image that will be stretched to fill the background of the login page.';
// Preset files setting.
$string['presetfiles'] = 'Additional theme preset files';
// Preset files help text.
$string['presetfiles_desc'] = 'Preset files can be used to dramatically alter the appearance of the theme. See <a href=https://docs.moodle.org/dev/Boost_Presets>Boost presets</a> for information on creating and sharing your own preset files, and see the <a href=http://moodle.net/boost>Presets repository</a> for presets that others have shared.';
// Preset setting.
$string['preset'] = 'Theme preset';
// Preset help text.
$string['preset_desc'] = 'Pick a preset to broadly change the look of the theme.';
// Raw SCSS setting.
$string['rawscss'] = 'Raw SCSS';
// Raw SCSS setting help text.
$string['rawscss_desc'] = 'Use this field to provide SCSS or CSS code which will be injected at the end of the style sheet.';
// Raw initial SCSS setting.
$string['rawscsspre'] = 'Raw initial SCSS';
// Raw initial SCSS setting help text.
$string['rawscsspre_desc'] = 'In this field you can provide initialising SCSS code, it will be injected before everything else. Most of the time you will use this setting to define variables.';
// We need to include a lang string for each block region.

$string['setting:login_analytics_js'] = 'Login layout analytics';
$string['setting:login_analytics_js_description'] = 'This script will be appended to the end of each page with login layout';

$string['signin_error_enter_text'] = "The email or password you entered didn't match our records. Please double-check and try again, or you may have registered through Single Sign-On (SSO)";
$string['signin_error_email_text'] = "Please enter your email";
$string['signin_error_password_text'] = "Please enter your password";
$string['signin_forgotpassword_text'] = "Forgot Password?";
$string['signin_donthaveaccount_text'] = "Don't have an account?";
$string['login'] = 'Log in';
$string['signup'] = 'Sign up';
$string['remember_me'] = 'Remember me';

$string['forgot_title_text'] = 'Reset Password';
$string['forgot_instructions'] = 'Please enter your email address and we will send you a password reset link';
$string['forgot_submit_btn'] = 'Reset password';
$string['forgot_goto'] = 'Go to';
$string['forgot_login'] = 'Log in page';

$string['profile'] = 'My Profile';

$string['you_current_password_incorrect'] = 'Your current password incorrect';
$string['current_password'] = 'Current Password';
$string['new_password'] = 'New Password';
$string['confirm_new_password'] = 'Confirm New Password';

$string['time_on_platform'] = '{$a} on the platform';
$string['carma_points'] = 'karma points';
$string['usernameexists'] = 'This Username is already registered.';
$string['user_details'] = 'User details';
$string['edit_profile'] = 'Edit profile';

//$string['file_types_text'] = 'Accepted file types: .JPG, .JPE, .JPEG, .GIF or .PNG.';
$string['maxfilesize'] = 'Max file size: {$a}.';
//temporary solution with filesize directly in accepted types
$string['file_types_text'] = 'Accepted file types: .JPG, .JPE, .JPEG, .GIF or .PNG. Max file size: 10MB.';
$string['attach_picture'] = 'Attach Picture';
$string['drag_drop'] = 'Drag and Drop';

$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['missingfirstname'] = 'Missing First Name';
$string['missinglastname'] = 'Missing Last Name';
$string['email_field'] = 'Email';
$string['username'] = 'Username';
$string['newpassword'] = 'New Password';
$string['userauth'] = 'User Auth: ';
$string['phone'] = $string['phone1'] = 'Phone number';
$string['missingphone'] = $string['missingphone1'] = 'Missing Phone number';
$string['username_help'] = 'Username cannot contain "." or "@"';
$string['mycourses'] = 'My current courses';
$string['footer_privacy'] = '© '.date('Y').' Data Education Holdings Inc <span>® </span> . All rights reserved.';
$string['footer_development'] = 'Design & Development by SmartApp';
$string['submit_answer'] = 'Submit answer';

$string['question'] = 'Question';
$string['company'] = 'Company';
$string['status'] = 'Status';
$string['todo'] = 'To do';
$string['locked'] = 'Locked';
$string['done'] = 'Done';
$string['next_question'] = 'Go to next question';
$string['not_questions'] = 'No questions';

$string['editor_popup_title'] = 'Open from a desktop';
$string['editor_popup_text'] = 'This task isn\'t available from a mobile or tablet, only from a desktop.';
$string['editor_popup_button'] = 'Back to page';

$string['previous'] = 'Previous';
$string['next'] = 'Next';

$string['access_to_upgrade'] = 'Want to access all premium content?';
$string['access_to_upgrade_description'] = 'Subscribe and become a premium user to get unlimited access to the entire platform.';

$string['currentinparentheses'] = '(current)';
$string['cannot_delete_category'] = 'Category, that you want to delete, has a questions. But you haven\'t any other category in which you can move question. Please create a new category and you can move questions to the new category or delete used questions.';

$string['you_can_login_with'] = 'or you can login with:';
$string['questions_course'] = 'Course with modules';
$string['projects_course'] = 'Course with projects';

$string['show_solution'] = 'Show solution';
$string['action_item'] = 'Actions item';
$string['action_list'] = 'Actions list';

$string['output'] = 'Output';
$string['editor'] = 'Editor';

$string['output'] = 'Output';
$string['editor'] = 'Editor';

$string['config:prismjs_code'] = 'Prism JS code';
$string['config:prismjs_code_desc'] = 'This is the Prism JS javascript code, which will be connected to the moodle. See <a href="https://prismjs.com/download.html">Prism Js site</a>';
$string['page_sign_up_email'] = '';