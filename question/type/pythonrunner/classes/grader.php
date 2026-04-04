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
/** The base class for the pythonrunner Grader classes.
 *  A Grader is called after running all testcases in a sandbox.
 *  Graders have an external name, which appears in the exported .xml question
 *  files for example, and a classname and a filename in which the class is
 *  defined.
 *  to confirm the correctness of the results.
 *  In the simplest subclass, qtype_pythonrunner_equality_grader, a test result is correct if
 *  the actual and expected outputs are identical after trailing white space
 *  has been removed. More complicated subclasses can, for example, do
 *  things like regular expression testing.
 */

/**
 * @package    qtype
 * @subpackage pythonrunner
 * @copyright  Richard Lobb, 2012, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

abstract class qtype_pythonrunner_grader extends qtype_sqlrunner_grader {

    use \qtype_pythonrunner\traits\python_grader_trait;
}
