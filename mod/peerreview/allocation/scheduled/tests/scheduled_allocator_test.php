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

namespace peerreviewallocation_scheduled;

/**
 * Test for the scheduled allocator.
 *
 * @package peerreviewallocation_scheduled
 * @copyright 2020 Jaume I University <https://www.uji.es/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_allocator_test extends \advanced_testcase {

    /** @var \stdClass $course The course where the tests will be run */
    private $course;

    /** @var \peerreview $peerreview The peerreview where the tests will be run */
    private $peerreview;

    /** @var \stdClass $peerreviewcm The peerreview course module instance */
    private $peerreviewcm;

    /** @var \stdClass[] $students An array of student enrolled in $course */
    private $students;

    /**
     * Tests that student submissions get automatically alocated after the submission deadline and when the peerreview
     * "Switch to the next phase after the submissions deadline" checkbox is active.
     */
    public function test_that_allocator_in_executed_on_submission_end_when_phaseswitchassessment_is_active(): void {
        global $DB;

        $this->resetAfterTest();

        $this->setup_test_course_and_peerreview();

        $this->activate_switch_to_the_next_phase_after_submission_deadline();
        $this->set_the_submission_deadline_in_the_past();
        $this->activate_the_scheduled_allocator();

        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        \core\cron::setup_user();

        // Let the students add submissions.
        $this->peerreview->switch_phase(\peerreview::PHASE_SUBMISSION);

        // Create some submissions.
        foreach ($this->students as $student) {
            $peerreviewgenerator->create_submission($this->peerreview->id, $student->id);
        }

        // No allocations yet.
        $this->assertEmpty($this->peerreview->get_allocations());

        /* Execute the tasks that will do the transition and allocation thing.
         * We expect the peerreview cron to do the whole work: change the phase and
         * allocate the submissions.
         */
        $this->execute_peerreview_cron_task();

        $peerreviewdb = $DB->get_record('peerreview', ['id' => $this->peerreview->id]);
        $peerreview = new \peerreview($peerreviewdb, $this->peerreviewcm, $this->course);

        $this->assertEquals(\peerreview::PHASE_ASSESSMENT, $peerreview->phase);
        $this->assertNotEmpty($peerreview->get_allocations());
    }

    /**
     * No allocations are performed if the allocator is not enabled.
     */
    public function test_that_allocator_is_not_executed_when_its_not_active(): void {
        global $DB;

        $this->resetAfterTest();

        $this->setup_test_course_and_peerreview();
        $this->activate_switch_to_the_next_phase_after_submission_deadline();
        $this->set_the_submission_deadline_in_the_past();

        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        \core\cron::setup_user();

        // Let the students add submissions.
        $this->peerreview->switch_phase(\peerreview::PHASE_SUBMISSION);

        // Create some submissions.
        foreach ($this->students as $student) {
            $peerreviewgenerator->create_submission($this->peerreview->id, $student->id);
        }

        // No allocations yet.
        $this->assertEmpty($this->peerreview->get_allocations());

        // Transition to the assessment phase.
        $this->execute_peerreview_cron_task();

        $peerreviewdb = $DB->get_record('peerreview', ['id' => $this->peerreview->id]);
        $peerreview = new \peerreview($peerreviewdb, $this->peerreviewcm, $this->course);

        // No allocations too.
        $this->assertEquals(\peerreview::PHASE_ASSESSMENT, $peerreview->phase);
        $this->assertEmpty($peerreview->get_allocations());
    }

    /**
     * Activates and configures the scheduled allocator for the peerreview.
     */
    private function activate_the_scheduled_allocator(): void {

        $settings = \peerreview_random_allocator_setting::instance_from_object((object)[
            'numofreviews' => count($this->students),
            'numper' => 1,
            'removecurrentuser' => true,
            'excludesamegroup' => false,
            'assesswosubmission' => true,
            'addselfassessment' => false
        ]);

        $allocator = new \peerreview_scheduled_allocator($this->peerreview);

        $storesettingsmethod = new \ReflectionMethod('peerreview_scheduled_allocator', 'store_settings');
        $storesettingsmethod->setAccessible(true);
        $storesettingsmethod->invoke($allocator, true, true, $settings, new \peerreview_allocation_result($allocator));
    }

    /**
     * Creates a minimum common setup to execute tests:
     */
    protected function setup_test_course_and_peerreview(): void {
        $this->setAdminUser();

        $datagenerator = $this->getDataGenerator();

        $this->course = $datagenerator->create_course();

        $this->students = [];
        for ($i = 0; $i < 10; $i++) {
            $this->students[] = $datagenerator->create_and_enrol($this->course);
        }

        $peerreviewdb = $datagenerator->create_module('peerreview', [
            'course' => $this->course,
            'name' => 'Test peerreview',
        ]);
        $this->peerreviewcm = get_coursemodule_from_instance('peerreview', $peerreviewdb->id, $this->course->id, false, MUST_EXIST);
        $this->peerreview = new \peerreview($peerreviewdb, $this->peerreviewcm, $this->course);
    }

    /**
     * Executes the peerreview cron task.
     */
    protected function execute_peerreview_cron_task(): void {
        ob_start();
        $cron = new \mod_peerreview\task\cron_task();
        $cron->execute();
        ob_end_clean();
    }

    /**
     * Executes the scheduled allocator cron task.
     */
    protected function execute_allocator_cron_task(): void {
        ob_start();
        $cron = new \peerreviewallocation_scheduled\task\cron_task();
        $cron->execute();
        ob_end_clean();
    }

    /**
     * Activates the "Switch to the next phase after the submissions deadline" flag in the peerreview.
     */
    protected function activate_switch_to_the_next_phase_after_submission_deadline(): void {
        global $DB;
        $DB->set_field('peerreview', 'phaseswitchassessment', 1, ['id' => $this->peerreview->id]);
    }

    /**
     * Sets the submission deadline in a past time.
     */
    protected function set_the_submission_deadline_in_the_past(): void {
        global $DB;
        $DB->set_field('peerreview', 'submissionend', time() - 1, ['id' => $this->peerreview->id]);
    }
}
