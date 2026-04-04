<?php
defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/db/upgradelib.php");
/**
 * Upgrade the block_rss_client database.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 * @return boolean
 * @throws dml_exception
 */
function xmldb_block_sql_comments_upgrade($oldversion)
{
    global $CFG, $DB;

    if ($oldversion < 2022112202) {
        $dbman = $DB->get_manager();
        // Define table block_sql_comments_karma to be created.
        $table = new xmldb_table('block_sql_comments_karma');

        // Adding fields to table block_sql_comments_karma.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('karma', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_sql_comments_karma.
        $table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_sql_comments_karma.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_sql_comments_karma_u to be created.
        $table = new xmldb_table('block_sql_comments_karma_u');

        // Adding fields to table block_sql_comments_karma_u.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ckid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('karma', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('setuserid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_sql_comments_karma_u.
        $table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_sql_comments_karma_u.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Sql_comments savepoint reached.
        upgrade_block_savepoint(true, 2022112202, 'sql_comments');
    }

    if ($oldversion < 2022112501) {
        if (empty($DB->get_record('user_info_field', ['shortname' => 'carma_points']))) {
            $row = new stdClass();
            $row->shortname = 'carma_points';
            $row->name = 'Carma points';
            $row->datatype = 'text';
            $row->description = '';
            $row->descriptionformat = 1;
            $row->categoryid = 1;
            $row->sortorder = 1;
            $row->required = 0;
            $row->locked = 1;
            $row->visible = 0;
            $row->forceunique = 0;
            $row->signup = 0;
            $row->defaultdata = '0';
            $row->defaultdataformat = 0;
            $row->param1 = 30;
            $row->param2 = 2048;
            $row->param3 = 0;
            $row->param4 = '';
            $row->param5 = '';
            $DB->insert_record('user_info_field', $row);
        }
        // Sql_comments savepoint reached.
        upgrade_block_savepoint(true, 2022112501, 'sql_comments');
    }

    return true;
}