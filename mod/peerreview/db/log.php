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
 * Definition of log events
 *
 * @package    mod_peerreview
 * @category   log
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    // peerreview instance log actions
    array('module'=>'peerreview', 'action'=>'add', 'mtable'=>'peerreview', 'field'=>'name'),
    array('module'=>'peerreview', 'action'=>'update', 'mtable'=>'peerreview', 'field'=>'name'),
    array('module'=>'peerreview', 'action'=>'view', 'mtable'=>'peerreview', 'field'=>'name'),
    array('module'=>'peerreview', 'action'=>'view all', 'mtable'=>'peerreview', 'field'=>'name'),
    // submission log actions
    array('module'=>'peerreview', 'action'=>'add submission', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'update submission', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'view submission', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    // assessment log actions
    array('module'=>'peerreview', 'action'=>'add assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'update assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    // example log actions
    array('module'=>'peerreview', 'action'=>'add example', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'update example', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'view example', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    // example assessment log actions
    array('module'=>'peerreview', 'action'=>'add reference assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'update reference assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'add example assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    array('module'=>'peerreview', 'action'=>'update example assessment', 'mtable'=>'peerreview_submissions', 'field'=>'title'),
    // grading evaluation log actions
    array('module'=>'peerreview', 'action'=>'update aggregate grades', 'mtable'=>'peerreview', 'field'=>'name'),
    array('module'=>'peerreview', 'action'=>'update clear aggregated grades', 'mtable'=>'peerreview', 'field'=>'name'),
    array('module'=>'peerreview', 'action'=>'update clear assessments', 'mtable'=>'peerreview', 'field'=>'name'),
);
