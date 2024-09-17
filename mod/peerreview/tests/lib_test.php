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
 * Unit tests for mod/peerreview/lib.php.
 *
 * @package    mod_peerreview
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerreview;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/peerreview/lib.php');

/**
 * Unit tests for mod/peerreview/lib.php.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Test calendar event provide action open.
     */
    public function test_peerreview_core_calendar_provide_event_action_open() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => $now - DAYSECS, 'submissionend' => $now + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event provide action open for a non user.
     */
    public function test_peerreview_core_calendar_provide_event_action_open_for_non_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => $now - DAYSECS, 'submissionend' => $now + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event provide action open when user id is provided.
     */
    public function test_peerreview_core_calendar_provide_event_action_open_for_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => $now - DAYSECS, 'submissionend' => $now + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory, $student->id);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event provide action closed.
     */
    public function test_peerreview_core_calendar_provide_event_action_closed() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course->id,
            'submissionend' => time() - DAYSECS));
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event provide action closed for a non user.
     */
    public function test_peerreview_core_calendar_provide_event_action_closed_for_non_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course->id,
            'submissionend' => time() - DAYSECS));
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event provide action closed when user id is provided.
     */
    public function test_peerreview_core_calendar_provide_event_action_closed_for_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course->id,
            'submissionend' => time() - DAYSECS));
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory, $student->id);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event action open in future.
     */
    public function test_peerreview_core_calendar_provide_event_action_open_in_future() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => time() + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event action open in future for a non user.
     */
    public function test_peerreview_core_calendar_provide_event_action_open_in_future_for_non_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => time() + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test calendar event action open in future when user id is provided.
     */
    public function test_peerreview_core_calendar_provide_event_action_open_in_future_for_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id,
            'submissionstart' => time() + DAYSECS]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory, $student->id);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event with no time specified.
     */
    public function test_peerreview_core_calendar_provide_event_action_no_time_specified() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('viewpeerreviewsummary', 'peerreview'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test calendar event with no time specified for a non user.
     */
    public function test_peerreview_core_calendar_provide_event_action_no_time_specified_for_non_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', ['course' => $course->id]);
        $event = $this->create_action_event($course->id, $peerreview->id, peerreview_EVENT_TYPE_SUBMISSION_OPEN);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        $factory = new \core_calendar\action_factory();
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    public function test_peerreview_core_calendar_provide_event_action_already_completed() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('peerreview', $peerreview->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerreview->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_peerreview_core_calendar_provide_event_action_already_completed_for_user() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Get some additional data.
        $cm = get_coursemodule_from_instance('peerreview', $peerreview->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerreview->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed for the student.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_peerreview_core_calendar_provide_event_action($event, $factory, $student->id);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The peerreview id.
     * @param string $eventtype The event type. eg. peerreview_EVENT_TYPE_OPEN.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'peerreview';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return \calendar_event::create($event);
    }

    /**
     * Test check_updates_since callback.
     */
    public function test_check_updates_since() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        // Create user.
        $student = self::getDataGenerator()->create_user();
        $teacher = self::getDataGenerator()->create_user();

        // User enrolment.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id, 'manual');
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id, 'manual');

        $this->setCurrentTimeStart();
        $record = array(
            'course' => $course->id,
            'custom' => 0,
            'feedback' => 1,
        );
        $peerreview = $this->getDataGenerator()->create_module('peerreview', $record);
        $cm = get_coursemodule_from_instance('peerreview', $peerreview->id, $course->id);
        $context = \context_module::instance($cm->id);
        $cm = \cm_info::create($cm);

        $this->setUser($student);
        // Check that upon creation, the updates are only about the new configuration created.
        $onehourago = time() - HOURSECS;
        $updates = peerreview_check_updates_since($cm, $onehourago);
        foreach ($updates as $el => $val) {
            if ($el == 'configuration') {
                $this->assertTrue($val->updated);
                $this->assertTimeCurrent($val->timeupdated);
            } else {
                $this->assertFalse($val->updated);
            }
        }

        // Set up a generator to create content.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        // Submission.
        $submissionid = $generator->create_submission($peerreview->id, $student->id, array(
            'title' => 'My custom title',
        ));
        // Now assessment.
        $assessmentid = $generator->create_assessment($submissionid, $student->id, array(
            'weight' => 3,
            'grade' => 95.00000,
        ));
        // Add files to one editor file area.
        $fs = get_file_storage();
        $filerecordinline = array(
            'contextid' => $context->id,
            'component' => 'mod_peerreview',
            'filearea'  => 'instructauthors',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'image.png',
        );
        $instructauthorsfile = $fs->create_file_from_string($filerecordinline, 'image contents (not really)');

        $updates = peerreview_check_updates_since($cm, $onehourago);
        $this->assertTrue($updates->submissions->updated);
        $this->assertCount(1, $updates->submissions->itemids);
        $this->assertEquals($submissionid, $updates->submissions->itemids[0]);
        $this->assertTrue($updates->assessments->updated);
        $this->assertCount(1, $updates->assessments->itemids);
        $this->assertEquals($assessmentid, $updates->assessments->itemids[0]);
        $this->assertTrue($updates->instructauthorsfiles->updated);
        $this->assertCount(1, $updates->instructauthorsfiles->itemids);
        $this->assertEquals($instructauthorsfile->get_id(), $updates->instructauthorsfiles->itemids[0]);

        // Check I see the user updates as teacher.
        $this->setUser($teacher);
        $updates = peerreview_check_updates_since($cm, $onehourago);
        $this->assertTrue($updates->usersubmissions->updated);
        $this->assertCount(1, $updates->usersubmissions->itemids);
        $this->assertEquals($submissionid, $updates->usersubmissions->itemids[0]);
        $this->assertTrue($updates->userassessments->updated);
        $this->assertCount(1, $updates->userassessments->itemids);
        $this->assertEquals($assessmentid, $updates->userassessments->itemids[0]);
        $this->assertTrue($updates->instructauthorsfiles->updated);
        $this->assertCount(1, $updates->instructauthorsfiles->itemids);
        $this->assertEquals($instructauthorsfile->get_id(), $updates->instructauthorsfiles->itemids[0]);

        // The teacher didn't do anything.
        $this->assertFalse($updates->submissions->updated);
        $this->assertFalse($updates->assessments->updated);
    }

    /**
     * An unknown event type should not have any limits
     */
    public function test_mod_peerreview_core_calendar_get_valid_event_timestart_range_unknown_event() {
        global $CFG;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $timestart = time();
        $timeend = $timestart + DAYSECS;
        $peerreview = new \stdClass();
        $peerreview->submissionstart = $timestart;
        $peerreview->submissionend = $timeend;
        $peerreview->assessmentstart = 0;
        $peerreview->assessmentend = 0;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'peerreview',
            'instance' => 1,
            'eventtype' => peerreview_EVENT_TYPE_SUBMISSION_CLOSE . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);
        list ($min, $max) = mod_peerreview_core_calendar_get_valid_event_timestart_range($event, $peerreview);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * Provider for test_mod_peerreview_core_calendar_get_valid_event_timestart_range.
     *
     * @return array of (submissionstart, submissionend, assessmentstart, assessmentend, eventtype, expectedmin, expectedmax)
     */
    public function mod_peerreview_core_calendar_get_valid_event_timestart_range_due_no_limit_provider() {
        $submissionstart = time() + DAYSECS;
        $submissionend = $submissionstart + DAYSECS;
        $assessmentstart = $submissionend + DAYSECS;
        $assessmentend = $assessmentstart + DAYSECS;

        return [
            'Only with submissionstart' => [$submissionstart, 0, 0, 0, peerreview_EVENT_TYPE_SUBMISSION_OPEN, null, null],
            'Only with submissionend' => [0, $submissionend, 0, 0, peerreview_EVENT_TYPE_SUBMISSION_CLOSE, null, null],
            'Only with assessmentstart' => [0, 0, $assessmentstart, 0, peerreview_EVENT_TYPE_ASSESSMENT_OPEN, null, null],
            'Only with assessmentend' => [0, 0, 0, $assessmentend, peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, null, null],

            'Move submissionstart when with submissionend' => [$submissionstart, $submissionend, 0, 0,
                    peerreview_EVENT_TYPE_SUBMISSION_OPEN, null, $submissionend - 1],
            'Move submissionend when with submissionstart' => [$submissionstart, $submissionend, 0, 0,
                    peerreview_EVENT_TYPE_SUBMISSION_CLOSE, $submissionstart + 1, null],
            'Move assessmentstart when with assessmentend' => [0, 0, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_OPEN, null, $assessmentend - 1],
            'Move assessmentend when with assessmentstart' => [0, 0, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, $assessmentstart + 1, null],

            'Move submissionstart when with assessmentstart' => [$submissionstart, 0, $assessmentstart, 0,
                    peerreview_EVENT_TYPE_SUBMISSION_OPEN, null, $assessmentstart],
            'Move submissionstart when with assessmentend' => [$submissionstart, 0, 0, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_OPEN, null, $assessmentend],
            'Move submissionend when with assessmentstart' => [0, $submissionend, $assessmentstart, 0,
                    peerreview_EVENT_TYPE_SUBMISSION_CLOSE, null, $assessmentstart],
            'Move submissionend when with assessmentend' => [0, $submissionend, 0, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_CLOSE, null, $assessmentend],

            'Move assessmentstart when with submissionstart' => [$submissionstart, 0, $assessmentstart, 0,
                    peerreview_EVENT_TYPE_ASSESSMENT_OPEN, $submissionstart, null],
            'Move assessmentstart when with submissionend' => [0, $submissionend, $assessmentstart, 0,
                    peerreview_EVENT_TYPE_ASSESSMENT_OPEN, $submissionend, null],
            'Move assessmentend when with submissionstart' => [$submissionstart, 0, 0, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, $submissionstart, null],
            'Move assessmentend when with submissionend' => [0, $submissionend, 0, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, $submissionend, null],

            'Move submissionstart when with others' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_OPEN, null, $submissionend - 1],
            'Move submissionend when with others' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_CLOSE, $submissionstart + 1, $assessmentstart],
            'Move assessmentstart when with others' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_OPEN, $submissionend, $assessmentend - 1],
            'Move assessmentend when with others' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, $assessmentstart + 1, null],
        ];
    }

    /**
     * Tests mod_peerreview_core_calendar_get_valid_event_timestart_range in various settings.
     *
     * @dataProvider mod_peerreview_core_calendar_get_valid_event_timestart_range_due_no_limit_provider
     *
     * @param int $submissionstart  The start of the submission phase
     * @param int $submissionend    The end of the submission phase
     * @param int $assessmentstart  The start of the assessment phase
     * @param int $assessmentend    The end of the assessment phase
     * @param string $eventtype     The type if the event
     * @param int|null $expectedmin The expected value for min of the valid event range
     * @param int|null $expectedmax The expected value for max of the valid event range
     */
    public function test_mod_peerreview_core_calendar_get_valid_event_timestart_range($submissionstart, $submissionend,
            $assessmentstart, $assessmentend, $eventtype, $expectedmin, $expectedmax) {

        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = new \stdClass();
        $peerreview->submissionstart = $submissionstart;
        $peerreview->submissionend = $submissionend;
        $peerreview->assessmentstart = $assessmentstart;
        $peerreview->assessmentend = $assessmentend;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'peerreview',
            'instance' => 1,
            'eventtype' => $eventtype,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);
        list($min, $max) = mod_peerreview_core_calendar_get_valid_event_timestart_range($event, $peerreview);

        $this->assertSame($expectedmin, is_array($min) ? $min[0] : $min);
        $this->assertSame($expectedmax, is_array($max) ? $max[0] : $max);
    }

    /**
     * An unknown event type should not change the peerreview instance.
     */
    public function test_mod_peerreview_core_calendar_event_timestart_updated_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $peerreviewgenerator = $generator->get_plugin_generator('mod_peerreview');
        $submissionstart = time() + DAYSECS;
        $submissionend = $submissionstart + DAYSECS;
        $assessmentstart = $submissionend + DAYSECS;
        $assessmentend = $assessmentstart + DAYSECS;
        $peerreview = $peerreviewgenerator->create_instance(['course' => $course->id]);
        $peerreview->submissionstart = $submissionstart;
        $peerreview->submissionend = $submissionend;
        $peerreview->assessmentstart = $assessmentstart;
        $peerreview->assessmentend = $assessmentend;
        $DB->update_record('peerreview', $peerreview);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'peerreview',
            'instance' => $peerreview->id,
            'eventtype' => peerreview_EVENT_TYPE_SUBMISSION_CLOSE . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_peerreview_core_calendar_event_timestart_updated($event, $peerreview);

        $peerreview = $DB->get_record('peerreview', ['id' => $peerreview->id]);
        $this->assertEquals($submissionstart, $peerreview->submissionstart);
        $this->assertEquals($submissionend, $peerreview->submissionend);
        $this->assertEquals($assessmentstart, $peerreview->assessmentstart);
        $this->assertEquals($assessmentend, $peerreview->assessmentend);
    }

    /**
     * Provider for test_mod_peerreview_core_calendar_event_timestart_updated.
     *
     * @return array of (submissionstart, submissionend, assessmentstart, assessmentend, eventtype, fieldtoupdate, newtime)
     */
    public function mod_peerreview_core_calendar_event_timestart_updated_provider() {
        $submissionstart = time() + DAYSECS;
        $submissionend = $submissionstart + DAYSECS;
        $assessmentstart = $submissionend + DAYSECS;
        $assessmentend = $assessmentstart + DAYSECS;

        return [
            'Move submissionstart' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_OPEN, 'submissionstart', $submissionstart + 50],
            'Move submissionend' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_SUBMISSION_CLOSE, 'submissionend', $submissionend + 50],
            'Move assessmentstart' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_OPEN, 'assessmentstart', $assessmentstart + 50],
            'Move assessmentend' => [$submissionstart, $submissionend, $assessmentstart, $assessmentend,
                    peerreview_EVENT_TYPE_ASSESSMENT_CLOSE, 'assessmentend', $assessmentend + 50],
        ];
    }

    /**
     * Due date events should update the peerreview due date.
     *
     * @dataProvider mod_peerreview_core_calendar_event_timestart_updated_provider
     *
     * @param int $submissionstart  The start of the submission phase
     * @param int $submissionend    The end of the submission phase
     * @param int $assessmentstart  The start of the assessment phase
     * @param int $assessmentend    The end of the assessment phase
     * @param string $eventtype     The type if the event
     * @param string $fieldtoupdate The field that is supposed to be updated.
     *                              Either of 'submissionstart', 'submissionend', 'assessmentstart' or 'assessmentend'.
     * @param int $newtime          The new value for the $fieldtoupdate
     */
    public function test_mod_peerreview_core_calendar_event_timestart_updated($submissionstart, $submissionend, $assessmentstart,
            $assessmentend, $eventtype, $fieldtoupdate, $newtime) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $peerreviewgenerator = $generator->get_plugin_generator('mod_peerreview');
        $peerreview = $peerreviewgenerator->create_instance(['course' => $course->id]);
        $peerreview->submissionstart = $submissionstart;
        $peerreview->submissionend = $submissionend;
        $peerreview->assessmentstart = $assessmentstart;
        $peerreview->assessmentend = $assessmentend;
        $DB->update_record('peerreview', $peerreview);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'peerreview',
            'instance' => $peerreview->id,
            'eventtype' => $eventtype,
            'timestart' => $newtime,
            'timeduration' => 86400,
            'visible' => 1
        ]);
        mod_peerreview_core_calendar_event_timestart_updated($event, $peerreview);

        $$fieldtoupdate = $newtime;

        $peerreview = $DB->get_record('peerreview', ['id' => $peerreview->id]);
        $this->assertEquals($submissionstart, $peerreview->submissionstart);
        $this->assertEquals($submissionend, $peerreview->submissionend);
        $this->assertEquals($assessmentstart, $peerreview->assessmentstart);
        $this->assertEquals($assessmentend, $peerreview->assessmentend);
    }
}
