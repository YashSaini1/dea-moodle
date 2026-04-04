<?php
// This file is part of CodeRunner - http://coderunner.org.nz/
//
// CodeRunner is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunner is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunner.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Configuration settings declaration information for the CodeRunner question type.
 *
 * @package    qtype
 * @subpackage sqlrunner
 * @copyright  2014 Richard Lobb, The University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use qtype_sqlrunner\constants;

require_once($CFG->dirroot . '/question/type/sqlrunner/lib.php');

$links = array(
    sqlrunner_str('bulkquestiontester', array('link' => (string) new moodle_url('/question/type/sqlrunner/bulktestindex.php'))),
    sqlrunner_str('bulkquestiontester', array('link' => (string) new moodle_url('/question/type/sqlrunner/dbsettings.php')))
);
$settings->add(new admin_setting_heading('supportscripts', sqlrunner_str('supportscripts'), '* ' . implode("\n* ", $links)));


$settings->add(new admin_setting_heading('sqlRunnerdbsettings',
    sqlrunner_str('sqlrunnerwssettings'), ''));

$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_host",
        sqlrunner_str('dbhost'),
        sqlrunner_str('dbhost'),
        '',
        PARAM_RAW,
        100)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_port",
        sqlrunner_str('dbport'),
        sqlrunner_str('dbport'),
        3306,
        PARAM_RAW,
        7)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_name",
        sqlrunner_str('dbname'),
        sqlrunner_str('dbname'),
        '',
        PARAM_RAW,
        60)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_user",
        sqlrunner_str('dbuser'),
        sqlrunner_str('dbuser'),
        '',
        PARAM_RAW,
        60)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_pass",
        sqlrunner_str('dbpass'),
        sqlrunner_str('dbpass'),
        '',
        PARAM_RAW,
        60)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_root_user",
        sqlrunner_str('dbrootuser'),
        sqlrunner_str('dbrootuser'),
        '',
        PARAM_RAW,
        60)
);
$settings->add(new admin_setting_configtext("qtype_sqlrunner/db_root_pass",
        sqlrunner_str('dbrootpass'),
        sqlrunner_str('dbrootpass'),
        '',
        PARAM_RAW,
        60)
);

$settings->add(new admin_setting_heading('sqlRunnersettings', sqlrunner_str('sqlrunnersettings'), ''));

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/default_penalty_regime",
        sqlrunner_str('default_penalty_regime'),
        sqlrunner_str('default_penalty_regime_desc'),
        '10, 20, ...'
        ));

$sandboxes = qtype_sqlrunner_sandbox::available_sandboxes();
foreach ($sandboxes as $sandbox => $classname) {
    $settings->add(new admin_setting_configcheckbox(
        "qtype_sqlrunner/{$sandbox}_enabled",
        sqlrunner_str('enable') . ' ' .$sandbox,
        sqlrunner_str('enable_sandbox_desc'),
        $sandbox === 'jobesandbox')  // Only jobesandbox is enabled by default.
    );
}

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/jobe_host",
        sqlrunner_str('jobe_host'),
        sqlrunner_str('jobe_host_desc'),
        constants::JOBE_HOST_DEFAULT,
        PARAM_RAW,
        60)
);

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/jobe_apikey",
        sqlrunner_str('jobe_apikey'),
        sqlrunner_str('jobe_apikey_desc'),
        constants::JOBE_HOST_DEFAULT_API_KEY));

$settings->add(new admin_setting_configtext(
    "qtype_sqlrunner/jobe_apikey",
    sqlrunner_str('jobe_apikey'),
    sqlrunner_str('jobe_apikey_desc'),
    constants::JOBE_HOST_DEFAULT_API_KEY));

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/ideone_user",
    sqlrunner_str('ideone_user'),
    sqlrunner_str('ideone_user_desc'),
        ''));

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/ideone_password",
    sqlrunner_str('ideone_pass'),
    sqlrunner_str('ideone_pass_desc'),
        ''));

$settings->add(new admin_setting_heading('sqlRunnerwssettings',
    sqlrunner_str('sqlrunnerwssettings'), ''));

$settings->add(new admin_setting_configcheckbox(
        "qtype_sqlrunner/wsenabled",
        sqlrunner_str('enable_sandbox_ws'),
        sqlrunner_str('enable_sandbox_ws_desc'),
        false)
);

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/wsjobeserver",
        sqlrunner_str('jobe_host_ws'),
        sqlrunner_str('jobe_host_ws_desc'),
        '',
        PARAM_RAW,
        60)
);

$settings->add(new admin_setting_configcheckbox(
        "qtype_sqlrunner/wsloggingenabled",
        sqlrunner_str('wsloggingenable'),
        sqlrunner_str('wsloggingenable_desc'),
        true)
);

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/wsmaxhourlyrate",
        sqlrunner_str('wsmaxhourlyrate'),
        sqlrunner_str('wsmaxhourlyrate_desc'),
        '200')
);

$settings->add(new admin_setting_configtext(
        "qtype_sqlrunner/wsmaxcputime",
        sqlrunner_str('wsmaxcputime'),
        sqlrunner_str('wsmaxcputime_desc'),
        '5')
);
