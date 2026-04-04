<?php

/**
 * Configuration settings declaration information for the CodeRunner question type.
 *
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  2014 Richard Lobb, The University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use qtype_pythonrunner\constants;

require_once($CFG->dirroot . '/question/type/pythonrunner/lib.php');

$links = array(
    pythonrunner_str('bulkquestiontester', array('link' => (string) new moodle_url('/question/type/pythonrunner/bulktestindex.php'))),
);
$settings->add(new admin_setting_heading('supportscripts', pythonrunner_str('supportscripts'), '* ' . implode("\n* ", $links)));

$settings->add(new admin_setting_heading('pythonrunnerdbsettings',
    pythonrunner_str('pythonrunnerwssettings'), ''));

$settings->add(new admin_setting_heading('pythonrunnersettings', pythonrunner_str('pythonrunnersettings'), ''));

$settings->add(new admin_setting_configtext(
    "qtype_pythonrunner/default_penalty_regime",
    pythonrunner_str('default_penalty_regime'),
    pythonrunner_str('default_penalty_regime_desc'),
    '10, 20, ...'
));

$sandboxes = qtype_pythonrunner_sandbox::available_sandboxes();
foreach ($sandboxes as $sandbox => $classname) {
    $settings->add(new admin_setting_configcheckbox(
        "qtype_pythonrunner/{$sandbox}_enabled",
        pythonrunner_str('enable') . ' ' .$sandbox,
        pythonrunner_str('enable_sandbox_desc'),
        $sandbox === 'jobesandbox')  // Only jobesandbox is enabled by default.
    );
}

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/jobe_host",
        pythonrunner_str('jobe_host'),
        pythonrunner_str('jobe_host_desc'),
        constants::JOBE_HOST_DEFAULT,
        PARAM_RAW,
        60)
);

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/jobe_apikey",
        pythonrunner_str('jobe_apikey'),
        pythonrunner_str('jobe_apikey_desc'),
        constants::JOBE_HOST_DEFAULT_API_KEY));

$settings->add(new admin_setting_configtext(
    "qtype_pythonrunner/jobe_apikey",
    pythonrunner_str('jobe_apikey'),
    pythonrunner_str('jobe_apikey_desc'),
    constants::JOBE_HOST_DEFAULT_API_KEY));

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/ideone_user",
    pythonrunner_str('ideone_user'),
    pythonrunner_str('ideone_user_desc'),
        ''));

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/ideone_password",
    pythonrunner_str('ideone_pass'),
    pythonrunner_str('ideone_pass_desc'),
        ''));

$settings->add(new admin_setting_heading('pythonrunnerwssettings',
    pythonrunner_str('pythonrunnerwssettings'), ''));

$settings->add(new admin_setting_configcheckbox(
        "qtype_pythonrunner/wsenabled",
        sqlrunner_str('enable_sandbox_ws'),
        sqlrunner_str('enable_sandbox_ws_desc'),
        false)
);

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/wsjobeserver",
        sqlrunner_str('jobe_host_ws'),
        sqlrunner_str('jobe_host_ws_desc'),
        '',
        PARAM_RAW,
        60)
);

$settings->add(new admin_setting_configcheckbox(
        "qtype_pythonrunner/wsloggingenabled",
        pythonrunner_str('wsloggingenable'),
        pythonrunner_str('wsloggingenable_desc'),
        true)
);

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/wsmaxhourlyrate",
        pythonrunner_str('wsmaxhourlyrate'),
        pythonrunner_str('wsmaxhourlyrate_desc'),
        '200')
);

$settings->add(new admin_setting_configtext(
        "qtype_pythonrunner/wsmaxcputime",
        pythonrunner_str('wsmaxcputime'),
        pythonrunner_str('wsmaxcputime_desc'),
        '5')
);
