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

/**
 * Defines the form for editing question categories.
 *
 * Form for editing questions categories (name, description, etc.)
 *
 * @package    qbank_managecategories
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sql_question_category_edit_form extends \qbank_managecategories\form\question_category_edit_form {

    /**
     * Build the form definition.
     *
     * This adds all the form fields that the manage categories feature needs.
     * @throws \coding_exception
     */
    protected function definition() {
        $mform = $this->_form;

        $currentcat = $this->_customdata['currentcat'];

        $mform->addElement('header', 'categoryheader', get_string('addcategory', 'question'));

        $mform->addElement('hidden', 'parent', $currentcat);
        $mform->setType('parent', PARAM_SEQUENCE);
        if (helper::question_is_only_child_of_top_category_in_context($currentcat)) {
            $mform->hardFreeze('parent');
        }

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->setDefault('name', '');
        $mform->addRule('name', get_string('categorynamecantbeblank', 'question'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('editor', 'info', get_string('categoryinfo', 'question'),
                ['rows' => 10], ['noclean' => 1]);
        $mform->setDefault('info', '');
        $mform->setType('info', PARAM_RAW);

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'question'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumber', 'question');
        $mform->setType('idnumber', PARAM_RAW);

        $this->add_action_buttons(true, get_string('addcategory', 'question'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
    }
}
