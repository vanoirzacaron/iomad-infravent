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

namespace mod_peerreview;

use testable_peerreview;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/peerreview/locallib.php');
require_once(__DIR__ . '/fixtures/testable.php');
require_once($CFG->dirroot . '/mod/peerreview/classes/portfolio_caller.php');

/**
 * Unit tests for mod_peerreview_portfolio_caller class
 *
 * @package    mod_peerreview
 * @copyright  2016 An Pham Van <an.phamvan@harveynash.vn>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_caller_test extends \advanced_testcase {

    /** @var \stdClass $peerreview Basic peerreview data stored in an object. */
    protected $peerreview;
    /** @var stdClass mod info */
    protected $cm;

    /**
     * Setup testing environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course]);
        $this->cm = get_coursemodule_from_instance('peerreview', $peerreview->id, $course->id, false, MUST_EXIST);
        $this->peerreview = new testable_peerreview($peerreview, $this->cm, $course);
    }

    /**
     * Tear down.
     */
    protected function tearDown(): void {
        $this->peerreview = null;
        $this->cm = null;
        parent::tearDown();
    }

    /**
     * Test the method mod_peerreview_portfolio_caller::load_data()
     */
    public function test_load_data() {
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $this->peerreview->course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);
        $asid1 = $peerreviewgenerator->create_assessment($subid1, $student2->id);

        $portfoliocaller = new \mod_peerreview_portfolio_caller(['id' => $this->peerreview->cm->id, 'submissionid' => $subid1]);
        $portfoliocaller->set_formats_from_button([]);
        $portfoliocaller->load_data();

        $reflector = new \ReflectionObject($portfoliocaller);
        $propertysubmission = $reflector->getProperty('submission');
        $propertysubmission->setAccessible(true);
        $submission = $propertysubmission->getValue($portfoliocaller);

        $this->assertEquals($subid1, $submission->id);
    }

    /**
     * Test the method mod_peerreview_portfolio_caller::get_return_url()
     */
    public function test_get_return_url() {
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);

        $portfoliocaller = new \mod_peerreview_portfolio_caller(['id' => $this->peerreview->cm->id, 'submissionid' => $subid1]);
        $portfoliocaller->set_formats_from_button([]);
        $portfoliocaller->load_data();

        $expected = new \moodle_url('/mod/peerreview/submission.php', ['cmid' => $this->peerreview->cm->id, 'id' => $subid1]);
        $actual = new \moodle_url($portfoliocaller->get_return_url());
        $this->assertTrue($expected->compare($actual));
    }

    /**
     * Test the method mod_peerreview_portfolio_caller::get_navigation()
     */
    public function test_get_navigation() {
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);

        $portfoliocaller = new \mod_peerreview_portfolio_caller(['id' => $this->peerreview->cm->id, 'submissionid' => $subid1]);
        $portfoliocaller->set_formats_from_button([]);
        $portfoliocaller->load_data();

        $this->assertTrue(is_array($portfoliocaller->get_navigation()));
    }

    /**
     * Test the method mod_peerreview_portfolio_caller::check_permissions()
     */
    public function test_check_permissions_exportownsubmissionassessment() {
        global $DB;
        $this->resetAfterTest(true);

        $context = \context_module::instance($this->cm->id);
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id, $roleids['student']);
        $this->getDataGenerator()->enrol_user($student2->id, $this->peerreview->course->id, $roleids['student']);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);
        $asid1 = $peerreviewgenerator->create_assessment($subid1, $student2->id);
        $this->setUser($student1);

        $portfoliocaller = new \mod_peerreview_portfolio_caller(['id' => $this->peerreview->cm->id, 'submissionid' => $subid1]);

        role_change_permission($roleids['student'], $context, 'mod/peerreview:exportsubmissions', CAP_PREVENT);
        $this->assertFalse($portfoliocaller->check_permissions());

        role_change_permission($roleids['student'], $context, 'mod/peerreview:exportsubmissions', CAP_ALLOW);
        $this->assertTrue($portfoliocaller->check_permissions());
    }

    /**
     * Test the method mod_peerreview_portfolio_caller::get_sha1()
     */
    public function test_get_sha1() {
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $this->peerreview->course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);
        $asid1 = $peerreviewgenerator->create_assessment($subid1, $student2->id);

        $portfoliocaller = new \mod_peerreview_portfolio_caller(['id' => $this->peerreview->cm->id, 'submissionid' => $subid1]);
        $portfoliocaller->set_formats_from_button([]);
        $portfoliocaller->load_data();

        $this->assertTrue(is_string($portfoliocaller->get_sha1()));
    }

    /**
     * Test function display_name()
     * Assert that this function can return the name of the module ('peerreview').
     */
    public function test_display_name() {
        $this->resetAfterTest(true);

        $name = \mod_peerreview_portfolio_caller::display_name();
        $this->assertEquals(get_string('pluginname', 'mod_peerreview'), $name);
    }
}
