<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_sql\output;

use core_courseformat\base as course_format;

/**
 * Basic renderer for Flexsections format.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_flexsections_renderer extends \format_flexsections\output\renderer {

    /**
     * Get the course index drawer with placeholder.
     *
     * The default course index is loaded after the page is ready. Format plugins can override
     * this method to provide an alternative course index.
     *
     * If the format is not compatible with the course index, this method will return an empty string.
     *
     * @param course_format $format the course format
     * @return String the course index HTML.
     */
    public function course_index_drawer(course_format $format): ?String {
        if ($format->uses_course_index()){
            if (theme_sql_is_customised_cm($this->page->cm) && $this->page->cm->modname != 'quiz'){
                return '';
            }
            include_course_editor($format);
            return $this->render_from_template('format_flexsections/local/courseindex/drawer', []);
        }
        return '';
    }
}
