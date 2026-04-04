<?php

/**
 * Local AB testing upgrade lib
 *
 * @package     local_ab_testing
 * @copyright   2024 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_ab_testing_upgrade_profile_fields_data($replacements){
    global $DB;

    $get_column = function($column_name) use ($DB){
        $columns = $DB->get_columns('user_info_data');
        foreach ($columns as $column){
            if ($column->name == $column_name){
                return $column;
            }
        }
        return null;
    };

    $column = $get_column('data');
    if (!$column){
        throw new \Exception('Cannot fetch column "data" in "user_info_data" table');
    }

    foreach ($replacements as $old_value => $new_value){
        $DB->replace_all_text('user_info_data', $column, $old_value, $new_value);
    }
}