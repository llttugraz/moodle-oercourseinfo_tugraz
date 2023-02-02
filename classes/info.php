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

use coursesync_lectures\api;
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
            $course->subplugin         = 'tugraz';
            $infos[$info->coursecode]  = $course;
        }
    }

    /**
     * Extend the existing metadata with the semester and year the course has according to course mapping.
     *
     * Courses can have different semester/year settings than the global system definition is.
     * For best metadata quality the semester has to be read from the course.
     * If no semester could be calculated from the mapping settings the global definition will be used.
     * - Mapping is ignored in local_oer course metadata settings -> skip (when no candidate remains -> global)
     * - This course has no mapping -> global
     * - When mappings qualify -> the newest mapping of this course will be selected (across all defined mappings)
     *
     * @param int $courseid Moodle courseid
     * @return array
     * @throws \dml_exception
     */
    public static function add_metadata_fields(int $courseid): array {
        global $DB;
        $semester       = strtoupper(get_config('coursesync_lectures', 'current_semester')) . 'S';
        $year           = get_config('coursesync_lectures', 'current_year');
        $currentcourse  = new currentcourse($courseid);
        $module         = $currentcourse->get_submodule();
        $coursesemester = null;
        $courseyear     = null;
        switch ($currentcourse->get_type_of_course()) {
            case 'lectures':
                $mappings = $module->read_mapping();
                foreach ($mappings as $mapping) {
                    // Find out if this mapping is used by the course metadata.
                    // Or if it is ignored or deleted. Skip for calculation.
                    if (!$DB->record_exists('local_oer_courseinfo',
                                            ['courseid' => $courseid, 'coursecode' => $mapping->identifier,
                                             'ignored'  => 0,
                                             'deleted'  => 0])) {
                        continue;
                    }
                    // Take the first four characters of the information string, as they mark the newest semester of that mapping.
                    $newest = substr($mapping->information, 0, 4);
                    if (preg_match('/[W|S]S[0-9]{2}/', $newest)) {
                        $localyear = intval(substr($newest, 2, 2));
                        // The semester has to be WS or SS for all mappings, so it is easy to assign.
                        $coursesemester = substr($newest, 0, 2);
                        if ($localyear > $courseyear) {
                            $courseyear = $localyear;
                        }
                    }
                }
                $year     = $courseyear ? "20" . $courseyear : $year;
                $semester = $coursesemester ?? $semester;
                break;
            case 'courseid':
                $mappings = $module->read_mapping();
                foreach ($mappings as $mapping) {
                    // Find out if this mapping is used by the course metadata.
                    // Or if it is ignored or deleted. Skip for calculation.
                    if (!$DB->record_exists('local_oer_courseinfo',
                                            ['courseid' => $courseid, 'external_courseid' => $mapping->identifier,
                                             'ignored'  => 0,
                                             'deleted'  => 0])) {
                        continue;
                    }
                    $localsemester = substr($mapping->information, -6, 2);
                    $localyear     = substr($mapping->information, -4, 4);
                    // If the year is higher than the currently stored year - both has to be taken, year and semester.
                    // If the year is the same it also has to be tested if the semester is WS -> WS > SS in the same year.
                    if ($localyear > $courseyear) {
                        $courseyear     = $localyear;
                        $coursesemester = $localsemester;
                    } else if ($localyear == $courseyear && $localsemester == 'WS') {
                        $coursesemester = $localsemester;
                    }
                }
                $year     = $courseyear ?? $year;
                $semester = $coursesemester ?? $semester;
                break;
            default:
                // For the other module types no semester is used.
                // So the global setting will be the best fit for a time definition.
        }
        return [
                'semester' => $semester,
                'year'     => $year,
        ];
    }
}
