<?php

/**
 * Theme sql backgrounds callbacks.
 *
 * @package     theme_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
use local_sql\moodle\role_manager;

defined('MOODLE_INTERNAL') || die();

const THEME_NAME = 'sql';

const PRISMJS_PATH = '/theme/'.THEME_NAME.'/js/prism/';
const PRISMJS_JS_FILE = 'prism.min.js';

const LINE_DELIMITER = '###';

require_once ($CFG->dirroot . '/theme/'.THEME_NAME.'/locallib.php');
require_once ($CFG->dirroot . '/theme/'.THEME_NAME.'/quiz_lib.php');
require_once ($CFG->dirroot . '/theme/'.THEME_NAME.'/hvp_lib.php');
require_once ($CFG->dirroot . '/theme/'.THEME_NAME.'/url_lib.php');

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_sql_get_main_scss_content($theme) {
  global $CFG;

  $scss = theme_boost_get_main_scss_content($theme);

  // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
  $pre = file_get_contents($CFG->dirroot . '/theme/'.THEME_NAME.'/scss/pre.scss');
  // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
  $post = file_get_contents($CFG->dirroot . '/theme/'.THEME_NAME.'/scss/post.scss');

  // Combine them together.
  return $pre . "\n" . $scss . "\n" . $post;
}

/**
 * Load all scss files from the 'scss' dirs in the OUR plugins
 * Warning: do NOT load theme files here!
 *
 * Inject additional SCSS.
 * Return the SCSS to append to our main SCSS for this theme
 * Note: really works only through the additional themes calls
 *
 * @see theme_config::get_css_content_from_scss() - theme function is called from here
 *
 * Note: result of this function is saved in theme cache,
 *  so after changes of function work you need purging the caches or enabling theme designer mode
 *
 * @param theme_config $theme The theme config object.
 *
 * @return string The custom post SCSS.
 */
function theme_sql_get_extra_scss($theme){
    $f_extra_content = function($content, $name){
        $prefix = "\n/** Extra SCSS from $name **/\n";
        $postfix =  "\n/** End extra SCSS from $name **/\n";
        return $prefix.join("\n", $content).$postfix;
    };

    $plugins = [
        'auth_stripe' => '/auth/stripe',
        'block_sql_comments' => '/blocks/sql_comments',
        'local_sql' => '/local/sql',
        'qtype_sqlrunner' => '/question/type/sqlrunner',
        'qtype_pythonrunner' => '/question/type/pythonrunner',
        'qtype_data_modeling' => '/question/type/data_modeling',
        'block_sql_myoverview' => '/blocks/sql_myoverview',
    ];

    $res_content = [];
    $theme_name = 'theme_'.$theme->name;
    foreach ($plugins as $plugin => $plugin_dir){
        if ($theme_name != $plugin && theme_sql_str_starts_with($plugin_dir, '/theme/', false)) continue;

        $dir = theme_sql_path($plugin_dir.'/scss/');
        if (!is_dir($dir)) continue;

        $content = theme_sql_load_scss_from_dir($dir);
        if (!empty($content)){
            $res_content[] = $f_extra_content($content, $plugin);
        }
    }

    if (!empty($res_content)){
        return $f_extra_content($res_content, __FUNCTION__);
    }

    return '';
}

/**
 * Load scss files from dir and child dirs
 *
 * @param string $dir - /path/to/dir/with/scss
 * @param array  $content - result array of contents
 *
 * @return array - copy of $content
 */
function theme_sql_load_scss_from_dir($dir, &$content=[]){
    if (!is_dir($dir)) return $content;

    if ($dh = opendir($dir)){
        while (($filename = readdir($dh)) !== false){
            if (theme_sql_str_starts_with($filename, ['.', '_'])) continue;

            $filepath = $dir.$filename;
            if (is_dir($filepath)){
                theme_sql_load_scss_from_dir($filepath.DIRECTORY_SEPARATOR, $content);
            } else {
                if (!theme_sql_str_ends_with($filename, '.scss')) continue;

                try {
                    $content[] = file_get_contents($dir.$filename);
                } catch (\Exception $ex){
                    continue;
                }
            }
        }
        closedir($dh);
    }

    return $content;
}

/**
 * Copy the updated theme image to the correct location in dataroot for the image to be served
 * by /theme/image.php. Also clear theme caches.
 *
 * @param $settingname
 */
function sql_update_settings_images($settingname) {
  global $CFG;

  // The setting name that was updated comes as a string like 's_sql_loginbackgroundimage'.
  // We split it on '_' characters.
  $parts = explode('_', $settingname);
  // And get the last one to get the setting name..
  $settingname = end($parts);

  // Admin settings are stored in system context.
  $syscontext = context_system::instance();
  // This is the component name the setting is stored in.
  $component = 'theme_photo';


  // This is the value of the admin setting which is the filename of the uploaded file.
  $filename = get_config($component, $settingname);
  // We extract the file extension because we want to preserve it.
  $extension = substr($filename, strrpos($filename, '.') + 1);

  // This is the path in the moodle internal file system.
  $fullpath = "/{$syscontext->id}/{$component}/{$settingname}/0{$filename}";

  // This location matches the searched for location in theme_config::resolve_image_location.
  $pathname = $CFG->dataroot . '/pix_plugins/'.THEME_NAME.'/photo/' . $settingname . '.' . $extension;

  // This pattern matches any previous files with maybe different file extensions.
  $pathpattern = $CFG->dataroot . '/pix_plugins/'.THEME_NAME.'/photo/' . $settingname . '.*';

  // Make sure this dir exists.
  @mkdir($CFG->dataroot . '/pix_plugins/'.THEME_NAME.'/photo/', $CFG->directorypermissions, true);

  // Delete any existing files for this setting.
  foreach (glob($pathpattern) as $filename) {
    @unlink($filename);
  }

  // Get an instance of the moodle file storage.
  $fs = get_file_storage();
  // This is an efficient way to get a file if we know the exact path.
  if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
    // We got the stored file - copy it to dataroot.
    $file->copy_content_to($pathname);
  }

  // Reset theme caches.
  theme_reset_all_caches();
}

/**
 * Preprocess secondary nav from $PAGE
 */
function theme_sql_process_secondary_menu(){
    if (is_siteadmin()) return;

    global $PAGE;

    if ($PAGE->cm){
        if (\local_sql\moodle\role_manager::is_student()){
            $available_quiz_nav = [];
        } else {
            $available_quiz_nav = ['modulepage', 'modedit', 'mod_quiz_edit', 'quiz_report'];
        }

        $activity_nav = $PAGE->secondarynav;
        if ($activity_nav){
            foreach ($activity_nav->children as $item){
                if (!in_array($item->key, $available_quiz_nav)){
                    $item->remove();
                }
            }
        }
    } elseif ($PAGE->course) {
        if (\local_sql\moodle\role_manager::is_student()){
            $available_course_nav = [];
        } else {
            $available_course_nav = [
                'coursehome',
                'editsettings',
                'questionbank',
                'coursereports',
                'participants'
            ];
        }

        $activity_nav = $PAGE->secondarynav;
        if ($activity_nav){
            foreach ($activity_nav->children as $item){
                if (!in_array($item->key, $available_course_nav)){
                    $item->remove();
                } else {
                    $item->forceintomoremenu = false;
                }
            }
        }
    }
}

/**
 * Process alter primary nav from builded menu obj.
 * Process menu after build, because it's impossible to influence to build this menu.
 */
function theme_sql_process_primary_menu(&$primary_menu){
    if(!empty($primary_menu['moremenu']['nodearray'])){
        $moremenu = [];
        $mobilemenu = [];
        $ctx = context_system::instance();
        $can_manage_dataset = has_capability('qtype/sqlrunner:manage_datasets', $ctx);
        $can_delete_comments = has_capability('moodle/comment:delete', $ctx);
        $isadmin = role_manager::is_local_admin();
        $can_view_prices = \auth_stripe\core::can_view_created_prices();
        $can_view_coupons = \auth_stripe\core::can_view_coupons();

        foreach ($primary_menu['moremenu']['nodearray'] as $key => $item){
            if (is_array($item) && ($item['key'] == 'home' || $item['key'] == 'myhome')){
                continue;
            }

            if (is_object($item)){
                if ($item->text == 'Admin Panel' && !$isadmin){
                    unset($item);
                    continue;
                }
                if ($item->text == 'Community' && !($isadmin || role_manager::is_local_coaching())){
                    unset($item);
                    continue;
                }
                if (!empty($item->children)){
                    $children = [];
                    foreach ($item->children as $child_key => $child){
                        if ($child->text == 'Datasets' && !$can_manage_dataset){
                            unset($item->children[$child_key]);
                            continue;
                        }
                        if ($child->text == 'Comments' && !$can_delete_comments){
                            unset($item->children[$child_key]);
                            continue;
                        }
                        if ($child->text == LINE_DELIMITER && empty($children)){
                            unset($item->children[$child_key]);
                            continue;
                        }

                        if (in_array($child->text, ['Prices', 'Special Prices']) && !$can_view_prices){
                            unset($item->children[$child_key]);
                            continue;
                        }

                        if (in_array($child->text, ['Coupons', 'Promobanner']) && !$can_view_coupons){
                            unset($item->children[$child_key]);
                            continue;
                        }

                        $children[] = $child;
                    }
                    if (empty($children)){
                        continue;
                    }

                    $count = count($children) - 1;
                    while ($count > 0 && $children[$count]->text == LINE_DELIMITER){
                        unset($children[$count]);
                        $count--;
                    }
                    $item->children = $children;
                }
            }

            $moremenu[] = $item;
            $mobilemenu[] = $primary_menu['mobileprimarynav'][$key];
        }

        // mustache cannot parse arrays with different indexes
        $primary_menu['moremenu']['nodearray'] = $moremenu;
        $primary_menu['mobileprimarynav'] = $mobilemenu;
    }

    if (!empty($primary_menu['user']['items'])){
        $usermenu = [];
        $allowed_user_menu = ['profile,moodle', 'logout,moodle'];
        foreach ($primary_menu['user']['items'] as $key => $item){
            if (!empty($item->titleidentifier) && !in_array($item->titleidentifier, $allowed_user_menu)){
                continue;
            }

            $usermenu[] = $item;
        }

        $primary_menu['user']['items'] = $usermenu;
    }
    return $primary_menu;
}

/**
 * Return days on platform string
 *
 * @param stdClass $user
 *
 * @return string
 */
function theme_sql_render_days_on_platform($user = null){
    global $USER;
    if (empty($user)){
        $user = $USER;
    }

    static $data = [];
    if (!isset($data[$user->id])){
        $timestamp_on_platform = get_user_time_on_platform($user);
        $data[$user->id] = print_time_on_platform($timestamp_on_platform);
    }

    return $data[$user->id];
}

function render_carma_points($user, $currentuser = true){
    global $OUTPUT;
    $data = [
        'carma_points' => \block_sql_comments\karma::getKarmaUser($user),
    ];
    return $OUTPUT->render_from_template('theme_sql/carma_points', $data);
}

function theme_sql_is_customised_page(\moodle_page $page){
    if ($page->context->contextlevel == CONTEXT_MODULE && theme_sql_is_customised_cm($page->cm)){
        return true;
    }

    return in_array($page->pagelayout, ['questioneditor', 'modhvp', 'modurl']);
}

function theme_sql_is_customised_cm($cm){
    return !empty($cm) && in_array($cm->modname, ['quiz', 'hvp', 'resource', 'url', 'accredible']);
}

function theme_sql_page_init($page){
    $course = $page->course;
    if ($course->id == SITEID){
        return;
    }
    $is_coaching = \local_sql\moodle\course_customfield::get_is_coaching_course($course->id);

    $body_class = $is_coaching ? 'coaching_course' : 'not_coaching_course';
    $page->add_body_class($body_class);
}

function theme_sql_get_upgrade_url(){
    if (!isloggedin() || \local_sql\moodle\role_manager::is_admin()){
        return false;
    }

    $tier_output = new \auth_stripe\output\stripe\user_tier_output();
    return $tier_output->get_upgrade_url();
}

function theme_sql_is_custom_question_type($qtype){
    return in_array($qtype, [
        'sqlrunner',
        'pythonrunner',
        'data_modeling',
    ]);
}

function theme_sql_update_prismjs_js(){
    global $CFG;
    $prismjscode = get_config('theme_sql', 'prismjs_js');
    file_put_contents($CFG->dirroot.PRISMJS_PATH.PRISMJS_JS_FILE, $prismjscode);
    purge_caches(['js' => 1]);
}

function theme_sql_get_prismjs_file_url(){
    global $PAGE, $CFG;

    $js_file = PRISMJS_PATH.PRISMJS_JS_FILE;
    $jsrev = $PAGE->requires->get_jsrev();
    if (empty($CFG->slasharguments)){
        return new moodle_url('/lib/javascript.php', array('rev' => $jsrev, 'jsfile' => $js_file));
    }

    $returnurl = new moodle_url('/lib/javascript.php');
    $returnurl->set_slashargument('/'.$jsrev.$js_file);
    return $returnurl;
}