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
 * External API
 *
 * @package    block_sql_comments
 * @since      Moodle 2.4
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use block_sql_comments\sql_comment;
use const block_sql_comments\STATUS_USER_UNVOTE;

defined('MOODLE_INTERNAL') || die;

class block_sql_comments_external extends external_api {


    public static function set_karma_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_TEXT, 'Invoice id'),
                'karma' => new external_value(PARAM_INT, 'Invoice id')
            )
        );
    }

    /**
     * Сhange karma for comment
     *
     * @param $id
     * @param $karma
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_karma($id,$karma){

        $params = self::validate_parameters(self::set_karma_parameters(), array('id' => $id,'karma' => $karma));
        global $USER;
        $p = explode('-',$params["id"]);
        $karma_obj = new \block_sql_comments\karma($p[1]);

        if ($karma) {
            $vote = \block_sql_comments\VOTE_UP;
        } else {
            $vote = \block_sql_comments\VOTE_DOWN;
        }
        $karma_obj->updateKarma($vote);
        $return = [
            'karma' => $karma_obj->karma,
            'error' => implode(' ',$karma_obj->error),
            'class' => [
                'up' => ($karma_obj->checkToVote(\block_sql_comments\VOTE_UP) == \block_sql_comments\STATUS_USER_UNVOTE) ? 'voting' : '',
                'down' => ($karma_obj->checkToVote(\block_sql_comments\VOTE_DOWN) == \block_sql_comments\STATUS_USER_UNVOTE) ? 'voting' : '',
            ]
        ];
        return $return;
    }

    public static function set_karma_returns(){
        return new external_single_structure([
            'karma' => new external_value(PARAM_INT, 'Karma'),
            'error' => new external_value(PARAM_TEXT, 'Error'),
            'class' => new external_single_structure([
                'up' => new external_value(PARAM_TEXT, 'up'),
                'down' => new external_value(PARAM_TEXT, 'down'),
            ])
        ]);
    }


    public static function delete_comment_parameters(){
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_TEXT, 'Invoice id')
            )
        );
    }

    public static function delete_comment($id){
        global $DB;

    }

    public static function delete_comment_returns(){
        return new external_single_structure([
            'karma' => new external_value(PARAM_INT, 'Karma'),

        ]);
    }

}

