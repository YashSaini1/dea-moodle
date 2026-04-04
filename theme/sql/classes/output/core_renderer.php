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

namespace theme_sql\output;

use context_course;
use html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot .'/theme/sql/lib.php');

class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form){
        global $CFG, $SITE, $PAGE;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        if ($CFG->rememberusername == 0){
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabledonlysession');
        } else {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        }
        $context->errorformatted = $this->error_text($context->error);
        if ($url = $this->get_logo_url()){
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);

        $context->signin_imgsrc = $this->image_url('auth/preview-sign-in-new', 'theme_sql');
        $context->signin_logo = $this->get_logo_url();

        $context->signin_error_imgsrc = '/theme/sql/pix/error.svg';

        $identity_providers = new \theme_sql\sql_identity_providers();
        $context->identityproviders = $identity_providers->render();

        $PAGE->requires->js_call_amd('theme_sql/login', 'init');
        return $this->render_from_template('theme_sql/auth/loginform', $context);
    }

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * {@see \auth_stripe\output\renderer::render_login_signup_form()}
     *
     * @param mform $form
     * @return string
     */
    public function render_signup_form($form) {
        global $SITE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }

        $context['preview_image'] = $this->image_url('auth/preview-sign-in-new', 'theme_sql');
        $context['signin_facebook'] = $this->pix_icon('auth/Facebook', 'Facebook', 'theme_sql');
        $context['signin_linkedin'] = $this->pix_icon('auth/Linkedin', 'Linkedin', 'theme_sql');
        $context['signin_google'] = $this->pix_icon('auth/Google', 'Google', 'theme_sql');

        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, false,
            ['context' => context_course::instance(SITEID), "escape" => false]);

        $context['loginurl'] = get_login_url();

        $identity_providers = new \theme_sql\sql_identity_providers();
        $context['identityproviders'] = $identity_providers->render();

        return $this->render_from_template('theme_sql/auth/signup', $context);
    }

    /**
     * Return the site's logo URL, if any.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return \moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        $logo = parent::get_logo_url($maxwidth, $maxheight);
        if($logo) return $logo;

        return $this->image_url('logo', 'theme_sql');
    }

    /**
     * Renders the context header for the page.
     *
     * @param array $headerinfo Heading information.
     * @param int $headinglevel What 'h' level to make the heading.
     * @return string A rendered context header.
     */
    public function context_header($headerinfo = null, $headinglevel = 1): string {
        global $DB, $USER, $CFG, $SITE;
        require_once($CFG->dirroot . '/user/lib.php');
        $context = $this->page->context;
        $heading = null;
        $imagedata = null;
        $subheader = null;
        $userbuttons = null;

        // Make sure to use the heading if it has been set.
        if (isset($headerinfo['heading'])) {
            $heading = $headerinfo['heading'];
        } else {
            $heading = $this->page->heading;
        }

        // The user context currently has images and buttons. Other contexts may follow.
        if ((isset($headerinfo['user']) || $context->contextlevel == CONTEXT_USER) && $this->page->pagetype !== 'my-index') {
            if (isset($headerinfo['user'])) {
                $user = $headerinfo['user'];
            } else {
                // Look up the user information if it is not supplied.
                $user = $DB->get_record('user', array('id' => $context->instanceid));
            }

            // If the user context is set, then use that for capability checks.
            if (isset($headerinfo['usercontext'])) {
                $context = $headerinfo['usercontext'];
            }

            // Only provide user information if the user is the current user, or a user which the current user can view.
            // When checking user_can_view_profile(), either:
            // If the page context is course, check the course context (from the page object) or;
            // If page context is NOT course, then check across all courses.
            $course = ($this->page->context->contextlevel == CONTEXT_COURSE) ? $this->page->course : null;

            if (user_can_view_profile($user, $course)) {
                // Use the user's full name if the heading isn't set.
                if (empty($heading)) {
                    $heading = fullname($user);
                }

                $imagedata = $this->user_picture($user, array('size' => 100));

                // Check to see if we should be displaying a message button.
                if (!empty($CFG->messaging) && has_capability('moodle/site:sendmessage', $context)) {
                    $userbuttons = array(
                        'messages' => array(
                            'buttontype' => 'message',
                            'title' => get_string('message', 'message'),
                            'url' => new \moodle_url('/message/index.php', array('id' => $user->id)),
                            'image' => 'message',
                            'linkattributes' => \core_message\helper::messageuser_link_params($user->id),
                            'page' => $this->page
                        )
                    );

                    if ($USER->id != $user->id) {
                        $iscontact = \core_message\api::is_contact($USER->id, $user->id);
                        $contacttitle = $iscontact ? 'removefromyourcontacts' : 'addtoyourcontacts';
                        $contacturlaction = $iscontact ? 'removecontact' : 'addcontact';
                        $contactimage = $iscontact ? 'removecontact' : 'addcontact';
                        $userbuttons['togglecontact'] = array(
                            'buttontype' => 'togglecontact',
                            'title' => get_string($contacttitle, 'message'),
                            'url' => new \moodle_url('/message/index.php', array(
                                    'user1' => $USER->id,
                                    'user2' => $user->id,
                                    $contacturlaction => $user->id,
                                    'sesskey' => sesskey())
                            ),
                            'image' => $contactimage,
                            'linkattributes' => \core_message\helper::togglecontact_link_params($user, $iscontact),
                            'page' => $this->page
                        );
                    }

                    $this->page->requires->string_for_js('changesmadereallygoaway', 'moodle');
                }
            } else {
                $heading = null;
            }
        }

        $prefix = null;
        if ($context->contextlevel == CONTEXT_MODULE) {
            if ($this->page->course->format === 'singleactivity') {
                $heading = $this->page->course->fullname;
                // remove mod header
            } elseif (theme_sql_is_customised_page($this->page)) {
                return '';
            } else {
                $heading = $this->page->cm->get_formatted_name();
                $imagedata = $this->pix_icon('monologo', '', $this->page->activityname, ['class' => 'activityicon']);
                $purposeclass = plugin_supports('mod', $this->page->activityname, FEATURE_MOD_PURPOSE);
                $purposeclass .= ' activityiconcontainer';
                $purposeclass .= ' modicon_' . $this->page->activityname;
                $imagedata = html_writer::tag('div', $imagedata, ['class' => $purposeclass]);
                $prefix = get_string('modulename', $this->page->activityname);
            }
        }

        $contextheader = new \context_header($heading, $headinglevel, $imagedata, $userbuttons, $prefix);
        return $this->render_context_header($contextheader);
    }

    /**
     * @param $totalcount
     * @param $page
     * @param $perpage
     * @param $baseurl
     * @param bool $showafterbutton
     * @param string $pagevar
     * @return bool|string
     * @throws \coding_exception
     */
    public function sql_paging_bar($totalcount, $page, $perpage, $baseurl = null, $type = 'quiz_navigation', $pagevar = 'page') {
        if (empty($baseurl)){
            $baseurl = $this->page->url;
        }
        $class = "\\theme_sql\\sql_{$type}_paging_bar";
        if (class_exists($class)) {
            $pb = new $class($totalcount, $page, $perpage, $baseurl, $pagevar);
        } else {
            $pb = new \theme_sql\sql_paging_bar($totalcount, $page, $perpage, $baseurl, $pagevar);
        }

        return $this->render($pb);
    }

    /**
     * Returns HTML to display the paging bar.
     *
     * @param \theme_sql\sql_paging_bar $pagingbar
     * @return string the HTML to output.
     */
    protected function render_sql_paging_bar(\theme_sql\sql_paging_bar $pagingbar) {
        // Any more than 10 is not usable and causes weird wrapping of the pagination.
        return $this->render_from_template('theme_sql/sql_paging_bar', $pagingbar->export_for_template($this));
    }

    public function navbar(): string {
        $newnav = new \theme_sql\sql_boostnavbar($this->page);
        return $this->render_from_template('core/navbar', $newnav);
    }

    public function activity_navigation(){
        // First we should check if we want to add navigation.
        $context = $this->page->context;
        $available_layouts = ['incourse', 'freametop', 'modurl'];
        if (!in_array($this->page->pagelayout, $available_layouts)
            || $context->contextlevel != CONTEXT_MODULE) {
            return '';
        }

        // If the activity is in stealth mode, show no links.
        if ($this->page->cm->is_stealth()) {
            return '';
        }

        $course = $this->page->cm->get_course();

        /** Do not check courseindex */
//        $courseformat = course_get_format($course);

        // If the theme implements course index and the current course format uses course index and the current
        // page layout is not 'frametop' (this layout does not support course index), show no links.
//        if ($this->page->theme->usescourseindex && $courseformat->uses_course_index() &&
//            $this->page->pagelayout !== 'frametop') {
//            return '';
//        }

        // Get a list of all the activities in the course.
        $modules = get_fast_modinfo($course->id)->get_cms();

        // Put the modules into an array in order by the position they are shown in the course.
        $mods = [];
//        $activitylist = [];
        foreach ($modules as $module) {
            // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
            if (!$module->uservisible || $module->is_stealth() || empty($module->url)) {
                continue;
            }
            $mods[$module->id] = $module;

            // No need to add the current module to the list for the activity dropdown menu.
            if ($module->id == $this->page->cm->id) {
                continue;
            }
            // Module name.
            $modname = $module->get_formatted_name();
            // Display the hidden text if necessary.
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }
            // Module URL.
            $linkurl = new \moodle_url($module->url, array('forceview' => 1));
            // Add module URL (as key) and name (as value) to the activity list array.
//            $activitylist[$linkurl->out(false)] = $modname;
        }

        $nummods = count($mods);

        // If there is only one mod then do nothing.
        if ($nummods == 1) {
            return '';
        }

        // Get an array of just the course module ids used to get the cmid value based on their position in the course.
        $modids = array_keys($mods);

        // Get the position in the array of the course module we are viewing.
        $position = array_search($this->page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        // Check if we have a previous mod to show.
        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        // Check if we have a next mod to show.
        if ($position < ($nummods - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new \core_course\output\activity_navigation($prevmod, $nextmod);
        $renderer = $this->page->get_renderer('core', 'course');
        return $renderer->render($activitynav);
    }

    public function footer(){
        $js_code = '';
        $file_url = theme_sql_get_prismjs_file_url();
        if (defined('THEME_SQL_CONNECT_PRISM') && THEME_SQL_CONNECT_PRISM){
            $js_code .= html_writer::script('',);
        } else {
            $url = $file_url->out(false);
            $this->page->requires->js_call_amd('theme_sql/detect_code_highlight', 'init', [$url]);
        }
        $footer = parent::footer();
        return $js_code.$footer;
    }
}
