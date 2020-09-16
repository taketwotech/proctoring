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
 * Strings for the quizaccess_proctoring plugin.
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'Proctoring quiz access rule';
$string['privacy:metadata'] = 'The Proctoring quiz access rule plugin does not store any personal data.';
$string['requiresafeexambrowser'] = 'Require the use of Safe Exam Browser';
$string['proctoringerror'] = 'This quiz has been set up so that it may only be attempted using the Proctoring.';
$string['proctoringnotice'] = 'This quiz has been configured so that students may only attempt it using the Proctoring.';
$string['enableproctoring'] = 'Enable proctoring with this quiz';
$string['enableproctoring_help'] = 'If you enable it, user has to verify their identity before starting this test';
$string['requireproctoringmessage'] = 'Please capture your image and upload ID proof';
$string['uploadidentity'] = 'Upload your indentiy';
$string['takepicture'] = 'Take picture';
$string['retake'] = 'Retake';
$string['useridentityerror'] = 'Please upload a valid file';
$string['awskey'] = 'AWS API Key';
$string['awskey_help'] = 'Enter AWS API key here to be used to access AWS services';
$string['awssecret'] = 'AWS Secret Key';
$string['awssecret_help'] = 'Enter AWS Secret here to be used to access AWS services';
$string['help_timeinterval'] = 'Select time interval for image procotring';
$string['proctoringtimeinterval'] = 'Time interval';
$string['nofacedetected'] = 'No face detected';
$string['multifacesdetected'] = 'More than one face detected';
$string['facesnotmatched'] = 'Your current image is different from the initial image.';
$string['eyesnotopened'] = 'Do not cover your eyes';