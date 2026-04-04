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
 * Strings for component 'qtype_pythonrunner', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   qtype_pythonrunner
 * @copyright Richard Lobb 2012
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['ace_ui_notready'] = 'Ace editor not ready. Perhaps reload page?';
$string['addingpythonrunner'] = 'Adding a new PythonRunner Question';
$string['ajax_error'] = '*** AJAX ERROR. DON\'T SAVE THIS! ***';
$string['allok'] = 'Passed all tests! ';
$string['allornone'] = 'Test code must be provided either for all testcases or for none.';

$string['allowmultiplestdins'] = 'Allow multiple stdins';
$string['answerprompt'] = 'Answer:';
$string['answer_help'] = 'A sample answer can be entered here and used for checking by the question author and optionally shown to students during review. It is also used by the bulk tester script. The correctness of a non-empty answer is checked when saving unless \'Validate on save\' is unchecked';
$string['answerrequired'] = 'Please provide a non-empty answer';
$string['atleastonetest'] = 'You must provide at least one test case for this question.';
$string['ace-language'] = 'Ace language';
$string['advanced_customisation'] = 'Advanced customisation';
$string['answer'] = 'Answer';
$string['answerbox_group'] = 'Answer box';
$string['answerboxlines'] = 'Rows';
$string['answerbox_group_help'] = 'Set the number of rows to allocate for the answer box. This sets the minimum height of the User Interface element (e.g. Ace) that controls the answer box. The width is set to fit the window. If the answer overflows the box vertically or horizontally, scrollbars will appear.';
$string['answerpreload'] = 'Answer box preload';
$string['answerpreload_help'] = 'Text supplied here will be preloaded into the student\'s answer box.';
$string['asolutionis'] = 'Question author\'s solution:';
$string['attachmentoptions'] = 'Attachment options';

$string['attachmentsoptional'] = 'Attachments are optional';
$string['attachmentsrequired'] = 'Require attachments';
$string['attachmentsrequired_help'] = 'This option specifies the minimum number of attachments required for a response to be graded.';

$string['autotagbycategorytitle'] = 'pythonrunner autotag by category';
$string['autotagbycategoryindextitle'] = 'pythonrunner question autotagger';

$string['badacelangstring'] = 'Bad Ace-language string';
$string['badcputime'] = 'CPU time limit must be left blank or must be an integer greater than zero';
$string['bad_dotdotdot'] = 'Misuse of \'...\'. Must be at end, after two increasing numeric penalties';
$string['bademptyprecheck'] = 'Precheck failed with the following unexpected output.';
$string['bad_empty_splitter'] = 'Test splitter cannot be empty when using a combinator template';
$string['badfilenamesregex'] = 'Invalid regular expression';
$string['badfiles'] = 'Disallowed file name(s): {$a}';
$string['badjsonfunc'] = 'Unknown JSON embedded func ({$a->func})';
$string['badjson'] = 'Bad JSON output from combinator grader output. Output was: {$a->output}';
$string['badmemlimit'] = 'Memory limit must either be left blank or must be a non-negative integer';
$string['bad_new_prototype_name'] = 'Illegal name for new prototype: already in use';
$string['badpenalties'] = 'Penalty regime must be a comma separated list of numbers in the range [0, 100]';
$string['badquestion'] = 'Error in question';
$string['badrandomintarg'] = 'Bad argument to JSON @randomint function';
$string['badrandompickarg'] = 'Bad argument to JSON @randompic function';
$string['badsandboxparams'] = '\'Other\' field (sandbox params) must be either blank or a valid JSON record';
$string['badtemplateparams'] = 'Template parameters must evaluate to blank or a valid JSON record. Got: <pre class="templateparamserror">{$a}</pre>';
$string['baduiparams'] = 'UI parameters must be blank or a valid JSON record.';
$string['brokencombinator'] = 'Expected {$a->numtests} test results, got {$a->numresults}. Perhaps excessive output or error in question?';
$string['brokentemplategrader'] = 'Bad output from grader: {$a->output}. Your program execution may have aborted (e.g. a timeout or memory limit exceeded).';
$string['bulkquestiontester'] = 'The <a href="{$a->link}">bulk tester script</a> tests that the sample answers for all questions in the current context are marked right. Useful only once some questions with sample answers have been added; the initial install has none.';
$string['bulktestallincontext'] = 'Test all';
$string['bulktestcontinuefromhere'] = 'Run again or resume, starting from here';
$string['bulktestindextitle'] = 'pythonrunner bulk testing';
$string['bulktestrun'] = 'Run all the question tests for all the questions in the system (slow, admin only)';
$string['bulktesttitle'] = 'Testing questions in {$a}';

$string['pythonrunnercategories'] = 'Categories with pythonrunner questions';
$string['pythonrunnercontexts'] = 'Contexts with pythonrunner questions';
$string['pythonrunner'] = 'Program Code';

$string['pythonrunner_install_testsuite_title'] = 'A test suite for pythonrunner sample answers';
$string['pythonrunner_install_testsuite_title_desc'] = 'The <a href="{$a->link}">sample-answer-test script</a> verifies that the questions with sample answers are performing correctly.';
$string['pythonrunner_install_testsuite_intro'] = 'This page allows you to test that the pythonrunner questions with sample answers are functioning correctly.';
$string['pythonrunner_install_testsuite_failures'] = 'Tests that failed';
$string['pythonrunner_install_testsuite_noanswer'] = 'Questions without sample answers';

$string['pythonrunner:sandboxwsaccess'] = 'Allow access to the Jobe sandbox via web services';
$string['pythonrunner:viewhiddentestcases'] = 'See hidden testcases when reviewing questions';
$string['pythonrunner_help'] = 'In response to a question, which is a specification for a program fragment, function or whole program, the respondent enters source code in a specified computer language that satisfies the specification.';
$string['pythonrunner_link'] = 'question/type/pythonrunner';
$string['pythonrunner_question_type'] = 'pythonrunner question type: ';
$string['pythonrunnersettings'] = 'pythonrunner settings';
$string['pythonrunnersummary'] = 'Answer is program code that is executed in the context of a set of test cases to determine its correctness.';
$string['pythonrunnertype'] = 'Question type';
$string['pythonrunnertype_help'] = 'Select the programming language and question type. Once a type has been selected, details can be seen in the Question type details panel below.';
$string['pythonrunnerwssettings'] = 'Sandbox web-service settings';
$string['columncontrols'] = 'Result table';
$string['columncontrols_help'] = 'The checkboxes select which columns of the results table should be displayed to the student after submission';

$string['confirm_proceed'] = 'If you save this question with \'Customise\' unchecked, any customisations made will be lost. Proceed?';
$string['confirmreset'] = 'Discard all your work on this question and reset answer box to original preloaded value?';
$string['corruptuiparams'] = 'The UI parameters for this question or its prototype are broken. Proceed with caution.';
$string['cputime'] = 'TimeLimit (secs)';
$string['customisationcontrols'] = 'Customisation';
$string['customise'] = 'Customise';
$string['customisation'] = 'Customisation';

$string['default_penalty_regime'] = 'Default penalty regime';
$string['default_penalty_regime_desc'] = 'The default penalty regime to apply to new questions, consisting of a comma separated list of penalty percentages, optionally ending in ", ..." to signify an on-going arithmetic progression.';

$string['display'] = 'Display';
$string['downloadquizattempts'] = 'Download quiz attempts';
$string['downloadquizattemptshelp'] = 'Click the appropriate course and/or download button
        for the course and quiz you wish to download. Numbers in parentheses
        after courses are the number of quizzes in the course with at least
        one submission. The numbers in parentheses after the quiz name
        are the numbers of submissions.';
$string['editingpythonrunner'] = 'Editing a pythonrunner Question';
$string['empty_new_prototype_name'] = 'New question type name cannot be empty';
$string['emptypenaltyregime'] = 'Penalty regime must be defined (since version 3.1)';
$string['enable'] = 'Enable';
$string['enablecombinator'] = 'Enable combinator';
$string['enable_diff_check'] = 'Enable \'Show differences\' button';
$string['enable_diff_check_desc'] = 'Present students with a \'Show differences\' button if their answer is wrong and an exact-match validator is being used';
$string['enable_sandbox_desc'] = 'Permit use of the specified sandbox for running student submissions';
$string['equalitygrader'] = 'Exact match';
$string['pythonequalitygrader'] = 'Sql data match';
$string['error_loading_prototype'] = 'Error loading prototype. Network problems or server down, perhaps?';
$string['error_loading_ui_descr'] = 'Error loading UI description. Network problems or server down, perhaps?';
$string['erroroninit'] = '**** ERROR WHEN INITIALISING QUESTION ****<br>{$a->error}<br>';
$string['errorstring-ok'] = 'OK';
$string['errorstring-autherror'] = 'Unauthorised to use sandbox';
$string['errorstring-jobe400'] = 'Error from Jobe sandbox server: ';
$string['errorstring-overload'] = 'Job could not be run due to server overload. Perhaps try again shortly?';
$string['errorstring-pastenotfound'] = 'Requesting status of non-existent job';
$string['errorstring-wronglangid'] = 'Non-existent language requested';
$string['errorstring-accessdenied'] = 'Access to sandbox denied';
$string['errorstring-submissionlimitexceeded'] = 'Sandbox submission limit reached';
$string['errorstring-submissionfailed'] = 'Submission to sandbox failed';
$string['errorstring-unknown'] = 'Unexpected error while executing your code. The sandbox server may be down or overloaded. Perhaps try again shortly?';

$string['event_sandboxwebserviceexec'] = 'CR sandbox exec';
$string['event_sandboxwebserviceexec_desc'] = 'A job was executed via the pythonrunner sandbox web service.';

$string['expand'] = 'Expand';
$string['expandtitle'] = 'Show question categories';
$string['expected'] = 'Expected output';
$string['expectedcolhdr'] = 'Expected';
$string['expected_help'] = 'The expected output from the test. Seen by the template as {{TEST.expected}}.';
$string['exportthisquestion'] = 'Export this question';
$string['exportthisquestion_help'] = 'This will create a Moodle XML export file containing just this one question. One example of when this is useful if you think this question demonstrates a bug in pythonrunner that you would like to report to the developers.';
$string['extra'] = 'Extra template data';
$string['extra_help'] = 'A sometimes-useful extra text field for use by the template, accessed as {{TEST.extra}}';

$string['fail'] = 'Fail';
$string['fails'] = 'failures';
$string['failedhidden'] = 'Your code failed one or more hidden tests.';
$string['failedntests'] = 'Failed {$a->numerrors} test(s)';
$string['failedtesting'] = 'Failed testing.';
$string['feedback'] = 'Feedback';
$string['feedback_quiz'] = 'Set by quiz';
$string['feedback_show'] = 'Force show';
$string['feedback_hide'] = 'Force hide';
$string['feedback_help'] = 'Choose \'Set by quiz\' to allow the quiz\'s review options (specifically the
\'Specific feedback\' setting) to control display of the result table, \'Force show\' to show the result table regardless and \'Force hide\' to hide it regardless';

$string['giveup'] = 'Stop button';
$string['giveup_aftermaxmarks'] = 'Available once mark cannot be improved';
$string['giveup_always'] = 'Always available';
$string['giveup_help'] = 'If this option is enabled, students will see a button to stop interacting with the question, and instead display the general feedback.

The \'Stop and read final feedback\' can be shown from the start, or only once the student can no longer improve their mark, due to the penalty regime.';
$string['giveup_never'] = 'Never available';

$string['goodemptyprecheck'] = 'Passed';
$string['gotcolhdr'] = 'Got';
$string['grader'] = 'Grader';
$string['grading'] = 'Grading';

$string['hidden'] = 'Hidden';
$string['hidecheck'] = 'Hide check';
$string['hidedetails'] = 'Hide details';
$string['hidedifferences'] = 'Hide differences';
$string['HIDE'] = 'Hide';
$string['HIDE_IF_FAIL'] = 'Hide if fail';
$string['HIDE_IF_SUCCEED'] = 'Hide if succeed';
$string['hiderestiffail'] = 'Hide rest if fail';
$string['hoisttemplateparams'] = 'Hoist template parameters';
$string['howtogetmore'] = 'For more detailed information, save the question with \'Validate on save\' unchecked and test manually';
$string['htmlui_html_src_descr'] = "Sets the source for the HTML code. Must be either 'globalextra' or 'prototypeextra'.";
$string['htmlui_sync_interval_secs_descr'] = 'The time interval in seconds between calls to sync the UI contents back to the question answer. 0 for no such auto-syncing.';
$string['htmlui_enable_in_editor_descr'] = 'If true, use the UI to display the sample answer and answer preload within the question editing form, rather than the serialised version. Set this to false if using Twig in the HTML src field.';
$string['htmluiloadfail'] = 'The HTML UI plugin failed to initialise. Probably the JSON state string is invalid.';

$string['illegaluiparamname'] = 'The following are not valid parameters for the {$a->uiname} UI: ';
$string['iscombinatortemplate'] = 'Is combinator';
$string['ideone_user'] = 'Ideone server user';
$string['ideone_user_desc'] = 'The login name to use when connecting to the deprecated Ideone server (if the ideone sandbox is enabled)';
$string['ideone_pass'] = 'Ideone server password';
$string['ideone_pass_desc'] = 'The password to use when connecting to the deprecated Ideone server (if the ideone sandbox is enabled)';
$string['info_unavailable'] = 'Question type information is not available for customised questions.';
$string['illegalformat'] = 'Illegal format ({$a->format}) in columnformats';

$string['jobe_apikey'] = 'Jobe API-key';
$string['jobe_apikey_desc'] = 'The API key to be included in all REST requests to the Jobe server (if required). Max 40 chars. Leave blank to omit the API Key from requests';
$string['jobe_host'] = 'Jobe server';
$string['jobe_host_desc'] = 'The host name of the Jobe server plus the port number if other than port 80, e.g. jobe.somewhere.edu:4010. The URL for the Jobe request is obtained by default by prefixing this string with http:// and appending /jobe/index.php/restapi/<REST_METHOD>. You may either specify the https:// protocol in front of the host name (e.g. https://jobe.somewhere.edu) if the Jobe server is set behind a reverse proxy which act as an SSL termination. Multiple jobe servers, separated by a semicolon, are possible for handling higher loads: one is chosen at random.';
$string['jobe_host_ws'] = 'Jobe server to use for web services';
$string['jobe_host_ws_desc'] = 'The sandbox server web service will use whatever sandbox is configured for the specified
    language. This is virtually always a Jobe server, and the particular Jobe server to use is configured via the admin interface (above).
    However, for best web service security it is better to use an alternative
    Jobe server, set by this field. Multiple jobe servers, separated by a semicolon, are possible for handling higher loads: one is chosen at random. Leave blank to use the default. ';
$string['jobe_warning_html'] = "<p style='background-color:yellow'>Run using the University of Canterbury's Jobe server. This is for initial testing only. Please set up your own Jobe server as soon as possible. See <a href='https://github.com/trampgeek/moodle-qtype_pythonrunner/blob/master/Readme.md#sandbox-configuration' target='_blank'>here</a>.</p>";
$string['jobe_canterbury_html'] = "<p style='color:gray; font-style:italic; font-size:smaller'>Run on the University of Canterbury's Jobe server.</p>";

$string['language'] = 'Sandbox language';

$string['legacyuiparams'] = 'UI parameters can no longer be defined within the template parameters field. Please move the following to the UI parameters field instead: ';
$string['legacyuiparams2'] = 'UI parameters can no longer be defined within the template parameters field. Please move the following to the UI parameters field instead, removing the \'{$a->uiname}_\' prefix: ';
$string['mark'] = 'Mark';
$string['maxfilesize'] = 'Max allowed file size (bytes)';
$string['maxfilesize_help'] = 'Select the maximum file upload size (bytes). Allowing large file uploads with large classes can impact performance and and disk space on both Moodle and Jobe servers.';
$string['memorylimit'] = 'MemLimit (MB)';
$string['missinganswers'] = 'missing answers';
$string['missingorbadfraction'] = 'Bad or missing fraction in output from template grader. Output was: {$a->output}';
$string['missingoutput'] = 'You must supply the expected output from this test case.';
$string['missingprototype'] = 'This question was defined to be of type \'{$a->crtype}\' but the prototype does not exist, or is non-unique, or is unavailable in this context. You should Cancel and try to (re)install the prototype.
Proceed to edit only if you know what you are doing!';
$string['missingprototypes'] = 'Missing prototypes';
$string['missinguiparams'] = 'The following UI parameters are required but not defined: ';
$string['multipledefaults'] = 'At most one language can be selected as default';
$string['multipleprototypes'] = 'Multiple prototypes found for \'{$a->crtype}\'';
$string['mustrequirefewer'] = 'You cannot require more attachments than you allow.';

$string['nearequalitygrader'] = 'Nearly exact match';
$string['nodetailsavailable'] = 'Select a question type to see detailed help.';
$string['nouiparameters'] = 'The {$a->uiname} UI does not take parameters.';
$string['noqtype'] = 'No question type selected';
$string['morehidden'] = 'Some hidden test cases failed, too.';
$string['noerrorsallowed'] = 'Your code must pass all tests to earn any marks. Try again.';
$string['nonnumericmark'] = 'Non-numeric mark';
$string['nosampleanswer'] = 'No sample answer';
$string['negativeorzeromark'] = 'Mark must be greater than zero';

$string['options'] = 'Options';
$string['ordering'] = 'Ordering';
$string['overallresult'] = 'Overall result';
$string['overloadoninit'] = 'Sandbox server overload prevented question initialisation';

$string['passes'] = 'passes';
$string['penaltyregime'] = '(penalty regime: {$a} %)';
$string['penaltyregimelabel'] = 'Penalty regime:';

$string['pass'] = 'Pass';
$string['pluginname'] = 'Pythonrunner';
$string['pluginnameadding'] = 'Adding a Python question';
$string['pluginnameediting'] = 'Editing a Python question';
$string['pluginnamesummary'] = 'Python runner: runs student-submitted code in a sandbox';
$string['pluginname_help'] = 'Use the \'Question type\' combo box to select the
computer language and question type that will be used to run the student\'s submission.
Specify the problem that the student must write code for, then define
a set of tests to be run on the student\'s submission';
$string['pluginname_link'] = 'question/type/pythonrunner';
$string['precheck'] = 'Precheck';

$string['precheckingemptyset'] = 'Prechecking examples, but there aren\'t any!';
$string['privacy:metadata'] = 'The pythonrunner question type plugin does not store any personal data.';
$string['proceed_at_own_risk'] = 'Editing a built-in question prototype?! Proceed at your own risk!';

$string['prototype_error'] = '*** PROTOTYPE LOAD FAILURE. DON\'T SAVE THIS! ***';
$string['prototype_load_failure'] = 'Error loading prototype: ';
$string['prototypeQ'] = 'Is prototype?';

$string['questiontype'] = 'Question type';
$string['question_type_changed'] = 'Changing question type. Click OK to reload customisation fields, Cancel to retain your customised ones.';

$string['questiontype_required'] = 'You must select the type of question';
$string['qWrongBehaviour'] = 'Please use Adaptive Behaviour for all pythonrunner questions, or there can be massive performance hits. For example, all questions on a page will need to be regraded when the page is re-displayed.';

$string['replacedollarscount'] = 'This category contains {$a} pythonrunner questions.';
$string['replaceexpectedwithgot'] = 'Click on the &lt;&lt; button to replace the expected output of this testcase with actual output.';
$string['resultcolumns'] = 'Result columns';
$string['resultstring-norun'] = 'No run';
$string['resultstring-compilationerror'] = 'Compilation error';
$string['resultstring-runtimeerror'] = 'Run error';
$string['resultstring-timelimit'] = 'Time limit exceeded';
$string['resultstring-success'] = 'OK';
$string['resultstring-memorylimit'] = 'Memory limit exceeded';
$string['resultstring-illegalsyscall'] = 'Illegal function call';
$string['resultstring-internalerror'] = 'pythonrunner error (IE): please tell a tutor';
$string['resultstring-sandboxpending'] = 'pythonrunner error (PD): please tell a tutor';
$string['resultstring-sandboxpolicy'] = 'pythonrunner error (BP): please tell a tutor';
$string['resultstring-sandboxoverload'] = 'Sandbox server overload. Perhaps try again soon?';
$string['resultstring-outputlimit'] = 'Excessive output';
$string['resultstring-abnormaltermination'] = 'Abnormal termination';

$string['sandboxparams'] = 'Parameters';
$string['showdetails'] = 'Show details';
$string['showdifferences'] = 'Show differences';
$string['supportscripts'] = 'Support scripts';

$string['template'] = 'Template';
$string['template_changed'] = 'Per-test template changed - disable combinator? [\'Cancel\' leaves it enabled.]';
$string['templategrader'] = 'Template grader';
$string['templateparamsusingsandbox'] = 'Preprocessors other than Twig use
the sandbox server. If "Evaluate per student" is also set, then when a student
starts a quiz all such questions initiate
a sandbox run before the question can even be displayed. In a test or exam,
this can overload the sandbox server. Caveat emptor!';
$string['testalltitle'] = 'Test all questions in this context';
$string['testallincategory'] = 'Test all questions in this category';
$string['testcase'] = 'Test case {$a}';

$string['testcases'] = 'Test cases';
$string['sql_queries'] = 'SQL queries';
$string['testcode'] = 'Test code';
$string['testcolhdr'] = 'Test';
$string['testingquestion'] = 'Testing question {$a}';
$string['testsplitterre'] = 'Test splitter (regex)';
$string['testcode_help'] = 'The code for the test, seen by the template as {{TEST.testcode}}';
$string['testtype'] = 'Precheck test type';
$string['type_header'] = 'Python question';
$string['typename'] = 'Question type';
$string['typerequired'] = 'Please select the type of question (language, format, etc)';

$string['ui_fallback'] = 'Falling back to raw text area.';
$string['uiparametergroup_help'] = 'A JSON string defining any User Interface
parameter values that are either required by the UI plugin or which override the
default values. For example, to draw larger nodes when using the GraphUI: \'{"noderadius": 30}\'';

$string['video'] = 'Video';
$string['video_url'] = 'Video URL';

$string['wrongnumberofformats'] = 'Wrong number of test results column formats. Expected {$a->expected}, got {$a->got}';
$string['wsdisabled'] = 'Sandbox web service disabled. Talk to a sysadmin';
$string['wsloggingenable'] = 'Log sandbox web service usage';
$string['wsloggingenable_desc'] = 'If this option is checked, every code execution via the sandbox web service will be logged. This option must be enabled if user rate throttling is to work.';
$string['wsnoaccess'] = 'Only logged-in non-guest users can access this functionality';
$string['wsmaxcputime'] = 'Max CPU time (secs)';
$string['wsmaxcputime_desc'] = 'Limits the maximum CPU time that a web service job can use, even if it explicitly sets the CPU time sandbox parameter.';
$string['wsmaxhourlyrate'] = 'Max hourly rate of submissions';
$string['wsmaxhourlyrate_desc'] = 'If a user attempts to exceed this rate of submissions in any given hour their submissions will be disallowed. 0 for no rate throttling. Requires that logging of web service usage be enabled.';

$string['pythontype'] = 'Python Type';
$string['pythontype:python_other'] = 'Python Other';
$string['pythontype:python_algo'] = 'Python Algo';