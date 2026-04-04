<?php

namespace local_sql;

require_once($CFG->dirroot.'/local/sql/lib.php');

/**
 * Custom mod actions.
 * This actions only saved and displayed for users, but client want to add this.
 *
 * TODO: in future we can add ACTION_TYPE_FILE (client says about it)
 *
 * @package     local_sql
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_actions {

    /**
     * @var string actions table
     */
    const MOD_ACTIONS_TABLE = 'sql_mod_actions';

    /**
     * @var string action type. Can be another type in future
     */
    const ACTION_TYPE_CHECKBOX = 'checkbox';

    /**
     * @var numeric cmid
     */
    protected $_cmid;

    /**
     * @param numeric $cmid
     */
    public function __construct($cmid){
        $this->_cmid = $cmid;
    }

    /**
     * Get all modules actions
     *
     * @param numeric $cmid
     * @param bool    $reload
     *
     * @return object[]
     */
    static public function get_by_cmid($cmid, $reload = false){
        static $mod_actions = [];
        if (!array_key_exists($cmid, $mod_actions) || $reload){
            global $DB;
            $mod_actions[$cmid] = $DB->get_records(static::MOD_ACTIONS_TABLE, ['cmid' => $cmid], 'position');
        }
        return $mod_actions[$cmid];
    }

    /**
     * Delete module actions
     *
     * @param numeric $cmid
     * @param array   $action_ids - deleted ids. Cmid must be the same
     */
    static public function delete_actions($cmid, $action_ids = []){
        global $DB;

        if (empty($action_ids)){
            $DB->delete_records(static::MOD_ACTIONS_TABLE, ['cmid' => $cmid]);
            return;
        }

        [$select, $params] = $DB->get_in_or_equal($action_ids, SQL_PARAMS_NAMED, 'id');
        $select = 'cmid=:cmid AND id '.$select;
        $params['cmid'] = $cmid;
        $DB->delete_records_select(static::MOD_ACTIONS_TABLE, $select, $params);
    }

    /**
     * Save new module actions.
     * If there are no records, create them.
     * In other cases, update and delete.
     */
    public function save($actions){
        $old_actions = static::get_by_cmid($this->_cmid);
        if (empty($old_actions)){
            $this->_create_new_actions_raw($actions);
            return;
        }

        [$add, $update, $delete] = $this->_process_inputted_actions($old_actions, $actions);
        $this->_delete_actions_by_ids($delete);
        $this->_update_actions($update);
        $this->_create_new_actions($add);
        $old_actions = static::get_by_cmid($this->_cmid, true);
    }

    /**
     * Save prepared actions
     *
     * @param array{position:numeric, info:string} $actions
     */
    protected function _create_new_actions($actions){
        global $DB;

        if (empty($actions)){
            return;
        }

        foreach ($actions as $action){
            $DB->insert_record(static::MOD_ACTIONS_TABLE, [
                'cmid'     => $this->_cmid,
                'type'     => static::ACTION_TYPE_CHECKBOX,
                'position' => $action['position'],
                'info'     => $action['info'],
            ]);
        }
    }

    /**
     * Save record by info
     *
     * @param string[] $actions  - array with info
     * @param numeric  $position - start position
     */
    protected function _create_new_actions_raw($actions, $position = 0){
        global $DB;

        if (empty($actions)){
            return;
        }

        foreach ($actions as $info){
            $info = local_sql_clear_spaces($info);
            if (empty($info)){
                continue;
            }

            $DB->insert_record(static::MOD_ACTIONS_TABLE, [
                'cmid'     => $this->_cmid,
                'type'     => static::ACTION_TYPE_CHECKBOX,
                'position' => $position,
                'info'     => $info,
            ]);
            $position++;
        }
    }

    /**
     * Update module actions
     *
     * @param array[] $actions - records to update
     */
    protected function _update_actions($actions){
        global $DB;
        if (empty($actions)){
            return;
        }

        foreach ($actions as $action){
            $DB->update_record(static::MOD_ACTIONS_TABLE, $action);
        }
    }

    /**
     * Delete records by ids.
     * @see static::delete_actions()
     *
     * @param array $action_ids - deleted ids
     */
    protected function _delete_actions_by_ids($action_ids){
        if (empty($action_ids)){
            return;
        }

        static::delete_actions($this->_cmid, $action_ids);
    }

    /**
     * Process old and new actions
     *
     *
     * @param object[] $old_actions
     * @param string[] $new_actions
     *
     * @return array[] of 3 arrays: [records_to_add, records_to_update, records_to_delete]
     */
    protected function _process_inputted_actions($old_actions, $new_actions){
        // $add_actions - array with actions to add. Each action is array
        // $update_actions - array with actions to update. Each action is array
        // $old_actions - array with objects which stored in DB. In the end, elements in this array will be deleted.
        //                  To save object, unset it from this array. Indexes here are records id
        $add_actions = $update_actions = [];
        // Iterate all new actions
        $position = 0;
        foreach ($new_actions as $info){
            $info = local_sql_clear_spaces($info);

            $found = false;
            foreach ($old_actions as $old_key => $old_action){
                // Find action with the same info
                if ($info != $old_action->info){
                    continue;
                }

                // If info and position are the same - don't need touch this action
                if ($old_action->position == $position){
                    $position++;
                    unset($old_actions[$old_key]);
                    continue 2;
                }

                $found = true;
                $old_action->position = $position;
                $old_action->info = $info;

                $update_actions[] = (array)$old_action;
                unset($old_actions[$old_key]);
                break;
            }
            if ($found){
                $position++;
                continue;
            }
            if (!empty($info)){
                $add_actions[] = [
                    'info'     => $info,
                    'position' => $position,
                ];
                $position++;
            }
        }

        // Updating records instead of its delete and create another one
        if (count($add_actions) >= count($old_actions)){
            $old_actions = array_values($old_actions);
            foreach ($old_actions as $key => $old_action){
                $add = $add_actions[$key];
                $old_action->info = $add['info'];
                $old_action->position = $add['position'];
                $update_actions[] = (array)$old_action;
                unset($add_actions[$key]);
            }
            $old_actions = [];
        } else {
            $old_actions = array_keys($old_actions);
        }

        return [$add_actions, $update_actions, $old_actions];
    }

    /**
     * Render elements
     *
     * @param numeric $cmid
     *
     * @return array of rendered actions.
     */
    static public function render_elements($cmid){
        $actions = static::get_by_cmid($cmid);
        $result = [];
        foreach ($actions as $action){
//            if ($action->type == static::ACTION_TYPE_CHECKBOX){}
            $result[] = \html_writer::checkbox('action', $action->id, 0, $action->info, [
                'class' => 'sql_checkbox',
            ]);
        }
        return $result;
    }
}