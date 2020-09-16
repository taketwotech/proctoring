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
 * Proctoring observers.
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_proctoring;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/proctoring/lib.php');

/**
 * Proctoring observers class.
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    public static function start_camera_and_validate($event){
        global $DB, $CFG;
        $eventdata = $event->get_data();
        if ($quizid = $eventdata['other']['quizid']) {
            if ($DB->record_exists('quizaccess_proctoring', array('quizid' => $quizid, 'enableproctoring' => 1))){
                camera_task_start($eventdata['contextinstanceid'], $eventdata['objectid'], $quizid); 
            }
        }
    }

    public  static function proctoring_deleted($event) {
        global $DB;

        $eventdata = $event->get_data();
        if ($quizid = $eventdata['other']['quizid']) {
            if ($records = $DB->get_records('quizaccess_proctoring_data', array('quizid' => $quizid, 'userid' => $event->relateduserid))) {
                foreach ($records as $record) {
                    $record->deleted = 1;
                    $DB->update_record('quizaccess_proctoring_data',$record);                    
                }
            }
        }
    }
}
