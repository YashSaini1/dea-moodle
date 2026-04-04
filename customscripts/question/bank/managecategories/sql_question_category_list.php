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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir. '/listlib.php');
require_once($CFG->customscripts."/question/bank/managecategories/sql_question_category_list_item.php");

/**
 * Class representing a list of question categories.
 *
 * @package    qbank_managecategories
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sql_question_category_list extends \qbank_managecategories\question_category_list {

    /**
     * List item class name.
     * @var $listitemclassname
     */
    public $listitemclassname = '\sql_question_category_list_item';

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $attributes
     * @param boolean $editable
     * @param \moodle_url $pageurl url for this page
     * @param integer $page if 0 no pagination. (These three params only used in top level list.)
     * @param string $pageparamname name of url param that is used for passing page no
     * @param integer $itemsperpage no of top level items.
     * @param \context $context
     */
    public function __construct($type='ul', $attributes='', $editable = false, $pageurl=null,
                                $page = 0, $pageparamname = 'page',
                                $itemsperpage = DEFAULT_QUESTIONS_PER_PAGE, $context = null) {
        moodle_list::__construct('ul', $attributes, $editable, $pageurl, $page, 'cpage', $itemsperpage);
        $this->context = $context;
    }
}
