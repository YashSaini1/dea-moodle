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

use qbank_managecategories\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Form for moving questions between categories.
 *
 * @package    qbank_managecategories
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sql_question_move_form extends moodleform {

    public $available_categories = [];

    /**
     * Build the form definition.
     *
     * This adds all the form fields that the question move feature needs.
     * @throws \coding_exception
     */
    protected function definition() {
        $mform = $this->_form;

        $currentcat = $this->_customdata['currentcat'];
        $contexts = $this->_customdata['contexts'];

        // we need to get default system category as default
        // If user not change it - trigger validation message
        $this->available_categories = [];
        foreach ($contexts as $ctx){
            if ($ctx->contextlevel != CONTEXT_COURSE){
                continue;
            }
            $q_cat = helper::get_categories_for_contexts($ctx->id, 'sortorder, id', true);
            $top = current($q_cat);

            // get only course sub categories (without default)
            foreach ($q_cat as $child_cat){
                if ($child_cat->id != $top->id && $child_cat->parent != $top->id){
                    $this->available_categories[$child_cat->id.','.$child_cat->contextid] = $child_cat->name;
                }
            }
        }

        if (count($this->available_categories) > 1){
            $mform->addElement('select', 'category', get_string('category', 'question'), $this->available_categories);
            $mform->addRule('category', 'required', 'required', null, 'client');

            $this->add_action_buttons(true, get_string('categorymoveto', 'question'));
        } else {
            $mform->addElement('cancel');
        }

        $mform->addElement('hidden', 'delete', $currentcat);
        $mform->setType('delete', PARAM_INT);
    }
}
