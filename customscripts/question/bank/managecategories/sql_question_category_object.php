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

use \qbank_managecategories\helper;

require_once($CFG->customscripts."/question/bank/managecategories/sql_question_category_edit_form.php");
require_once($CFG->customscripts."/question/bank/managecategories/sql_question_category_list.php");

/**
 * Class for performing operations on question categories.
 *
 * @var sql_question_category_list_item[] $editlists
 *
 * @package    qbank_managecategories
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sql_question_category_object extends \qbank_managecategories\question_category_object {

    /**
     * Constructor.
     *
     * @param int $page page number.
     * @param moodle_url $pageurl base URL of the display categories page. Used for redirects.
     * @param context[] $contexts contexts where the current user can edit categories.
     * @param int $currentcat id of the category to be edited. 0 if none.
     * @param int|null $defaultcategory id of the current category. null if none.
     * @param int $todelete id of the category to delete. 0 if none.
     * @param context[] $addcontexts contexts where the current user can add questions.
     */
    public function __construct($page, $pageurl, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts) {
        parent::__construct($page, $pageurl, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts);
    }

    /**
     * Initializes this classes general category-related variables
     *
     * @param int $page page number.
     * @param context[] $contexts contexts where the current user can edit categories.
     * @param int $currentcat id of the category to be edited. 0 if none.
     * @param int|null $defaultcategory id of the current category. null if none.
     * @param int $todelete id of the category to delete. 0 if none.
     * @param context[] $addcontexts contexts where the current user can add questions.
     */
    public function initialize($page, $contexts, $currentcat, $defaultcategory, $todelete, $addcontexts): void {
        $lastlist = null;
        $count = 1;
        $paged = false;

        // show only COURSE category
        foreach ($contexts as $context){
            if ($context->contextlevel != CONTEXT_COURSE){
                continue;
            }
            $this->editlists[$context->id] =
                new sql_question_category_list('ul', 'class="category-list"', true, $this->pageurl, $page, 'cpage', QUESTION_PAGE_LENGTH, $context);
            $this->editlists[$context->id]->lastlist =& $lastlist;
            if ($lastlist !== null) {
                $lastlist->nextlist =& $this->editlists[$context->id];
            }
            $this->editlists[$context->id]->list_from_records($paged, $count);
            $lastlist =& $this->editlists[$context->id];
        }

        $this->catform = new \sql_question_category_edit_form($this->pageurl,
            ['contexts' => $contexts, 'currentcat' => $currentcat ?? 0]);
        if (!$currentcat) {
            $this->catform->set_data(['parent' => $defaultcategory]);
        }
    }

    /**
     * Display the form to move a category.
     *
     * @param int $questionsincategory
     * @param object $category
     * @throws \coding_exception
     */
    public function display_move_form($questionsincategory, $category): void {
        global $OUTPUT;
        $vars = new stdClass();
        $vars->name = $category->name;
        $vars->count = $questionsincategory;
        if(count($this->moveform->available_categories) > 1){
            echo $OUTPUT->box(get_string('categorymove', 'question', $vars), 'generalbox boxaligncenter');
        } else {
            echo $OUTPUT->box(get_string('cannot_delete_category', 'theme_sql', $vars), 'generalbox boxaligncenter');
        }
        $this->moveform->display();
    }

    /**
     * Outputs a list to allow editing/rearranging of existing categories.
     *
     * $this->initialize() must have already been called
     *
     */
    public function output_edit_lists(): void {
        global $OUTPUT;

        foreach ($this->editlists as $context => $list) {
            $key = key($list->items);
            $list->items[$key]->item->hide_category_info = true;
            $listhtml = $list->to_html(0, ['str' => $this->str]);
            if ($listhtml) {
                echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox questioncategories contextlevel' . $list->context->contextlevel);
                $fullcontext = context::instance_by_id($context);
                echo $OUTPUT->heading(get_string('questioncatsfor', 'question', $fullcontext->get_context_name()), 3);
                echo $listhtml;
                echo $OUTPUT->box_end();
            }
        }
        echo $list->display_page_numbers();
    }

    /**
     * Edit a category, or add a new one if the id is zero.
     *
     * @param int $categoryid Category id.
     */
    public function edit_single_category(int $categoryid): void {
        // Interface for adding a new category.
        global $DB;

        if ($categoryid) {
            // Editing an existing category.
            $category = $DB->get_record("question_categories", ["id" => $categoryid], '*', MUST_EXIST);
            if ($category->parent == 0) {
                throw new moodle_exception('cannotedittopcat', 'question', '', $categoryid);
            }

            $category->parent = "{$category->parent},{$category->contextid}";
            $category->submitbutton = get_string('savechanges');
            $category->categoryheader = $this->str->edit;
            $this->catform->set_data($category);
        }

        // Show the form.
        $this->catform->display();
    }

    /**
     * Create a new category.
     *
     * Data is expected to come from question_category_edit_form.
     *
     * By default redirects on success, unless $return is true.
     *
     * @param string $newparent 'categoryid,contextid' of the parent category.
     * @param string $newcategory the name.
     * @param string $newinfo the description.
     * @param bool $return if true, return rather than redirecting.
     * @param int|string $newinfoformat description format. One of the FORMAT_ constants.
     * @param null $idnumber the idnumber. '' is converted to null.
     * @return bool|int New category id if successful, else false.
     */
    public function add_category($newparent, $newcategory, $newinfo, $return = false, $newinfoformat = FORMAT_HTML,
            $idnumber = null): int {
        global $DB;
        if (empty($newcategory)) {
            throw new moodle_exception('categorynamecantbeblank', 'question');
        }
        list($parentid, $contextid) = explode(',', $newparent);
        // ...moodle_form makes sure select element output is legal no need for further cleaning.
        require_capability('moodle/question:managecategory', context::instance_by_id($contextid));

        if ($parentid) {
            if (!($DB->get_field('question_categories', 'contextid', ['id' => $parentid]) == $contextid)) {
                throw new moodle_exception('cannotinsertquestioncatecontext', 'question', '',
                    ['cat' => $newcategory, 'ctx' => $contextid]);
            }
        }

        if ((string) $idnumber === '') {
            $idnumber = null;
        } else if (!empty($contextid)) {
            // While this check already exists in the form validation, this is a backstop preventing unnecessary errors.
            if ($DB->record_exists('question_categories',
                    ['idnumber' => $idnumber, 'contextid' => $contextid])) {
                $idnumber = null;
            }
        }

        $cat = new stdClass();
        $cat->parent = $parentid;
        $cat->contextid = $contextid;
        $cat->name = $newcategory;
        $cat->info = $newinfo;
        $cat->infoformat = $newinfoformat;
        $cat->sortorder = 999;
        $cat->stamp = make_unique_id_code();
        $cat->idnumber = $idnumber;
        $categoryid = $DB->insert_record("question_categories", $cat);

        // Log the creation of this category.
        $category = new stdClass();
        $category->id = $categoryid;
        $category->contextid = $contextid;
        $event = \core\event\question_category_created::create_from_question_category_instance($category);
        $event->trigger();

        if ($return) {
            return $categoryid;
        } else {
            // Always redirect after successful action.
            redirect($this->pageurl);
        }
    }

    /**
     * Updates an existing category with given params.
     *
     * Warning! parameter order and meaning confusingly different from add_category in some ways!
     *
     * @param int $updateid id of the category to update.
     * @param int $newparent 'categoryid,contextid' of the parent category to set.
     * @param string $newname category name.
     * @param string $newinfo category description.
     * @param int|string $newinfoformat description format. One of the FORMAT_ constants.
     * @param int $idnumber the idnumber. '' is converted to null.
     * @param bool $redirect if true, will redirect once the DB is updated (default).
     */
    public function update_category($updateid, $newparent, $newname, $newinfo, $newinfoformat = FORMAT_HTML,
            $idnumber = null, $redirect = true): void {
        global $CFG, $DB;
        if (empty($newname)) {
            throw new moodle_exception('categorynamecantbeblank', 'question');
        }

        // Get the record we are updating.
        $oldcat = $DB->get_record('question_categories', ['id' => $updateid]);
        $lastcategoryinthiscontext = helper::question_is_only_child_of_top_category_in_context($updateid);

        if (!empty($newparent) && !$lastcategoryinthiscontext) {
            list($parentid, $tocontextid) = explode(',', $newparent);
        } else {
            $parentid = $oldcat->parent;
            $tocontextid = $oldcat->contextid;
        }

        // Check permissions.
        $fromcontext = context::instance_by_id($oldcat->contextid);
        require_capability('moodle/question:managecategory', $fromcontext);

        // If moving to another context, check permissions some more, and confirm contextid,stamp uniqueness.
        $newstamprequired = false;
        if ($oldcat->contextid != $tocontextid) {
            $tocontext = context::instance_by_id($tocontextid);
            require_capability('moodle/question:managecategory', $tocontext);

            // Confirm stamp uniqueness in the new context. If the stamp already exists, generate a new one.
            if ($DB->record_exists('question_categories', ['contextid' => $tocontextid, 'stamp' => $oldcat->stamp])) {
                $newstamprequired = true;
            }
        }

        if ((string) $idnumber === '') {
            $idnumber = null;
        } else if (!empty($tocontextid)) {
            // While this check already exists in the form validation, this is a backstop preventing unnecessary errors.
            if ($DB->record_exists_select('question_categories',
                    'idnumber = ? AND contextid = ? AND id <> ?',
                    [$idnumber, $tocontextid, $updateid])) {
                $idnumber = null;
            }
        }

        // Update the category record.
        $cat = new stdClass();
        $cat->id = $updateid;
        $cat->name = $newname;
        $cat->info = $newinfo;
        $cat->infoformat = $newinfoformat;
        $cat->parent = $parentid;
        $cat->contextid = $tocontextid;
        $cat->idnumber = $idnumber;
        if ($newstamprequired) {
            $cat->stamp = make_unique_id_code();
        }
        $DB->update_record('question_categories', $cat);
        // Update the set_reference records when moving a category to a different context.
        move_question_set_references($cat->id, $cat->id, $oldcat->contextid, $tocontextid);

        // Log the update of this category.
        $event = \core\event\question_category_updated::create_from_question_category_instance($cat);
        $event->trigger();

        // If the category name has changed, rename any random questions in that category.
        if ($oldcat->name != $cat->name) {
            // Get the question ids for each question category.
            $questionids = $this->get_real_question_ids_in_category($cat->id);

            foreach ($questionids as $question) {
                $where = "qtype = 'random' AND id = ? AND " . $DB->sql_compare_text('questiontext') . " = ?";

                $randomqtype = question_bank::get_qtype('random');
                $randomqname = $randomqtype->question_name($cat, false);
                $DB->set_field_select('question', 'name', $randomqname, $where, [$question->id, '0']);

                $randomqname = $randomqtype->question_name($cat, true);
                $DB->set_field_select('question', 'name', $randomqname, $where, [$question->id, '1']);
            }
        }

        if ($oldcat->contextid != $tocontextid) {
            // Moving to a new context. Must move files belonging to questions.
            question_move_category_to_context($cat->id, $oldcat->contextid, $tocontextid);
        }

        // Cat param depends on the context id, so update it.
        $this->pageurl->param('cat', $updateid . ',' . $tocontextid);
        if ($redirect) {
            // Always redirect after successful action.
            redirect($this->pageurl);
        }
    }

}
