<?php

/**
 * Local SQL upgrade
 *
 * @package     local_sql
 * @copyright   2022 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/db/upgradelib.php');
require_once($CFG->dirroot.'/local/sql/db/upgradelib.php');

/**
 * Upgrade the local_sql plugin.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 */
function xmldb_local_sql_upgrade($oldversion){
    global $DB;
    $dbman = $DB->get_manager();

    $transaction = $DB->start_delegated_transaction();
    if ($oldversion < 2022113001){
        update_repository_sortnumber();

        // Sql_comments savepoint reached.
        upgrade_plugin_savepoint(true, 2022113001, 'local', 'sql');
    }

    if($oldversion < 2022122300){
        $users = $DB->get_records('user', [], '', 'id, firstname');
        foreach ($users as $userid => $notused){
            update_filepicker_preference($userid);
        }
    }


    if ($oldversion < 2022122301) {
        create_custom_field();
    }

    if ($oldversion < 2023011800) {
        $jsoncustomfiletypes = get_config('core', 'customfiletypes');
        $customfiletypes = json_decode($jsoncustomfiletypes);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $customfiletypes = [];
        }
        $is_exist = false;
        foreach ($customfiletypes as $customfiletype) {
            if ($customfiletype->extension == 'sql') {
                $is_exist = true;
            }
        }
        if (!$is_exist) {
            $customfiletypes[] = [
                'extension' => 'sql',
                'type' => 'application/sql',
                'icon' => 'markup',
                'customdescription' => 'Mysql',
            ];
            $jsoncustomfiletypes = json_encode($customfiletypes);
            set_config('customfiletypes', $jsoncustomfiletypes);
        }
    }

    if ($oldversion < 2023021301) {
        create_custom_field();
    }

    if ($oldversion < 2023021700) {
        // Define table sql_mod_actions to be created.
        $table = new xmldb_table('sql_mod_actions');

        // Adding fields to table sql_mod_actions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('position', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table sql_mod_actions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);

        // Conditionally launch create table for sql_mod_actions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2023082800){
        $module = $DB->get_field('modules', 'id', ['name' => 'url']);
        $DB->execute('UPDATE {course_modules} SET completion = 1, completionview = 0 WHERE module = ?', [$module]);
    }

    if ($oldversion < 2024072900) {

        $table = new xmldb_table('user');
        $field = new xmldb_field('waitonboarding', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'description'); // Adjust the last field name accordingly.

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024072900, 'local', 'sql');
    }

    if ($oldversion < 2024082600) {
        $table = new xmldb_table('sql_extended');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2024082600, 'local', 'sql');
    }

    if ($oldversion < 2024102200) {
        $table = new xmldb_table('auth_stripe_price');
        $field = new xmldb_field('is_allow_coupon', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0); // Adjust the last field name accordingly.

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024102200, 'local', 'sql');
    }

    if ($oldversion < 2024111800) {
        $table = new xmldb_table('auth_stripe_price');
        $field = new xmldb_field('is_checkout', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0); // Adjust the last field name accordingly.

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024111800, 'local', 'sql');
    }

    if ($oldversion < 2024112100) {
        $ref_owners = new xmldb_table('sql_referrals_owner');

        $ref_owners->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $ref_owners->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ref_owners->add_field('code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $ref_owners->add_field('balance', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, 0.0);
        $ref_owners->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());

        $ref_owners->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $ref_owners->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($ref_owners)) {
            $dbman->create_table($ref_owners);
        }

        $ref = new xmldb_table('sql_referrals');

        $ref->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $ref->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ref->add_field('ownerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ref->add_field('is_bonus_allow', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
        $ref->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());

        $ref->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $ref->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $ref->add_key('ownerid', XMLDB_KEY_FOREIGN, ['ownerid'], 'sql_referrals_owner', ['id']);

        if (!$dbman->table_exists($ref)) {
            $dbman->create_table($ref);
        }

        $ref_payment = new xmldb_table('sql_referrals_payment');

        $ref_payment->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $ref_payment->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ref_payment->add_field('ownerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $ref_payment->add_field('is_coaching', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $ref_payment->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, time());

        $ref_payment->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $ref_payment->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        if (!$dbman->table_exists($ref_payment)) {
            $dbman->create_table($ref_payment);
        }

        upgrade_plugin_savepoint(true, 2024112100, 'local', 'sql');
    }

    if ($oldversion < 2024120900) {
        $paypal = new xmldb_table('sql_paypal');

        $paypal->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $paypal->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('amount', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('uniqueid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('batchid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('success', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('refund', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $paypal->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $paypal->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($paypal)) {
            $dbman->create_table($paypal);
        }

        upgrade_plugin_savepoint(true, 2024120900, 'local', 'sql');
    }

    $transaction->allow_commit();
    return true;
}