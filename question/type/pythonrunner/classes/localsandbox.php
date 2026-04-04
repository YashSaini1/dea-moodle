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

/** A LocalSandbox is a subclass of the base qtype_pythonrunner_sandbox class,
 *  representing a sandbox that runs on the local server, performing compilation locally,
 *  caching compiled files, and processing the entire submission in a single
 *  call, rather than queueing the task for asynchronous procesing or
 *  sending it to a remove web service.
 *  It is assumed that an instance of the local sandbox will be created for
 *  each question run, though possibly not for each testcase, and that each
 *  call to createSubmission will run to completion before returning. Those
 *  conditions ensure that only one submission is running at a time on a particular
 *  sandbox, which allows caching of question-related information in the sandbox
 *  itself during submission.
 */

/**
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2012, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/*******************************************************************
 *
 * LocalSandbox Class
 *
 ******************************************************************/

abstract class qtype_pythonrunner_localsandbox extends qtype_sqlrunner_localsandbox {

    /**
     * {@inheritDoc}
     */
    public function execute($sourcecode, $language, $input, $files=null, $params=null) {
        $savedcurrentdir = getcwd();
        $language = strtolower($language);
        if (!in_array($language, $this->get_languages()->languages)) {
            return (object) array('error' => self::WRONG_LANG_ID);  // Should be impossible.
        }
        if ($input !== '' && substr($input, -1) != "\n") {
            $input .= "\n";  // Force newline on the end if necessary.
        }
        // Record input data in $this.
        $this->input = $input;
        $this->language = $language;
        $this->params = $params;
        $this->files = $files;

        // If this is the first call, make a working directory.
        if (empty($this->workdir)) {
            $this->set_path();
            $this->make_directory();
        }

        $this->load_files();  // Do this on every call in case a test run corrupts the files.

        $error = self::OK; // Start by being optimistic.

        if (empty($this->source) || $this->source !== $sourcecode) {
            // Copy sourcecode and recompile if new run or new sourcecode.
            $this->source = $sourcecode;
            $this->save_source();
            $error = $this->compile();
            if ($error === self::OK && !empty($this->cmpinfo)) {
                $this->result = self::RESULT_COMPILATION_ERROR;
            }
        }

        if ($error === self::OK && empty($this->cmpinfo)) {
            $error = $this->run_in_sandbox();
        }

        chdir($savedcurrentdir);
        if ($error === self::OK) {
            return (object) array(
                'error'     => self::OK,
                'cmpinfo'   => $this->cmpinfo,
                'result'    => $this->result,
                'stderr'    => $this->stderr,
                'output'    => $this->output,
                'signal'    => $this->signal);
        } else {
            return (object) array('error' => $error);
        }
    }
}

