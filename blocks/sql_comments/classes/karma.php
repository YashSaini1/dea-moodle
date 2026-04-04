<?php


namespace block_sql_comments;

/**
 * Table names
 */
const TABLE_KARMA = 'block_sql_comments_karma';
const TABLE_KARMA_USER = 'block_sql_comments_karma_u';

/**
 * Statuses for karma
 */
const STATUS_USER_VOTE = 1;
const STATUS_USER_UNVOTE = 2;
const STATUS_USER_CHANGE_VOTE = 3;

const TABLE_PROFILE_FIELD_KARMA = 'carma_points';

/**
 * Karma
 */
const VOTE_UP = 1;
const VOTE_DOWN = -1;

class karma
{
    /**
     * @var
     */
    public $id;

    /**
     * @var
     */
    public $commentid;

    /**
     * @var
     */
    public $userid;

    /**
     * @var
     */
    public $karma;

    /**
     * @var bool|false|mixed|\stdClass
     */
    public $comment;

    /**
     * @var array
     */
    public $error = [];


    /**
     * karma constructor.
     * @param $commentid
     * @throws \dml_exception
     */
    public function __construct($commentid)
    {
        global $DB;
        $this->comment = $DB->get_record('comments', ['id' => $commentid]);

        $karma = $DB->get_record(TABLE_KARMA, ['commentid' => $commentid]);

        if (!$karma) {
            $karma = $this->create($commentid);
        }

        if ($karma) {
            $this->id = $karma->id;
            $this->commentid = $karma->commentid;
            $this->userid = $karma->userid;
            $this->karma = $karma->karma;
        }
    }

    /**
     * @param $commentid
     * @return \stdClass
     */
    public function create($commentid)
    {
        global $DB;
        $obj = new \stdClass();
        $obj->commentid = $commentid;
        $obj->userid = $this->comment->userid;
        $obj->karma = 0;

        try {
            $obj->id = $DB->insert_record(TABLE_KARMA, $obj);
        } catch (\dml_exception $e) {
            $this->error[] = $e->getMessage();
        }
        return $obj;
    }

    /**
     * Adding karma to the table
     *
     * @param $vote
     */
    public function addUserVoteToComment($vote)
    {
        global $DB, $USER;
        $obj = new \stdClass();
        $obj->ckid = $this->id;
        $obj->userid = $USER->id;
        $obj->karma = $vote;
        $obj->setuserid = $this->userid;
        try {
            $DB->insert_record(TABLE_KARMA_USER, $obj);
        } catch (\dml_exception $e) {
            $this->error[] = $e->getMessage();
        }
    }

    /**
     * Removing karma from a comment
     *
     * @throws \dml_exception
     */
    public function deleteUserVoteAll()
    {
        global $DB, $USER;
        $DB->delete_records(TABLE_KARMA_USER, ['ckid' => $this->id, 'userid' => $USER->id]);
    }

    /**
     * User karma updates
     *
     * @param $vote
     * @param bool $set
     * @throws \dml_exception
     */
    public function updateUserProfileKarma($vote, $set = false)
    {
        global $CFG,$USER;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $user = \core_user::get_user($this->userid);
        if ($user) {
            profile_load_data($user);
            if ($set) {
                $user->profile_field_carma_points = $vote;
            } else {
                $user->profile_field_carma_points += $vote;
            }

            profile_save_data($user);
        }

    }

    /**
     *
     */
    public function save()
    {
        global $DB, $USER, $CFG;

        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->karma = $this->karma;
        try {
            $DB->update_record(TABLE_KARMA, $obj);
        } catch (\dml_exception $e) {
            $this->error[] = $e->getMessage();
        }

    }

    /**
     * Checking for the possibility of changing karma
     *
     * @param $vote
     * @return int
     * @throws \dml_exception
     */
    public function checkToVote($vote)
    {
        global $DB, $USER;
        $karma = $DB->get_record(TABLE_KARMA_USER, ['ckid' => $this->id, 'userid' => $USER->id]);
        if (!$karma) {
            return STATUS_USER_VOTE;
        } else {
            if ($karma->karma == $vote) {
                return STATUS_USER_UNVOTE;
            } else {
                return STATUS_USER_CHANGE_VOTE;
            }
        }
    }

    /**
     * Update karma
     *
     * @param $vote
     * @throws \dml_exception
     */
    public function updateKarma($vote)
    {
        global $DB, $USER;
        switch ($this->checkToVote($vote)) {
            case STATUS_USER_UNVOTE:
                $this->karma += ($vote * -1);
                $this->deleteUserVoteAll();
                $this->updateUserProfileKarma($vote * -1);
                $this->save();
                break;
            case STATUS_USER_VOTE:
                $this->karma = $this->karma + $vote;
                $this->updateUserProfileKarma($vote);
                $this->addUserVoteToComment($vote);
                $this->save();
                break;
            case STATUS_USER_CHANGE_VOTE:
                $this->karma += ($vote * 2);
                $this->deleteUserVoteAll();
                $this->addUserVoteToComment($vote);
                $this->updateUserProfileKarma($vote * 2);
                $this->save();
                break;
        }
    }

    /**
     * Removing karma in comment
     *
     * @throws \dml_exception
     */
    public function delete()
    {
        global $DB;
        $DB->delete_records(TABLE_KARMA_USER, ['ckid' => $this->id]);
        $DB->delete_records(TABLE_KARMA, ['commentid' => $this->commentid]);
        $this->updateUserProfileKarma($this->getKarmaFromVotingUsers(), true);
    }

    /**
     * Getting the user's karma amount
     * @return bool|mixed
     * @throws \dml_exception
     */
    public function getKarmaFromVotingUsers()
    {
        global $DB;
        return $DB->get_field_select(TABLE_KARMA_USER, 'SUM(karma)', 'setuserid=:setuserid', ['setuserid' => $this->userid]);
    }

    /**
     * @param $user
     * @return bool|int|mixed
     * @throws \dml_exception
     */
    static function getKarmaUser($user)
    {
        global $DB;
        try {
            $karma = $DB->get_field_sql("SELECT uid.data
            FROM {user_info_data} as uid
            JOIN {user_info_field} AS uif ON uid.fieldid = uif.id AND uif.shortname = :shortname
            WHERE uid.userid = :userid", ['shortname' => TABLE_PROFILE_FIELD_KARMA, 'userid' => $user->id]);
        } catch (\moodle_exception $e) {
            $karma = '-';
        }

        return $karma ?? 0;
    }
}