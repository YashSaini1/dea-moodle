<?php

namespace theme_sql;

use auth_stripe\model\user_tier;
use auth_stripe\subscription\tier_processor;
use moodle_url;

/**
 * Class sql_quiz_navigation_paging_bar
 * @package theme_sql
 */
class sql_quiz_navigation_paging_bar extends sql_paging_bar
{
    public $showafterbutton = false;
    public $freecount = 0;
    public $currpage = 0;
    public $lastpage = 1;
    public $positionleft = true;
    public $is_premium = false;

    /**
     * @param $data
     */
    function init(&$data)
    {
        global $COURSE;
        $this->showafterbutton = true;
        $this->is_premium = tier_processor::user_has_tier(user_tier::PREMIUM_TIER);
        $this->freecount = \local_sql\moodle\course_customfield::get_number_of_free_questions($COURSE->id);
        $data->smallbar = $this->totalcount <= $this->maxdisplay;
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    function getPages()
    {
        $displaycount = 0;
        $pages = [];
        $maxdisplay = $this->maxdisplay;
        if ($maxdisplay < round($this->totalcount / $this->perpage)) {
            if ($this->currpage > 0) {
                $maxdisplay = $this->maxdisplay - 3;
            }
            if (($this->lastpage - $this->currpage) < $maxdisplay) {
                $this->currpage = $this->lastpage - $maxdisplay;
            }
            $this->positionleft = false;
        }
        global $attemptobj;
        while ($displaycount < $maxdisplay and $this->currpage < $this->lastpage) {
            $displaypage = $this->currpage + 1;

            $iscurrent = $this->page == $this->currpage;
            $link = new moodle_url($this->baseurl, [$this->pagevar => $this->currpage]);
            try {
                $qa = $attemptobj->get_question_attempt($displaypage);
                $class = $qa->get_state_class(false);
            } catch (\moodle_exception $ex) {
                $class = '';
            }

            $url = (!$this->is_premium && $displaypage > $this->freecount) ? null : ($iscurrent ? null : $link->out(false));
            $pages[] = [
                'page' => $displaypage,
                'active' => $iscurrent,
                'class' => (!$this->is_premium && $displaypage > $this->freecount) ? 'upgrade_plan disabled '.$class : $class,
                'url' => $url
            ];

            $displaycount++;
            $this->currpage++;
        }

        return $pages;
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    function getNext()
    {
        if ($this->page + 1 != $this->lastpage) {
            return [
                'page' => $this->page + 2,
                'url' => (!$this->is_premium && ($this->page + 2) > $this->freecount) ? '#' : (new moodle_url($this->baseurl, [$this->pagevar => $this->page + 1]))->out(false),
                'class' => (!$this->is_premium && $this->page + 2 > $this->freecount) ? 'upgrade_plan disabled' : '',
            ];
        } else {
            return [
                'disabled' => true,
                'page' => $this->page,
                'url' => '#',
            ];
        }
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    function getLast()
    {
        if ($this->currpage < $this->lastpage) {
            return [
                'page' => $this->lastpage,
                'url' => !$this->is_premium && ($this->lastpage > $this->freecount) ? '#' : (new moodle_url($this->baseurl, [$this->pagevar => $this->lastpage - 1]))->out(false),
                'class' => (!$this->is_premium && $this->lastpage > $this->freecount) ? 'upgrade_plan disabled' : '',
            ];
        }
        return [];
    }
}