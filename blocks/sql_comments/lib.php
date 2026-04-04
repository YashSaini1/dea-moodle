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
 * The comments block helper functions and callbacks
 *
 * @package   block_sql_comments
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Validate comment parameter before perform other comments actions
 *
 * @package  block_sql_comments
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function block_sql_comments_comment_validate($comment_param) {
    if ($comment_param->commentarea != 'page_comments') {
        throw new comment_exception('invalidcommentarea');
    }
    return true;
}

/**
 * Running addtional permission check on plugins
 *
 * @package  block_sql_comments
 * @category comment
 *
 * @param stdClass $args
 * @return array
 */
function block_sql_comments_comment_permissions($args) {
    return array('post'=>true, 'view'=>true);
}

/**
 * Validate comment data before displaying comments
 *
 * @package  block_sql_comments
 * @category comment
 *
 * @param stdClass $comment
 * @param stdClass $args
 * @return boolean
 */
function block_sql_comments_comment_display($comments, $args) {
    if ($args->commentarea != 'page_comments') {
        throw new comment_exception('invalidcommentarea');
    }
    return $comments;
}

/**
 * Override comments template
 *
 * @package  block_sql_comments
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return string
 */
function block_sql_comments_comment_template($template_params) {
    // load template
    $template = html_writer::start_tag('div', array('class' => 'comment-message'));

    $template .= html_writer::start_tag('div', array('class' => 'comment-message-meta mr-3'));

    $template .= html_writer::tag('span', '___picture___', array('class' => 'picture'));

    $template .= html_writer::start_div('comment-user-time');
    $template .= html_writer::tag('span', '___name___', array('class' => 'user'));
    $template .= html_writer::tag('span', '___time___', array('class' => 'time'));
    $template .= html_writer::end_div();

    $template .= html_writer::end_tag('div'); // .comment-message-meta
    $template .= html_writer::tag('div', '___content___', array('class' => 'text'));

    $template .= html_writer::end_tag('div'); // .comment-message
    return $template;
}
