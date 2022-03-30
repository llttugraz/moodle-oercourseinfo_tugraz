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

/**
 * Graz University of Technology specific subplugin for Open Educational Resources Plugin.
 *
 * @package    oercourseinfo_tugraz
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oercourseinfo_tugraz;

use local_coursesync\currentcourse;
use local_oer\metadata\courseinfo;
use local_oer\metadata\external_metadata;
use local_tugrazonlinewebservice\client;

/**
 * Class info
 *
 * Implements the external_metadata interface of the base class.
 * Uses the webservices of the local_tugrazonlinewebservice plugin to load
 * course informations from TUGRAZonline.
 */
class info implements external_metadata {
    /**
     * Load the linked course metadata from TUGRAZonline.
     * A Moodle course at Graz University of Technology can have multiple course mappings of the external system.
     *
     * @param int   $courseid Moodle courseid
     * @param array $infos    Array of courseinformations created in local_oer plugin
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function load_data(int $courseid, array &$infos): void {
        global $DB;
        $currentcourse = new currentcourse($courseid);
        $module        = $currentcourse->get_submodule();
        switch ($currentcourse->get_type_of_course()) {
            case 'lectures':
                $courseids = $module->get_tugrazonline_ids_to_sync();
                break;
            case 'courseid':
                $identifiers = $DB->get_records('coursesync_courseid_mapping', ['courseid' => $courseid], '', 'identifier');
                $courseids   = array_keys($identifiers);
                break;
            default:
                return;
        }
        $tracking = [];
        foreach ($courseids as $tugrazonlinecourseid) {
            $info  = client::load('teachers', [$tugrazonlinecourseid]);
            $value = $info->semester == 's' ? $info->year : $info->year + 0.5;
            if (isset($tracking[$info->coursecode]) && $value < $tracking[$info->coursecode]) {
                // We already visited a newer semester of the same course -> skip it.
                continue;
            } else {
                $tracking[$info->coursecode] = $value;
            }
            $dbcourse = $DB->get_record('coursesync_lectures_courses', ['courseid' => $tugrazonlinecourseid], '*', MUST_EXIST);
            $org      = client::load('organisation', [$dbcourse->orgunitid]);
            $teach    = [];
            foreach ($info->teachers as $teacher) {
                if ($teacher->roleid == 'V') {
                    $teach[] = $teacher->firstname . ' ' . $teacher->lastname;
                }
            }
            $course                    = courseinfo::get_default_metadata_object($courseid);
            $course->external_courseid = $dbcourse->courseid;
            $course->external_sourceid = $dbcourse->sourceid ?? -1;
            $course->coursecode        = $info->coursecode;
            $course->coursename        = $info->name;
            $course->structure         = $info->teachingactivityname . ' (' . $info->teachingactivityid . ')';
            $course->description       = $info->description;
            $course->objectives        = $info->objectives;
            $course->organisation      = $org->name;
            $course->language          = $info->language;
            $course->lecturer          = implode(',', $teach);
            $infos[$info->coursecode]  = $course;
        }
    }

    /**
     * Extend the existing metadata with the semester and year the system is currently set to.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function add_metadata_fields(): array {
        return [
                'semester' => strtoupper(get_config('coursesync_lectures', 'current_semester')) . 'S',
                'year'     => get_config('coursesync_lectures', 'current_year'),
        ];
    }
}
