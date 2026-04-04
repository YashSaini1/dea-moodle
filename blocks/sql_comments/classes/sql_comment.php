<?php
namespace block_sql_comments;

use coding_exception;
use comment;
use core_component;
use html_writer;
use moodle_url;
use stdClass;

class sql_comment extends comment {
    /**
     * The component that this comment is for.
     *
     * It is STRONGLY recommended to set this.
     * Added as a database field in 2.9, old comments will have a null component.
     *
     * @var string
     */
    protected $component;
    /** @var string This is calculated by normalising the component */
    protected $pluginname;
    /** @var string This is calculated by normalising the component */
    protected $plugintype;

    /** @var bool Use non-javascript UI */
    protected static $nonjs = false;
    /** @var int comment itemid used in non-javascript UI */
    protected static $comment_itemid = null;
    /** @var int comment context used in non-javascript UI */
    protected static $comment_context = null;
    /** @var string comment area used in non-javascript UI */
    protected static $comment_area = null;
    /** @var string comment page used in non-javascript UI */
    protected static $comment_page = null;
    /** @var string comment itemid component in non-javascript UI */
    protected static $comment_component = null;

    /**
     * @var int comment character limit
     */
    private $max_len = 1000;

    public function __construct(stdClass $options){
        self::$nonjs = optional_param('nonjscomment', '', PARAM_ALPHANUM);
        self::$comment_itemid = optional_param('comment_itemid',  '', PARAM_INT);
        self::$comment_component = optional_param('comment_component', '', PARAM_COMPONENT);
        self::$comment_context = optional_param('comment_context', '', PARAM_INT);
        self::$comment_page = optional_param('comment_page',    '', PARAM_INT);
        self::$comment_area = optional_param('comment_area',    '', PARAM_AREA);

        parent::__construct($options);
    }

    /**
     * Sets the component.
     *
     * This method shouldn't be public, changing the component once it has been set potentially
     * invalidates permission checks.
     * A coding_error is now thrown if code attempts to change the component.
     *
     * @throws coding_exception if you try to change the component after it has been set.
     * @param string $component
     */
    public function set_component($component) {
        if (!empty($this->component) && $this->component !== $component) {
            throw new coding_exception('You cannot change the component of a comment once it has been set');
        }
        $this->component = $component;
        list($this->plugintype, $this->pluginname) = core_component::normalize_component($component);
        parent::set_component($component);
    }

    /**
     * Change "output" to add karma to output
     *
     * @param bool $return
     * @return string|string[]|void
     */
    public function output($return = true)
    {
        global $PAGE, $OUTPUT;
        static $template_printed;

        $this->initialise_javascript($PAGE);

        if (!empty(self::$nonjs)) {
            // return non js comments interface
            return $this->print_comments(self::$comment_page, $return, true);
        }

        $html = '';

        // print html template
        // Javascript will use the template to render new comments
        if (empty($template_printed) && $this->can_view()) {
            $html .= html_writer::tag('div', $this->get_template(), array('style' => 'display:none', 'id' => 'cmt-tmpl'));
            $template_printed = true;
        }

        if ($this->can_view()) {
            // print commenting icon and tooltip
            $html .= html_writer::start_tag('div', array('class' => 'mdl-left'));
            $html .= html_writer::link($this->get_nojslink($PAGE), get_string('showcommentsnonjs'), array('class' => 'showcommentsnonjs'));

            if (!$this->notoggle) {
                // If toggling is enabled (notoggle=false) then print the controls to toggle
                // comments open and closed
                $countstring = '';
                if ($this->displaytotalcount) {
                    $countstring = '('.$this->count().')';
                }

                if (right_to_left()) {
                    $collapsedimage= 't/collapsed_rtl';
                } else {
                    $collapsedimage= 't/collapsed';
                }
                $html .= html_writer::start_tag('a', array(
                        'class' => 'comment-link',
                        'id' => 'comment-link-'.$this->get_cid(),
                        'href' => '#',
                        'role' => 'button',
                        'target' => '_blank',
                        'aria-expanded' => 'false')
                );
                $html .= $OUTPUT->pix_icon($collapsedimage, $this->get_linktext());
                $html .= html_writer::tag('span', $this->get_linktext().' '.$countstring, array('id' => 'comment-link-text-'.$this->get_cid()));
                $html .= html_writer::end_tag('a');
            }

            $html .= html_writer::start_tag('div', array('id' => 'comment-ctrl-'.$this->get_cid(), 'class' => 'comment-ctrl'));

            if ($this->autostart) {
                // If autostart has been enabled print the comments list immediatly
                $html .= html_writer::start_tag('ul', array('id' => 'comment-list-'.$this->get_cid(), 'class' => 'comment-list comments-loaded'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= $this->print_comments(0, true, false);
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= $this->get_pagination(0);
            } else {
                $html .= html_writer::start_tag('ul', array('id' => 'comment-list-'.$this->get_cid(), 'class' => 'comment-list'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= html_writer::tag('div', '', array('id' => 'comment-pagination-'.$this->get_cid(), 'class' => 'comment-pagination'));
            }

            if ($this->can_post()) {
                // print posting textarea
                $textareaattrs = array(
                    'name' => 'content',
                    'rows' => 2,
                    'id' => 'dlg-content-'.$this->get_cid(),
                    'aria-label' => get_string('comment_placeholder', 'block_sql_comments'),
                    'maxlength' => $this->max_len, // added character limit to comment
                );
                if (!$this->fullwidth) {
                    $textareaattrs['cols'] = '20';
                } else {
                    $textareaattrs['class'] = 'fullwidth';
                }

                $html .= html_writer::start_tag('div', array('class' => 'comment-area'));
                $html .= html_writer::start_tag('div', array('class' => 'db'));
                $html .= html_writer::tag('textarea', '', $textareaattrs);
                $html .= html_writer::end_tag('div'); // .db

                $html .= html_writer::start_tag('div', array('class' => 'fd', 'id' => 'comment-action-'.$this->get_cid()));
                $html .= html_writer::link('#', get_string('postcomment', 'block_sql_comments'),
                    array('id' => 'comment-action-post-'.$this->get_cid()));

                if ($this->displaycancel) {
                    $html .= html_writer::tag('span', ' | ');
                    $html .= html_writer::link('#', get_string('cancel'), array('id' => 'comment-action-cancel-'.$this->get_cid()));
                }

                $html .= html_writer::end_tag('div'); // .fd
                $html .= html_writer::end_tag('div'); // .comment-area
                $html .= html_writer::tag('div', '', array('class' => 'clearer'));
            }

            $html .= html_writer::end_tag('div'); // .comment-ctrl
            $html .= html_writer::end_tag('div'); // .mdl-left
        } else {
            $html = '';
        }

        // add open link in new tab
        $html = str_replace('class="user"><a',
            'class="user"><a target="_blank"',$html);

        $html = str_replace('class="picture"><a',
            'class="picture"><a target="_blank"',$html);

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * Change add to add karma to output
     *
     * @param string $content
     * @param int|string $format
     * @return \stdClass
     * @throws \comment_exception
     */
    public function add($content, $format = FORMAT_MOODLE)
    {
        $content = substr($content, 0, $this->max_len + 1);
        $comment = parent::add($content, $format);
        $comment->content .= '</div><div class="comment-vote"><span class="up"></span><span class="vote">0</span><span class="down" ></span></div>';
        return $comment;
    }

    /**
     * Change get_comments to add karma to output
     *
     * @param string $page
     * @param string $sortdirection
     * @return array|bool
     * @throws \dml_exception
     */
    public function get_comments($page = '', $sortdirection = 'DESC') {
        global $DB, $CFG, $USER, $OUTPUT;
        if (!$this->can_view()) {
            return false;
        }
        if (!is_numeric($page)) {
            $page = 0;
        }
        $params = array();
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
        $userfieldsapi = \core_user\fields::for_userpic();
        $ufields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

        list($componentwhere, $component) = $this->get_component_select_sql('c');
        if ($component) {
            $params['component'] = $component;
        }

        $sortdirection = ($sortdirection === 'ASC') ? 'ASC' : 'DESC';
        $sql = "SELECT $ufields, c.id AS cid, c.content AS ccontent, c.format AS cformat, c.timecreated AS ctimecreated
                  FROM {comments} c
                  JOIN {user} u ON u.id = c.userid
                 WHERE c.contextid = :contextid AND
                       c.commentarea = :commentarea AND
                       c.itemid = :itemid AND
                       $componentwhere
              ORDER BY c.timecreated $sortdirection, c.id $sortdirection";
        $params['contextid'] = $this->get_context()->id;
        $params['commentarea'] = $this->get_commentarea();
        $params['itemid'] = $this->get_itemid();

        $comments = array();
        $formatoptions = array('overflowdiv' => true, 'blanktarget' => true);
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        foreach ($rs as $u) {
            $c = new stdClass();
            $c->id          = $u->cid;
            $c->content     = $u->ccontent;
            $c->format      = $u->cformat;
            $c->timecreated = $u->ctimecreated;
            $c->strftimeformat = get_string('strftimerecentfull', 'langconfig');
            $url = new moodle_url('/user/profile.php', array('id'=>$u->id));
            $c->profileurl = $url->out(false); // URL should not be escaped just yet.
            $c->fullname = fullname($u);
            $c->time = userdate($c->timecreated, $c->strftimeformat);
            $c->content = format_text($c->content, $c->format, $formatoptions);

            $karma_obj = new karma($c->id);
            $up = ($karma_obj->checkToVote(VOTE_UP) == STATUS_USER_UNVOTE) ? ' voting' : '';
            $down = ($karma_obj->checkToVote(VOTE_DOWN) == STATUS_USER_UNVOTE) ? ' voting' : '';

            $karma = $karma_obj->karma;
            if (!$karma) {
                $karma = 0;
            }
            $c->content .= '<div class="comment-vote"><span class="up '.$up.'"></span><span class="vote">'.$karma.'</span><span class="down '.$down.
                '"></span></div>';
            $c->avatar = $OUTPUT->user_picture($u, array('size' => 16));
            $c->userid = $u->id;

            if ($this->can_delete($c)) {
                $c->delete = true;
            }
            $comments[] = $c;
        }

        $rs->close();

        if (!empty($this->plugintype)) {
            // moodle module will filter comments
            $comments = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'display', array($comments, $this->comment_param), $comments);
        }

        return $comments;
    }

    /**
     * @param int|\stdClass $comment
     * @return bool
     * @throws \comment_exception
     * @throws \dml_exception
     */
    public function delete($comment)
    {
        $status = parent::delete($comment);
        if ($status) {
            $karma = new karma($comment);
            $karma->delete();
        }
        return $status;
    }
}