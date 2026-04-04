<?php

/**
 * Defines the \mod_quiz\structure class.
 *
 * @package   mod_quiz
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sql\mod_quiz;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Quiz structure class.
 *
 * The structure of the quiz. That is, which questions it is built up
 * from. This is used on the Edit quiz page (edit.php) and also when
 * starting an attempt at the quiz (startattempt.php). Once an attempt
 * has been started, then the attempt holds the specific set of questions
 * that that student should answer, and we no longer use this class.
 *
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_sql_structure extends \mod_quiz\structure {

    /**
     * Create an instance of this class representing an empty quiz.
     * @return theme_sql_structure
     */
    public static function create() {
        return new static();
    }

    /**
     * Create an instance of this class representing the structure of a given quiz.
     *
     * @param \quiz $quizobj the quiz.
     *
     * @return theme_sql_structure
     */
    public static function create_for_quiz($quizobj) {
        $structure = static::create();
        $structure->quizobj = $quizobj;
        $structure->populate_structure();
        return $structure;
    }

    /**
     * Quizzes can only be edited if they have not been attempted.
     * @return bool whether the quiz can be edited.
     */
    public function can_be_edited() {
        if ($this->canbeedited === null) {
            // set always true, we need to update and move questions in real time
            $this->canbeedited = true;//!quiz_has_attempts($this->quizobj->get_quizid());
        }
        return $this->canbeedited;
    }
    /**
     * Move a slot from its current location to a new location.
     *
     * After callig this method, this class will be in an invalid state, and
     * should be discarded if you want to manipulate the structure further.
     *
     * @param int $idmove id of slot to be moved
     * @param int $idmoveafter id of slot to come before slot being moved
     * @param int $page new page number of slot being moved
     * @param bool $insection if the question is moving to a place where a new
     *      section starts, include it in that section.
     * @return void
     */
    public function move_slot($idmove, $idmoveafter, $page) {
        global $DB;

        $this->check_can_be_edited();

        $movingslot = $this->get_slot_by_id($idmove);
        if (empty($movingslot)) {
            throw new \moodle_exception('Bad slot ID ' . $idmove);
        }
        $movingslotnumber = (int) $movingslot->slot;

        // Empty target slot means move slot to first.
        if (empty($idmoveafter)) {
            $moveafterslotnumber = 0;
        } else {
            $moveafterslotnumber = (int) $this->get_slot_by_id($idmoveafter)->slot;
        }

        // If the action came in as moving a slot to itself, normalise this to
        // moving the slot to after the previous slot.
        if ($moveafterslotnumber == $movingslotnumber) {
            $moveafterslotnumber = $moveafterslotnumber - 1;
        }

        $followingslotnumber = $moveafterslotnumber + 1;
        // Prevent checking against non-existance slot when already at the last slot.
        if ($followingslotnumber == $movingslotnumber && !$this->is_last_slot_in_quiz($followingslotnumber)) {
            $followingslotnumber += 1;
        }

        // Check the target page number is OK.
        if ($page == 0 || $page === '') {
            $page = 1;
        }
        if (($moveafterslotnumber > 0 && $page < $this->get_page_number_for_slot($moveafterslotnumber)) ||
                $page < 1) {
            // Do not trigger error beacause after slot movig we call repaginate function
//            throw new \coding_exception('The target page number is too small.');
        } else if (!$this->is_last_slot_in_quiz($moveafterslotnumber) &&
                $page > $this->get_page_number_for_slot($followingslotnumber)) {
            throw new \coding_exception('The target page number is too large.');
        }

        // Work out how things are being moved.
        $slotreorder = array();
        if ($moveafterslotnumber > $movingslotnumber) {
            // Moving down.
            $slotreorder[$movingslotnumber] = $moveafterslotnumber;
            for ($i = $movingslotnumber; $i < $moveafterslotnumber; $i++) {
                $slotreorder[$i + 1] = $i;
            }

            $headingmoveafter = $movingslotnumber;
            if ($this->is_last_slot_in_quiz($moveafterslotnumber) ||
                    $page == $this->get_page_number_for_slot($moveafterslotnumber + 1)) {
                // We are moving to the start of a section, so that heading needs
                // to be included in the ones that move up.
                $headingmovebefore = $moveafterslotnumber + 1;
            } else {
                $headingmovebefore = $moveafterslotnumber;
            }
            $headingmovedirection = -1;

        } else if ($moveafterslotnumber < $movingslotnumber - 1) {
            // Moving up.
            $slotreorder[$movingslotnumber] = $moveafterslotnumber + 1;
            for ($i = $moveafterslotnumber + 1; $i < $movingslotnumber; $i++) {
                $slotreorder[$i] = $i + 1;
            }

            if ($page == $this->get_page_number_for_slot($moveafterslotnumber + 1)) {
                // Moving to the start of a section, don't move that section.
                $headingmoveafter = $moveafterslotnumber + 1;
            } else {
                // Moving tot the end of the previous section, so move the heading down too.
                $headingmoveafter = $moveafterslotnumber;
            }
            $headingmovebefore = $movingslotnumber + 1;
            $headingmovedirection = 1;
        } else {
            // Staying in the same place, but possibly changing page/section.
            if ($page > $movingslot->page) {
                $headingmoveafter = $movingslotnumber;
                $headingmovebefore = $movingslotnumber + 2;
                $headingmovedirection = -1;
            } else if ($page < $movingslot->page) {
                $headingmoveafter = $movingslotnumber - 1;
                $headingmovebefore = $movingslotnumber + 1;
                $headingmovedirection = 1;
            } else {
                return; // Nothing to do.
            }
        }

        if ($this->is_only_slot_in_section($movingslotnumber)) {
            throw new \coding_exception('You cannot remove the last slot in a section.');
        }

        $trans = $DB->start_delegated_transaction();

        // Slot has moved record new order.
        if ($slotreorder) {
            update_field_with_unique_index('quiz_slots', 'slot', $slotreorder,
                    array('quizid' => $this->get_quizid()));
        }

        // Page has changed. Record it.
        if ($movingslot->page != $page) {
            $DB->set_field('quiz_slots', 'page', $page,
                    array('id' => $movingslot->id));
        }

        // Update section fist slots.
        quiz_update_section_firstslots($this->get_quizid(), $headingmovedirection,
                $headingmoveafter, $headingmovebefore);

        // If any pages are now empty, remove them.
        $emptypages = $DB->get_fieldset_sql("
                SELECT DISTINCT page - 1
                  FROM {quiz_slots} slot
                 WHERE quizid = ?
                   AND page > 1
                   AND NOT EXISTS (SELECT 1 FROM {quiz_slots} WHERE quizid = ? AND page = slot.page - 1)
              ORDER BY page - 1 DESC
                ", array($this->get_quizid(), $this->get_quizid()));

        foreach ($emptypages as $emptypage) {
            $DB->execute("
                    UPDATE {quiz_slots}
                       SET page = page - 1
                     WHERE quizid = ?
                       AND page > ?
                    ", array($this->get_quizid(), $emptypage));
        }

        $trans->allow_commit();

        // Log slot moved event.
        $event = \mod_quiz\event\slot_moved::create([
            'context' => $this->quizobj->get_context(),
            'objectid' => $idmove,
            'other' => [
                'quizid' => $this->quizobj->get_quizid(),
                'previousslotnumber' => $movingslotnumber,
                'afterslotnumber' => $moveafterslotnumber,
                'page' => $page
             ]
        ]);
        $event->trigger();
    }
}