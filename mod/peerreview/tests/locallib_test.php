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
 * Unit tests for peerreview api class defined in mod/peerreview/locallib.php
 *
 * @package    mod_peerreview
 * @category   phpunit
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerreview;

use testable_peerreview;
use peerreview;
use peerreview_example_assessment;
use peerreview_example_reference_assessment;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/peerreview/locallib.php'); // Include the code to test
require_once(__DIR__ . '/fixtures/testable.php');


/**
 * Test cases for the internal peerreview api
 */
class locallib_test extends \advanced_testcase {

    /** @var object */
    protected $course;

    /** @var peerreview */
    protected $peerreview;

    /** setup testing environment */
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $this->course));
        $cm = get_coursemodule_from_instance('peerreview', $peerreview->id, $this->course->id, false, MUST_EXIST);
        $this->peerreview = new testable_peerreview($peerreview, $cm, $this->course);
    }

    protected function tearDown(): void {
        $this->peerreview = null;
        parent::tearDown();
    }

    public function test_aggregate_submission_grades_process_notgraded() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 12, 'submissiongrade' => null, 'weight' => 1, 'grade' => null);
        //$DB->expectNever('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_single() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 12, 'submissiongrade' => null, 'weight' => 1, 'grade' => 10.12345);
        $expected = 10.12345;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_null_doesnt_influence() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 12, 'submissiongrade' => null, 'weight' => 1, 'grade' => 45.54321);
        $batch[] = (object)array('submissionid' => 12, 'submissiongrade' => null, 'weight' => 1, 'grade' => null);
        $expected = 45.54321;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_weighted_single() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 12, 'submissiongrade' => null, 'weight' => 4, 'grade' => 14.00012);
        $expected = 14.00012;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_mean() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 56.12000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 12.59000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 10.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 0.00000);
        $expected = 19.67750;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_mean_changed() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 12.57750, 'weight' => 1, 'grade' => 56.12000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 12.57750, 'weight' => 1, 'grade' => 12.59000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 12.57750, 'weight' => 1, 'grade' => 10.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 12.57750, 'weight' => 1, 'grade' => 0.00000);
        $expected = 19.67750;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_mean_nochange() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 19.67750, 'weight' => 1, 'grade' => 56.12000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 19.67750, 'weight' => 1, 'grade' => 12.59000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 19.67750, 'weight' => 1, 'grade' => 10.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => 19.67750, 'weight' => 1, 'grade' => 0.00000);
        //$DB->expectNever('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_rounding() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 4.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 2.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 1.00000);
        $expected = 2.33333;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_submission_grades_process_weighted_mean() {
        $this->resetAfterTest(true);

        // fixture set-up
        $batch = array();   // batch of a submission's assessments
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 3, 'grade' => 12.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 2, 'grade' => 30.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 1, 'grade' => 10.00000);
        $batch[] = (object)array('submissionid' => 45, 'submissiongrade' => null, 'weight' => 0, 'grade' => 1000.00000);
        $expected = 17.66667;
        //$DB->expectOnce('update_record');
        // exercise SUT
        $this->peerreview->aggregate_submission_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_nograding() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>2, 'gradinggrade'=>null, 'gradinggradeover'=>null, 'aggregationid'=>null, 'aggregatedgrade'=>null);
        // expectation
        //$DB->expectNever('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_single_grade_new() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>3, 'gradinggrade'=>82.87670, 'gradinggradeover'=>null, 'aggregationid'=>null, 'aggregatedgrade'=>null);
        // expectation
        $now = time();
        $expected = new \stdClass();
        $expected->peerreviewid = $this->peerreview->id;
        $expected->userid = 3;
        $expected->gradinggrade = 82.87670;
        $expected->timegraded = $now;
        //$DB->expectOnce('insert_record', array('peerreview_aggregations', $expected));
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch, $now);
    }

    public function test_aggregate_grading_grades_process_single_grade_update() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>3, 'gradinggrade'=>90.00000, 'gradinggradeover'=>null, 'aggregationid'=>1, 'aggregatedgrade'=>82.87670);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_single_grade_uptodate() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>3, 'gradinggrade'=>90.00000, 'gradinggradeover'=>null, 'aggregationid'=>1, 'aggregatedgrade'=>90.00000);
        // expectation
        //$DB->expectNever('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_single_grade_overridden() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>4, 'gradinggrade'=>91.56700, 'gradinggradeover'=>82.32105, 'aggregationid'=>2, 'aggregatedgrade'=>91.56700);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_multiple_grades_new() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>99.45670, 'gradinggradeover'=>null, 'aggregationid'=>null, 'aggregatedgrade'=>null);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>87.34311, 'gradinggradeover'=>null, 'aggregationid'=>null, 'aggregatedgrade'=>null);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>51.12000, 'gradinggradeover'=>null, 'aggregationid'=>null, 'aggregatedgrade'=>null);
        // expectation
        $now = time();
        $expected = new \stdClass();
        $expected->peerreviewid = $this->peerreview->id;
        $expected->userid = 5;
        $expected->gradinggrade = 79.3066;
        $expected->timegraded = $now;
        //$DB->expectOnce('insert_record', array('peerreview_aggregations', $expected));
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch, $now);
    }

    public function test_aggregate_grading_grades_process_multiple_grades_update() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>56.23400, 'gradinggradeover'=>null, 'aggregationid'=>2, 'aggregatedgrade'=>79.30660);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>87.34311, 'gradinggradeover'=>null, 'aggregationid'=>2, 'aggregatedgrade'=>79.30660);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>51.12000, 'gradinggradeover'=>null, 'aggregationid'=>2, 'aggregatedgrade'=>79.30660);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_multiple_grades_overriden() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>56.23400, 'gradinggradeover'=>99.45670, 'aggregationid'=>2, 'aggregatedgrade'=>64.89904);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>87.34311, 'gradinggradeover'=>null, 'aggregationid'=>2, 'aggregatedgrade'=>64.89904);
        $batch[] = (object)array('reviewerid'=>5, 'gradinggrade'=>51.12000, 'gradinggradeover'=>null, 'aggregationid'=>2, 'aggregatedgrade'=>64.89904);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_multiple_grades_one_missing() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>50.00000, 'gradinggradeover'=>null, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>null, 'gradinggradeover'=>null, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>52.20000, 'gradinggradeover'=>null, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_aggregate_grading_grades_process_multiple_grades_missing_overridden() {
        $this->resetAfterTest(true);
        // fixture set-up
        $batch = array();
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>50.00000, 'gradinggradeover'=>null, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>null, 'gradinggradeover'=>69.00000, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        $batch[] = (object)array('reviewerid'=>6, 'gradinggrade'=>52.20000, 'gradinggradeover'=>null, 'aggregationid'=>3, 'aggregatedgrade'=>100.00000);
        // expectation
        //$DB->expectOnce('update_record');
        // excersise SUT
        $this->peerreview->aggregate_grading_grades_process($batch);
    }

    public function test_percent_to_value() {
        $this->resetAfterTest(true);
        // fixture setup
        $total = 185;
        $percent = 56.6543;
        // exercise SUT
        $part = peerreview::percent_to_value($percent, $total);
        // verify
        $this->assertEquals($part, $total * $percent / 100);
    }

    public function test_percent_to_value_negative() {
        $this->resetAfterTest(true);
        // fixture setup
        $total = 185;
        $percent = -7.098;

        // exercise SUT
        $this->expectException(\coding_exception::class);
        $part = peerreview::percent_to_value($percent, $total);
    }

    public function test_percent_to_value_over_hundred() {
        $this->resetAfterTest(true);
        // fixture setup
        $total = 185;
        $percent = 121.08;

        // exercise SUT
        $this->expectException(\coding_exception::class);
        $part = peerreview::percent_to_value($percent, $total);
    }

    public function test_lcm() {
        $this->resetAfterTest(true);
        // fixture setup + exercise SUT + verify in one step
        $this->assertEquals(peerreview::lcm(1,4), 4);
        $this->assertEquals(peerreview::lcm(2,4), 4);
        $this->assertEquals(peerreview::lcm(4,2), 4);
        $this->assertEquals(peerreview::lcm(2,3), 6);
        $this->assertEquals(peerreview::lcm(6,4), 12);
    }

    public function test_lcm_array() {
        $this->resetAfterTest(true);
        // fixture setup
        $numbers = array(5,3,15);
        // excersise SUT
        $lcm = array_reduce($numbers, 'peerreview::lcm', 1);
        // verify
        $this->assertEquals($lcm, 15);
    }

    public function test_prepare_example_assessment() {
        $this->resetAfterTest(true);
        // fixture setup
        $fakerawrecord = (object)array(
            'id'                => 42,
            'submissionid'      => 56,
            'weight'            => 0,
            'timecreated'       => time() - 10,
            'timemodified'      => time() - 5,
            'grade'             => null,
            'gradinggrade'      => null,
            'gradinggradeover'  => null,
            'feedbackauthor'    => null,
            'feedbackauthorformat' => 0,
            'feedbackauthorattachment' => 0,
        );
        // excersise SUT
        $a = $this->peerreview->prepare_example_assessment($fakerawrecord);
        // verify
        $this->assertTrue($a instanceof peerreview_example_assessment);
        $this->assertTrue($a->url instanceof \moodle_url);

        // modify setup
        $fakerawrecord->weight = 1;
        $this->expectException('coding_exception');
        // excersise SUT
        $a = $this->peerreview->prepare_example_assessment($fakerawrecord);
    }

    public function test_prepare_example_reference_assessment() {
        global $USER;
        $this->resetAfterTest(true);
        // fixture setup
        $fakerawrecord = (object)array(
            'id'                => 38,
            'submissionid'      => 56,
            'weight'            => 1,
            'timecreated'       => time() - 100,
            'timemodified'      => time() - 50,
            'grade'             => 0.75000,
            'gradinggrade'      => 1.00000,
            'gradinggradeover'  => null,
            'feedbackauthor'    => null,
            'feedbackauthorformat' => 0,
            'feedbackauthorattachment' => 0,
        );
        // excersise SUT
        $a = $this->peerreview->prepare_example_reference_assessment($fakerawrecord);
        // verify
        $this->assertTrue($a instanceof peerreview_example_reference_assessment);

        // modify setup
        $fakerawrecord->weight = 0;
        $this->expectException('coding_exception');
        // excersise SUT
        $a = $this->peerreview->prepare_example_reference_assessment($fakerawrecord);
    }

    /**
     * Tests user restrictions, as they affect lists of users returned by
     * core API functions.
     *
     * This includes the groupingid option (when group mode is in use), and
     * standard activity restrictions using the availability API.
     */
    public function test_user_restrictions() {
        global $DB, $CFG;

        $this->resetAfterTest();

        // Use existing sample course from setUp.
        $courseid = $this->peerreview->course->id;

        // Make a test grouping and two groups.
        $generator = $this->getDataGenerator();
        $grouping = $generator->create_grouping(array('courseid' => $courseid));
        $group1 = $generator->create_group(array('courseid' => $courseid));
        groups_assign_grouping($grouping->id, $group1->id);
        $group2 = $generator->create_group(array('courseid' => $courseid));
        groups_assign_grouping($grouping->id, $group2->id);

        // Group 3 is not in the grouping.
        $group3 = $generator->create_group(array('courseid' => $courseid));

        // Enrol some students.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $student3 = $generator->create_user();
        $generator->enrol_user($student1->id, $courseid, $roleids['student']);
        $generator->enrol_user($student2->id, $courseid, $roleids['student']);
        $generator->enrol_user($student3->id, $courseid, $roleids['student']);

        // Place students in groups (except student 3).
        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);
        groups_add_member($group3, $student3);

        // The existing peerreview doesn't have any restrictions, so user lists
        // should include all three users.
        $allusers = get_enrolled_users(\context_course::instance($courseid));
        $result = $this->peerreview->get_grouped($allusers);
        $this->assertCount(4, $result);
        $users = array_keys($result[0]);
        sort($users);
        $this->assertEquals(array($student1->id, $student2->id, $student3->id), $users);
        $this->assertEquals(array($student1->id), array_keys($result[$group1->id]));
        $this->assertEquals(array($student2->id), array_keys($result[$group2->id]));
        $this->assertEquals(array($student3->id), array_keys($result[$group3->id]));

        // Test get_users_with_capability_sql (via get_potential_authors).
        $users = $this->peerreview->get_potential_authors(false);
        $this->assertCount(3, $users);
        $users = $this->peerreview->get_potential_authors(false, $group2->id);
        $this->assertEquals(array($student2->id), array_keys($users));

        // Create another test peerreview with grouping set.
        $peerreviewitem = $this->getDataGenerator()->create_module('peerreview',
                array('course' => $courseid, 'groupmode' => SEPARATEGROUPS,
                'groupingid' => $grouping->id));
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewitem->id,
                $courseid, false, MUST_EXIST);
        $peerreviewgrouping = new testable_peerreview($peerreviewitem, $cm, $this->peerreview->course);

        // This time the result should only include users and groups in the
        // selected grouping.
        $result = $peerreviewgrouping->get_grouped($allusers);
        $this->assertCount(3, $result);
        $users = array_keys($result[0]);
        sort($users);
        $this->assertEquals(array($student1->id, $student2->id), $users);
        $this->assertEquals(array($student1->id), array_keys($result[$group1->id]));
        $this->assertEquals(array($student2->id), array_keys($result[$group2->id]));

        // Test get_users_with_capability_sql (via get_potential_authors).
        $users = $peerreviewgrouping->get_potential_authors(false);
        $userids = array_keys($users);
        sort($userids);
        $this->assertEquals(array($student1->id, $student2->id), $userids);
        $users = $peerreviewgrouping->get_potential_authors(false, $group2->id);
        $this->assertEquals(array($student2->id), array_keys($users));

        // Enable the availability system and create another test peerreview with
        // availability restriction on grouping.
        $CFG->enableavailability = true;
        $peerreviewitem = $this->getDataGenerator()->create_module('peerreview',
                array('course' => $courseid, 'availability' => json_encode(
                    \core_availability\tree::get_root_json(array(
                    \availability_grouping\condition::get_json($grouping->id)),
                    \core_availability\tree::OP_AND, false))));
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewitem->id,
                $courseid, false, MUST_EXIST);
        $peerreviewrestricted = new testable_peerreview($peerreviewitem, $cm, $this->peerreview->course);

        // The get_grouped function isn't intended to apply this restriction,
        // so it should be the same as the base peerreview. (Note: in reality,
        // get_grouped is always run with the parameter being the result of
        // one of the get_potential_xxx functions, so it works.)
        $result = $peerreviewrestricted->get_grouped($allusers);
        $this->assertCount(4, $result);
        $this->assertCount(3, $result[0]);

        // The get_users_with_capability_sql-based functions should apply it.
        $users = $peerreviewrestricted->get_potential_authors(false);
        $userids = array_keys($users);
        sort($userids);
        $this->assertEquals(array($student1->id, $student2->id), $userids);
        $users = $peerreviewrestricted->get_potential_authors(false, $group2->id);
        $this->assertEquals(array($student2->id), array_keys($users));
    }

    /**
     * Test the peerreview reset feature.
     */
    public function test_reset_phase() {
        $this->resetAfterTest(true);

        $this->peerreview->switch_phase(peerreview::PHASE_CLOSED);
        $this->assertEquals(peerreview::PHASE_CLOSED, $this->peerreview->phase);

        $settings = (object)array(
            'reset_peerreview_phase' => 0,
        );
        $status = $this->peerreview->reset_userdata($settings);
        $this->assertEquals(peerreview::PHASE_CLOSED, $this->peerreview->phase);

        $settings = (object)array(
            'reset_peerreview_phase' => 1,
        );
        $status = $this->peerreview->reset_userdata($settings);
        $this->assertEquals(peerreview::PHASE_SETUP, $this->peerreview->phase);
        foreach ($status as $result) {
            $this->assertFalse($result['error']);
        }
    }

    /**
     * Test deleting assessments related data on peerreview reset.
     */
    public function test_reset_userdata_assessments() {
        global $DB;
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $this->peerreview->course->id);

        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);
        $subid2 = $peerreviewgenerator->create_submission($this->peerreview->id, $student2->id);

        $asid1 = $peerreviewgenerator->create_assessment($subid1, $student2->id);
        $asid2 = $peerreviewgenerator->create_assessment($subid2, $student1->id);

        $settings = (object)array(
            'reset_peerreview_assessments' => 1,
        );
        $status = $this->peerreview->reset_userdata($settings);

        foreach ($status as $result) {
            $this->assertFalse($result['error']);
        }

        $this->assertEquals(2, $DB->count_records('peerreview_submissions', array('peerreviewid' => $this->peerreview->id)));
        $this->assertEquals(0, $DB->count_records('peerreview_assessments'));
    }

    /**
     * Test deleting submissions related data on peerreview reset.
     */
    public function test_reset_userdata_submissions() {
        global $DB;
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $this->peerreview->course->id);
        $this->getDataGenerator()->enrol_user($student2->id, $this->peerreview->course->id);

        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        $subid1 = $peerreviewgenerator->create_submission($this->peerreview->id, $student1->id);
        $subid2 = $peerreviewgenerator->create_submission($this->peerreview->id, $student2->id);

        $asid1 = $peerreviewgenerator->create_assessment($subid1, $student2->id);
        $asid2 = $peerreviewgenerator->create_assessment($subid2, $student1->id);

        $settings = (object)array(
            'reset_peerreview_submissions' => 1,
        );
        $status = $this->peerreview->reset_userdata($settings);

        foreach ($status as $result) {
            $this->assertFalse($result['error']);
        }

        $this->assertEquals(0, $DB->count_records('peerreview_submissions', array('peerreviewid' => $this->peerreview->id)));
        $this->assertEquals(0, $DB->count_records('peerreview_assessments'));
    }

    /**
     * Test normalizing list of extensions.
     */
    public function test_normalize_file_extensions() {
        $this->resetAfterTest(true);

        peerreview::normalize_file_extensions('');
        $this->assertDebuggingCalled();
    }

    /**
     * Test cleaning list of extensions.
     */
    public function test_clean_file_extensions() {
        $this->resetAfterTest(true);

        peerreview::clean_file_extensions('');
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * Test validation of the list of file extensions.
     */
    public function test_invalid_file_extensions() {
        $this->resetAfterTest(true);

        peerreview::invalid_file_extensions('', '');
        $this->assertDebuggingCalledCount(3);
    }

    /**
     * Test checking file name against the list of allowed extensions.
     */
    public function test_is_allowed_file_type() {
        $this->resetAfterTest(true);

        peerreview::is_allowed_file_type('', '');
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * Test peerreview::check_group_membership() functionality.
     */
    public function test_check_group_membership() {
        global $DB, $CFG;

        $this->resetAfterTest();

        $courseid = $this->course->id;
        $generator = $this->getDataGenerator();

        // Make test groups.
        $group1 = $generator->create_group(array('courseid' => $courseid));
        $group2 = $generator->create_group(array('courseid' => $courseid));
        $group3 = $generator->create_group(array('courseid' => $courseid));

        // Revoke the accessallgroups from non-editing teachers (tutors).
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        unassign_capability('moodle/site:accessallgroups', $roleids['teacher']);

        // Create test use accounts.
        $teacher1 = $generator->create_user();
        $tutor1 = $generator->create_user();
        $tutor2 = $generator->create_user();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $student3 = $generator->create_user();

        // Enrol the teacher (has the access all groups permission).
        $generator->enrol_user($teacher1->id, $courseid, $roleids['editingteacher']);

        // Enrol tutors (can not access all groups).
        $generator->enrol_user($tutor1->id, $courseid, $roleids['teacher']);
        $generator->enrol_user($tutor2->id, $courseid, $roleids['teacher']);

        // Enrol students.
        $generator->enrol_user($student1->id, $courseid, $roleids['student']);
        $generator->enrol_user($student2->id, $courseid, $roleids['student']);
        $generator->enrol_user($student3->id, $courseid, $roleids['student']);

        // Add users in groups.
        groups_add_member($group1, $tutor1);
        groups_add_member($group2, $tutor2);
        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);
        groups_add_member($group3, $student3);

        // peerreview with no groups.
        $peerreviewitem1 = $this->getDataGenerator()->create_module('peerreview', [
            'course' => $courseid,
            'groupmode' => NOGROUPS,
        ]);
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewitem1->id, $courseid, false, MUST_EXIST);
        $peerreview1 = new testable_peerreview($peerreviewitem1, $cm, $this->course);

        $this->setUser($teacher1);
        $this->assertTrue($peerreview1->check_group_membership($student1->id));
        $this->assertTrue($peerreview1->check_group_membership($student2->id));
        $this->assertTrue($peerreview1->check_group_membership($student3->id));

        $this->setUser($tutor1);
        $this->assertTrue($peerreview1->check_group_membership($student1->id));
        $this->assertTrue($peerreview1->check_group_membership($student2->id));
        $this->assertTrue($peerreview1->check_group_membership($student3->id));

        // peerreview in visible groups mode.
        $peerreviewitem2 = $this->getDataGenerator()->create_module('peerreview', [
            'course' => $courseid,
            'groupmode' => VISIBLEGROUPS,
        ]);
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewitem2->id, $courseid, false, MUST_EXIST);
        $peerreview2 = new testable_peerreview($peerreviewitem2, $cm, $this->course);

        $this->setUser($teacher1);
        $this->assertTrue($peerreview2->check_group_membership($student1->id));
        $this->assertTrue($peerreview2->check_group_membership($student2->id));
        $this->assertTrue($peerreview2->check_group_membership($student3->id));

        $this->setUser($tutor1);
        $this->assertTrue($peerreview2->check_group_membership($student1->id));
        $this->assertTrue($peerreview2->check_group_membership($student2->id));
        $this->assertTrue($peerreview2->check_group_membership($student3->id));

        // peerreview in separate groups mode.
        $peerreviewitem3 = $this->getDataGenerator()->create_module('peerreview', [
            'course' => $courseid,
            'groupmode' => SEPARATEGROUPS,
        ]);
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewitem3->id, $courseid, false, MUST_EXIST);
        $peerreview3 = new testable_peerreview($peerreviewitem3, $cm, $this->course);

        $this->setUser($teacher1);
        $this->assertTrue($peerreview3->check_group_membership($student1->id));
        $this->assertTrue($peerreview3->check_group_membership($student2->id));
        $this->assertTrue($peerreview3->check_group_membership($student3->id));

        $this->setUser($tutor1);
        $this->assertTrue($peerreview3->check_group_membership($student1->id));
        $this->assertFalse($peerreview3->check_group_membership($student2->id));
        $this->assertFalse($peerreview3->check_group_membership($student3->id));

        $this->setUser($tutor2);
        $this->assertFalse($peerreview3->check_group_membership($student1->id));
        $this->assertTrue($peerreview3->check_group_membership($student2->id));
        $this->assertFalse($peerreview3->check_group_membership($student3->id));
    }

    /**
     * Test init_initial_bar function.
     *
     * @covers \peerreview::init_initial_bar
     */
    public function test_init_initial_bar(): void {
        global $SESSION;
        $this->resetAfterTest();

        $_GET['ifirst'] = 'A';
        $_GET['ilast'] = 'B';
        $contextid = $this->peerreview->context->id;

        $this->peerreview->init_initial_bar();
        $initialbarprefs = $this->get_initial_bar_prefs_property();

        $this->assertEquals('A', $initialbarprefs['i_first']);
        $this->assertEquals('B', $initialbarprefs['i_last']);
        $this->assertEquals('A', $SESSION->mod_peerreview->initialbarprefs['id-' . $contextid]['i_first']);
        $this->assertEquals('B', $SESSION->mod_peerreview->initialbarprefs['id-' . $contextid]['i_last']);

        $_GET['ifirst'] = null;
        $_GET['ilast'] = null;
        $SESSION->mod_peerreview->initialbarprefs['id-' . $contextid]['i_first'] = 'D';
        $SESSION->mod_peerreview->initialbarprefs['id-' . $contextid]['i_last'] = 'E';

        $this->peerreview->init_initial_bar();
        $initialbarprefs = $this->get_initial_bar_prefs_property();

        $this->assertEquals('D', $initialbarprefs['i_first']);
        $this->assertEquals('E', $initialbarprefs['i_last']);
    }

    /**
     * Test empty init_initial_bar
     *
     * @covers \peerreview::init_initial_bar
     */
    public function test_init_initial_bar_empty(): void {
        $this->resetAfterTest();

        $this->peerreview->init_initial_bar();
        $initialbarprefs = $this->get_initial_bar_prefs_property();

        $this->assertEmpty($initialbarprefs);
    }

    /**
     * Test get_initial_first function
     *
     * @covers \peerreview::get_initial_first
     */
    public function test_get_initial_first(): void {
        $this->resetAfterTest();
        $this->peerreview->init_initial_bar();
        $this->assertEquals(null, $this->peerreview->get_initial_first());

        $_GET['ifirst'] = 'D';
        $this->peerreview->init_initial_bar();
        $this->assertEquals('D', $this->peerreview->get_initial_first());
    }

    /**
     * Test get_initial_last function
     *
     * @covers \peerreview::get_initial_last
     */
    public function test_get_initial_last(): void {
        $this->resetAfterTest();
        $this->peerreview->init_initial_bar();
        $this->assertEquals(null, $this->peerreview->get_initial_last());

        $_GET['ilast'] = 'D';
        $this->peerreview->init_initial_bar();
        $this->assertEquals('D', $this->peerreview->get_initial_last());
    }

    /**
     * Get the protected propertyinitialbarprefs from peerreview class.
     *
     * @coversNothing
     * @return array initialbarspref property. eg ['i_first' => 'A', 'i_last' => 'B']
     */
    private function get_initial_bar_prefs_property(): array {

        $reflector = new \ReflectionObject($this->peerreview);
        $initialbarprefsprop = $reflector->getProperty('initialbarprefs');
        $initialbarprefsprop->setAccessible(true);
        $initialbarprefs = $initialbarprefsprop->getValue($this->peerreview);

        return $initialbarprefs;
    }
}
