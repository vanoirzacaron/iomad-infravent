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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package    peerreviewform_numerrors
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/gradelib.php'); // grade_floatval() called here

/**
 * Conversion handler for the numerrors grading strategy data
 */
class moodle1_peerreviewform_numerrors_handler extends moodle1_peerreviewform_handler {

    /** @var array */
    protected $mappings = array();

    /** @var array */
    protected $dimensions = array();

    /**
     * New peerreview instance is being processed
     */
    public function on_elements_start() {
        $this->mappings = array();
        $this->dimensions = array();
    }

    /**
     * Converts <ELEMENT> into <peerreviewform_numerrors_dimension> and stores it for later writing
     *
     * @param array $data legacy element data
     * @param array $raw raw element data
     *
     * @return array to be written to peerreview.xml
     */
    public function process_legacy_element(array $data, array $raw) {

        $peerreview = $this->parenthandler->get_current_peerreview();

        $mapping = array();
        $mapping['id'] = $data['id'];
        $mapping['nonegative'] = $data['elementno'];
        if ($peerreview['grade'] == 0 or $data['maxscore'] == 0) {
            $mapping['grade'] = 0;
        } else {
            $mapping['grade'] = grade_floatval($data['maxscore'] / $peerreview['grade'] * 100);
        }
        $this->mappings[] = $mapping;

        $converted = null;

        if (trim($data['description']) and $data['description'] <> '@@ GRADE_MAPPING_ELEMENT @@') {
            // prepare a fake record and re-use the upgrade logic
            $fakerecord = (object)$data;
            $converted = (array)peerreviewform_numerrors_upgrade_element($fakerecord, 12345678);
            unset($converted['peerreviewid']);

            $converted['id'] = $data['id'];
            $this->dimensions[] = $converted;
        }

        return $converted;
    }

    /**
     * Writes gathered mappings and dimensions
     */
    public function on_elements_end() {

        foreach ($this->mappings as $mapping) {
            $this->write_xml('peerreviewform_numerrors_map', $mapping, array('/peerreviewform_numerrors_map/id'));
        }

        foreach ($this->dimensions as $dimension) {
            $this->write_xml('peerreviewform_numerrors_dimension', $dimension, array('/peerreviewform_numerrors_dimension/id'));
        }
    }
}

/**
 * Transforms a given record from peerreview_elements_old into an object to be saved into peerreviewform_numerrors
 *
 * @param stdClass $old legacy record from peerreview_elements_old
 * @param int $newpeerreviewid id of the new peerreview instance that replaced the previous one
 * @return stdclass to be saved in peerreviewform_numerrors
 */
function peerreviewform_numerrors_upgrade_element(stdclass $old, $newpeerreviewid) {
    $new = new stdclass();
    $new->peerreviewid = $newpeerreviewid;
    $new->sort = $old->elementno;
    $new->description = $old->description;
    $new->descriptionformat = FORMAT_HTML;
    $new->grade0 = get_string('grade0default', 'peerreviewform_numerrors');
    $new->grade1 = get_string('grade1default', 'peerreviewform_numerrors');
    // calculate new weight of the element. Negative weights are not supported any more and
    // are replaced with weight = 0. Legacy peerreview did not store the raw weight but the index
    // in the array of weights (see $peerreview_EWEIGHTS in peerreview 1.x)
    // peerreview 2.0 uses integer weights only (0-16) so all previous weights are multiplied by 4.
    switch ($old->weight) {
        case 8: $new->weight = 1; break;
        case 9: $new->weight = 2; break;
        case 10: $new->weight = 3; break;
        case 11: $new->weight = 4; break;
        case 12: $new->weight = 6; break;
        case 13: $new->weight = 8; break;
        case 14: $new->weight = 16; break;
        default: $new->weight = 0;
    }
    return $new;
}
