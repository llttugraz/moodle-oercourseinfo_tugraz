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
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace oercourseinfo_tugraz;

use coursesync_lectures\course_syncer;
use coursesync_lectures\module;
use local_coursesync\currentcourse;
use local_oer\forms\fileinfo_form;
use local_oer\fromform;
use local_oer\testcourse;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/helper/testcourse.php');
require_once(__DIR__ . '/../../../tests/helper/fromform.php');

/**
 * Class info_test
 *
 * @coversDefaultClass \oercourseinfo_tugraz\info
 */
final class info_test extends \advanced_testcase {
    /**
     * The testcourse created in setup.
     *
     * @var array
     */
    private $course = null;

    /**
     * Set up a testing environment.
     *
     * Since this plugin loads data from TUGRAZonline to add to the OER metadata, it has some dependencies to other Moodle plugins
     * used at Graz University of Technology. The test environment is set up to use classes and methods from these additional
     * plugins. These plugins are not shared, so Travis does not have access to them and the tests will fail on Github. Moodle
     * cannot be installed in the Travis test environment because of the dependencies of this plugin.
     *
     * @return void
     * @throws \dml_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        // Set up local_coursesync, coursesync_lectures and local_tugrazonlinewebservice to have useful metadata.
        set_config('current_semester', 'w', 'coursesync_lectures');
        set_config('current_year', 2023, 'coursesync_lectures');
        set_config('metadataaggregator', 'tugraz', 'local_oer');
        // Create mapping for course.
        $syncer = new course_syncer();
        // This uses the testfile courses_2023_w_0.json, with a link to teachers_132456_0.json and organisation_123_0.json.
        $syncer->sync_courses(2023, 'w');
        // This test will use already defined testdata from local_tugrazonlinewebservice.
        // If the test fails please check if someone edited the testdata.
        // This should not happen, as other plugins need those testfiles too.
        $helper = new testcourse();
        $course = $helper->generate_testcourse($this->getDataGenerator());
        $module = new module($course->id, 'lectures');
        $module->create_mapping('MAT123');
        $module->store_default_settings();
        $fromform = new \stdClass();
        $fromform->submodule = $module->get_submodule_type();
        $fromform->syncenabled = 1;
        currentcourse::set_course_type($course->id, $fromform);
        $this->course = $course;
    }

    /**
     * Test load_data method.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @covers ::load_data
     */
    public function test_load_data(): void {
        $this->resetAfterTest();
        $info = new info();
        $result = [];
        $info->load_data($this->course->id, $result);
        $this->assertCount(1, $result);
        $courseinfo = reset($result);
        $this->assertEquals('132456', $courseinfo->external_courseid);
        $this->assertEquals('-1', $courseinfo->external_sourceid);
        $this->assertEquals('MAT123', $courseinfo->coursecode);
        $this->assertEquals('Testcase for phpunit loader', $courseinfo->coursename);
        $this->assertEquals('Seminar (SE)', $courseinfo->structure);
        $this->assertEquals('Load this data into unit test', $courseinfo->description);
        $this->assertEquals('Load data  and verify it', $courseinfo->objectives);
        $this->assertEquals('de', $courseinfo->language);
        $this->assertEquals('Test Teacher', $courseinfo->lecturer);
        $this->assertEquals('tugraz', $courseinfo->subplugin);
    }

    /**
     * Test add_metadata_fields method.
     *
     * @return void
     * @throws \dml_exception
     * @covers ::add_metadata_fields
     */
    public function test_add_metadata_fields(): void {
        $this->resetAfterTest();

        $result = info::add_metadata_fields($this->course->id);
        $this->assertCount(2, $result);
        $this->assertEquals('WS', $result['semester']);
        $this->assertEquals(2023, $result['year']);

        $helper = new testcourse();
        $helper->sync_course_info($this->course->id);

        $result = info::add_metadata_fields($this->course->id);
        $this->assertCount(2, $result);
        $this->assertEquals('WS', $result['semester']);
        $this->assertEquals(2023, $result['year']);
    }
}
