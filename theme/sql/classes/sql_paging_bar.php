<?php

namespace theme_sql;

use moodle_url;
use renderer_base;
use stdClass;

/**
 * Class sql_paging_bar
 * @package theme_sql
 */
class sql_paging_bar extends \paging_bar
{
    public $currpage = 0;
    public $lastpage = 1;
    public $maxdisplay = 10;

    /**
     * @param renderer_base $output
     * @return stdClass
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->previous = null;
        $data->next = null;
        $data->first = null;
        $data->last = null;
        $data->label = get_string('page');
        $data->pages = [];
        $data->haspages = true;
        $data->pagesize = $this->perpage;

        $this->init($data);

        if (!$data->haspages) {
            return $data;
        }

        $data->previous = $this->getPrevious();

        $data->first = $this->getFirst();
        $this->setLastPage();
        $data->pages = $this->getPages();
        $data->last = $this->getLast();
        $data->next = $this->getNext();

        return $data;
    }

    /**
     * @param $data
     */
    function init(&$data)
    {

    }

    /**
     *
     */
    function setLastPage()
    {
        if ($this->perpage > 0) {
            $this->lastpage = (int) ceil($this->totalcount / $this->perpage);
        }
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    function getPages()
    {
        $displaycount = 0;
        $pages = [];

        while ($displaycount < $this->maxdisplay and $this->currpage < $this->lastpage) {
            $displaypage = $this->currpage + 1;

            $iscurrent = $this->page == $this->currpage;
            $link = new moodle_url($this->baseurl, [$this->pagevar => $this->currpage]);

            $pages[] = [
                'page' => $displaypage,
                'active' => $iscurrent,
                'class' => '',
                'url' => $iscurrent ? null : $link->out(false)
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
    function getFirst()
    {
        if ($this->page > round(($this->maxdisplay / 3) * 2)) {
            $this->currpage = (int) ($this->page - round($this->maxdisplay / 3));
            return [
                'page' => 1,
                'url' => (new moodle_url($this->baseurl, [$this->pagevar => 0]))->out(false)
            ];
        }
        return [];
    }

    /**
     * @return array
     * @throws \moodle_exception
     */
    function getPrevious()
    {
        if ($this->page > 0) {
            return [
                'page' => $this->page,
                'url' => (new moodle_url($this->baseurl, [$this->pagevar => $this->page - 1]))->out(false)
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
    function getNext()
    {
        if ($this->page + 1 != $this->lastpage) {
            return [
                'page' => $this->page + 2,
                'url' => (new moodle_url($this->baseurl, [$this->pagevar => $this->page + 1]))->out(false),
                'class' => '',
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
                'url' => (new moodle_url($this->baseurl, [$this->pagevar => $this->lastpage - 1]))->out(false),
                'class' => '',
            ];
        }
        return [];
    }
}