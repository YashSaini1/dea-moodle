<?php

namespace auth_stripe\output\subscription;

use auth_stripe\core;
use auth_stripe\output\stripe\user_tier_output;
use auth_stripe\processor\user_tier\subscription\manual_subscription_manager;
use local_sql\moodle\role_manager;

require_once($CFG->dirroot.'/local/sql/lib.php');

class manual_subscription_renderer {

    public const MANUAL_SUBSCRIPTION_MANAGER_URL = '/auth/stripe/update_tier.php';

    public const ACTION_ADD = 'add';
    public const ACTION_REMOVE = 'remove';

    public const ACTION_BLOCK = 'block';

    public const ACTION_UNBLOCK = 'unblock';

    public const ACTION_ADD_EXTENDED = 'add_extended';
    public $tier_output;

    /**
     * @param user_tier_output $user_tier_output
     */
    public function __construct(user_tier_output $user_tier_output){
        $this->tier_output = $user_tier_output;
    }



    public function create_checkbox_list($userid = null) {
        global $DB;
        if ($userid === null) {
            return [];
        }
        $records = get_sections_by_condition('extended', 1);
        $checkboxes = [];
        foreach ($records as $record) {
            $course = get_course($record->course);
            if ($course == null)
                continue;

            $checked = false;
            if ($DB->record_exists('sql_extended', ['userid' => $userid, 'sectionid' => $record->id])) {
                $checked = true;
            }

            $sectionName = !empty($record->name) ? $record->name : 'Topics';

            $label = 'Course: ' . $course->fullname . ', Section: ' . $sectionName;

            $checkboxes[] = [
                'id' => $record->id,
                'label' => $label,
                'checked' => $checked
            ];
        }

        usort($checkboxes, function($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return $checkboxes;
    }


    public function render_buttons(){
        if (!$this->should_be_rendered()){
            return '';
        }

        $userid = $this->tier_output->userid;
        $user = $this->tier_output->user;

        $context = ['user_buttons' => []];
        $link = new \moodle_url(static::MANUAL_SUBSCRIPTION_MANAGER_URL, ['userid' => $userid]);
        $add_button = function($url_params, $title, $disabled = false) use ($link, &$context){
            $url = clone $link;
            $url->params($url_params);
            $context['user_buttons'][] = [
                'link'    => $url->out(false),
                'title'   => $title,
                'disabled' => $disabled ? 'disabled' : '',
            ];
        };

        $manual_subscription_manager = new manual_subscription_manager();

        // Determine the onboarding state
        $waitonboarding = isset($user->waitonboarding) ? $user->waitonboarding : 0;
        $onboarding_unblock = ($waitonboarding && $this->tier_output->user->waitonboarding);

        // Add the buttons for each type
        foreach (manual_subscription_manager::TYPES_PAGES as $type => $not_used){
            $action = static::ACTION_ADD;
            if ($manual_subscription_manager->user_has_subscription($type, $this->tier_output->user)){
                $action = static::ACTION_REMOVE;
            }
            $add_button([
                'action' => $action,
                'type'   => $type,
            ], ucfirst($action).' '.ucfirst($type), $onboarding_unblock);
        }

        // Add the Onboarding button
        $button_title = $waitonboarding ? 'Unblock Onboarding' : 'Block Onboarding';
        $action = $waitonboarding ? 'unblock' : 'block';
        $add_button([
            'action' => $action,
            'type'   => 'onboarding',
        ], $button_title);

        $add_button([
            'action' => 'add_extended',
            'type'   => 'extended',
        ], 'Add Extended');

        if (empty($context['user_buttons'])){
            return '';
        }

        core::call_js_amd('update_tier_popup', 'init', [
            core::str('update_tier:popup_text'),
            fullname($this->tier_output->user),
            'checkboxes' => $this->create_checkbox_list($userid)
        ]);
        return core::render_from_template('manual_manage_subscription', $context);
    }



    public function should_be_rendered(){
        global $USER;
        return $this->tier_output->userid != $USER->id && role_manager::is_admin();
    }
}