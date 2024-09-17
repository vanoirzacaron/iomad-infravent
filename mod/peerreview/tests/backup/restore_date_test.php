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

namespace mod_peerreview\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/peerreview/locallib.php');
require_once($CFG->dirroot . '/mod/peerreview/lib.php');
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");
require_once($CFG->dirroot . "/mod/peerreview/tests/fixtures/testable.php");

/**
 * Restore date tests.
 *
 * @package    mod_peerreview
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_date_test extends \restore_date_testcase {

    /**
     * Test restore dates.
     */
    public function test_restore_dates() {
        global $DB, $USER;

        // Create peerreview data.
        $record = ['submissionstart' => 100, 'submissionend' => 100, 'assessmentend' => 100, 'assessmentstart' => 100];
        list($course, $peerreview) = $this->create_course_and_module('peerreview', $record);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');
        $subid = $peerreviewgenerator->create_submission($peerreview->id, $USER->id);
        $exsubid = $peerreviewgenerator->create_submission($peerreview->id, $USER->id, ['example' => 1]);
        $peerreviewgenerator->create_assessment($subid, $USER->id);
        $peerreviewgenerator->create_assessment($exsubid, $USER->id, ['weight' => 0]);
        $peerreviewgenerator->create_assessment($exsubid, $USER->id);

        // Set time fields to a constant for easy validation.
        $timestamp = 100;
        $DB->set_field('peerreview_submissions', 'timecreated', $timestamp);
        $DB->set_field('peerreview_submissions', 'timemodified', $timestamp);
        $DB->set_field('peerreview_assessments', 'timecreated', $timestamp);
        $DB->set_field('peerreview_assessments', 'timemodified', $timestamp);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newpeerreview = $DB->get_record('peerreview', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($peerreview, $newpeerreview, ['timemodified']);
        $props = ['submissionstart', 'submissionend', 'assessmentend', 'assessmentstart'];
        $this->assertFieldsRolledForward($peerreview, $newpeerreview, $props);

        $submissions = $DB->get_records('peerreview_submissions', ['peerreviewid' => $newpeerreview->id]);
        // peerreview submission time checks.
        foreach ($submissions as $submission) {
            $this->assertEquals($timestamp, $submission->timecreated);
            $this->assertEquals($timestamp, $submission->timemodified);
            $assessments = $DB->get_records('peerreview_assessments', ['submissionid' => $submission->id]);
            // peerreview assessment time checks.
            foreach ($assessments as $assessment) {
                $this->assertEquals($timestamp, $assessment->timecreated);
                $this->assertEquals($timestamp, $assessment->timemodified);
            }
        }
    }
}
