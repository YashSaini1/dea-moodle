<?php

namespace local_sql\observers;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Observer for user events
 *
 * @package     local_sql
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm_observer {

    /**
     * Delete modules actions
     *
     * @param \core\event\course_module_deleted $event
     *
     * @return bool
     */
    public static function cm_deleted(\core\event\course_module_deleted $event){
        $cmid = $event->objectid;
        \local_sql\mod_actions::delete_actions($cmid);
        return true;
    }

    /**
     * Create or duplicate course module
     *
     * @param \core\event\course_module_created $event
     *
     * @return bool
     */
    public static function cm_created(\core\event\course_module_created $event){
        $cmid = $event->objectid;
        $duplicate = static::_get_duplicated_cmid();
        if(!empty($duplicate)){
            $old_actions = \local_sql\mod_actions::get_by_cmid($duplicate);
            $old_actions_raw = [];
            foreach ($old_actions as $action){
                $old_actions_raw[] = $action->info;
            }

            $mod_actions = new \local_sql\mod_actions($cmid);
            $mod_actions->save($old_actions_raw);
        }
        return true;
    }

    static protected function _get_duplicated_cmid(){
        // if via course/mod.php
        $duplicate = optional_param('duplicate', 0, PARAM_INT);
        if (!empty($duplicate)){
            return $duplicate;
        }

        // if via services.php
        $service_params = json_decode(file_get_contents('php://input'), true);
        if (empty($service_params)){
            return false;
        }

        $args = $service_params[0]['args'];
        if ($args['action'] !== 'duplicate'){
            return 0;
        }
        return $args['id'] ?? 0;
    }
}