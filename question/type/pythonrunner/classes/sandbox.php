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

/** The base class for the CodeRunner Sandbox classes.
 *  Sandboxes have an external name, which appears in the exported .xml question
 *  files for example, and a classname and a filename in which the class is
 *  defined.
 *  Error and result codes are based on those of the ideone
 *  API, which should be consulted for details:
 *  see ideone.com/files/ideone-api.pdf
 */

/**
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2012, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// TODO: provide a mechanism to check that a sandbox recognises all the
// non-null parameters it has been given (in particular the 'files' param).

defined('MOODLE_INTERNAL') || die();

global $CFG;

abstract class qtype_pythonrunner_sandbox extends qtype_sqlrunner_sandbox {
    use \qtype_pythonrunner\traits\python_sandbox_trait;
}