<?php
/**
 * Implementaton of the quizaccess_proctoring plugin.
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');


/**
 * A rule representing the safe browser check.
 *
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_proctoring extends quiz_access_rule_base {

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {

        if (!$quizobj->get_quiz()->enableproctoring) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    public function prevent_access() {
        if (!$this->check_proctoring()) {
            return get_string('proctoringerror', 'quizaccess_proctoring');
        } else {
            return false;
        }
    }

    public function description() {
        return get_string('proctoringnotice', 'quizaccess_proctoring');
    }

    public function is_preflight_check_required($attemptid) {
        global $SESSION, $DB, $USER;
        $user = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        $attemptid = $attemptid ? $attemptid : 0;
        if ($DB->record_exists('quizaccess_proctoring_data', array('quizid' => $this->quiz->id, 'image_status' => 'M', 'userid' => $user->id, 'deleted' => 0))) {
            return false;
        } else {
            return empty($SESSION->proctoringcheckedquizzes[$this->quiz->id]);
        }
    }

    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform,
            MoodleQuickForm $mform, $attemptid) {
        global $PAGE;

        $PAGE->requires->js_call_amd('quizaccess_proctoring/add_camera', 'init', [$this->quiz->cmid, true, false, $attemptid]);
    
        $mform->addElement('static', 'proctoringmessage', '',
                get_string('requireproctoringmessage', 'quizaccess_proctoring'));

        $filemanager_options = array();
        $filemanager_options['accepted_types'] = '*';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = 1;
        $filemanager_options['mainfile'] = true;
        //video tag
        $html = html_writer::start_tag('div', array('id' => 'fitem_id_user_video', 'class' => 'form-group row fitem videohtml'));
        $html .= html_writer::div('','col-md-3');
        $videotag   = html_writer::tag('video', '', array('id' => 'video', 'width' => '320', 'height' => '240', 'autoplay'=>'autoplay'));
        $html .= html_writer::div($videotag, 'col-md-9');
        $html .= html_writer::end_tag('div');

        //canvas tag
        $html  .= html_writer::start_tag('div', array('id' => 'fitem_id_user_canvas', 'class' =>'form-group row fitem videohtml'));
        $html .= html_writer::div('', 'col-md-3');
        $canvastag   = html_writer::tag('canvas', '', array('id' => 'canvas', 'width' => '320', 'height' => '240', 'class'=>'hidden'));
        $html .= html_writer::div($canvastag, 'col-md-9');
        $html .= html_writer::end_tag('div');
                        
        //Take picture button
        $html .= html_writer::start_tag('div', array('id' => 'fitem_id_user_takepicture', 'class' =>'form-group row fitem'));
        $html .= html_writer::div('', 'col-md-3');
        

        $button = html_writer::tag('button', get_string('takepicture', 'quizaccess_proctoring'), 
            array('class' => 'btn btn-primary', 'id' => 'takepicture'));
        $html .= html_writer::div($button, 'col-md-9');
        $html .= html_writer::end_tag('div');

        //Retake button
        $html .= html_writer::start_tag('div', array('id' => 'fitem_id_user_retake', 'class' =>'form-group row fitem'));
        $html .= html_writer::div('', 'col-md-3');
        $button = html_writer::tag('button', get_string('retake', 'quizaccess_proctoring'), 
            array('class' => 'btn btn-primary hidden', 'id' => 'retake'));
        $html .= html_writer::div($button, 'col-md-9');
        $html .= html_writer::end_tag('div');

        $mform->addElement('hidden','userimg','userimg','1');
        $mform->setType('userimg',PARAM_TEXT);
        $mform->addElement('html', $html);
        $mform->addElement('filemanager', 'user_identity', get_string('uploadidentity', 'quizaccess_proctoring'), null, $filemanager_options);
        $mform->addRule('user_identity', null, 'required', null, 'client');

    }

    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        global $USER, $DB, $CFG;
        $user_identity = $data['user_identity'];
        $cmid =  $data['cmid'];
        $userimg = $data['userimg'];
        $record = new stdClass();
        $record->user_identity = $user_identity;
        $record->userid = $USER->id;
        $record->quizid = $this->quiz->id;
        $record->userimg = $userimg;
        $attemptid = $attemptid ? $attemptid : 0;
        // We probably have an entry already in DB.
        if ($rc = $DB->get_record('quizaccess_proctoring_data', array('userid' => $USER->id, 'quizid' => $this->quiz->id, 'attemptid' => $attemptid, 'image_status' => 'I' ))) {
            $context = context_module::instance($cmid);
            $rc->user_identity = $user_identity;
            $rc->image_status = 'M';
            $DB->update_record('quizaccess_proctoring_data', $rc);
            file_save_draft_area_files($user_identity, $context->id, 'quizaccess_proctoring', 'identity' , $rc->id);
        } else if ($id = $DB->insert_record('quizaccess_proctoring_data', $record)){
            $context = context_module::instance($cmid);
            file_save_draft_area_files($user_identity, $context->id,'quizaccess_proctoring', 'identity' , $id);
        } else {
            $errors['user_identity'] = get_string('useridentityerror', 'quizaccess_proctoring');
        }
        return $errors; 
    }

    public function notify_preflight_check_passed($attemptid) {
        global $SESSION;
        $SESSION->proctoringcheckedquizzes[$this->quiz->id] = true;
    }

    /**
     * Checks if required SDK and APIs are available
     *
     * @return true, if browser is safe browser else false
     */
    public function check_proctoring() {
        return true;
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        global $CFG;
                
        // Allow to enable the access rule only if the Mobile services are enabled.
        $mform->addElement('selectyesno', 'enableproctoring', get_string('enableproctoring', 'quizaccess_proctoring'));
        $mform->addHelpButton('enableproctoring', 'enableproctoring', 'quizaccess_proctoring');
        $mform->setDefault('enableproctoring', 0);

        // time interval set for proctoring image.
        $mform->addElement('select', 'time_interval', get_string('proctoringtimeinterval','quizaccess_proctoring'),
                array("1"=>"1 minute","5"=>"5 minutes","10" => "10 minutes"));
       // $mform->addHelpButton('interval', 'interval', 'quiz');
        $mform->setDefault('time_interval', $CFG->quizaccess_proctoring_img_check_time);
    }

    public static function save_settings($quiz) {
        global $DB;
        
        $interval = required_param('time_interval',PARAM_INT);
        if (empty($quiz->enableproctoring)) {
            $DB->delete_records('quizaccess_proctoring', array('quizid' => $quiz->id));
            $record = new stdClass();
            $record->quizid = $quiz->id;
            $record->enableproctoring = 0;
            $record->time_interval = $interval;
            $DB->insert_record('quizaccess_proctoring', $record);
        } else {
            $DB->delete_records('quizaccess_proctoring', array('quizid' => $quiz->id));
            $record = new stdClass();
            $record->quizid = $quiz->id;
            $record->enableproctoring = 1;
            $record->time_interval = $interval;
            $DB->insert_record('quizaccess_proctoring', $record);
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_proctoring', array('quizid' => $quiz->id));
    }
    
    public static function get_settings_sql($quizid) {
        return array(
            'enableproctoring,time_interval',
            'LEFT JOIN {quizaccess_proctoring} proctoring ON proctoring.quizid = quiz.id',
            array());
    }

    public function current_attempt_finished() {
        global $SESSION;
        // Clear the flag in the session that says that the user has already
        // entered the password for this quiz.
        if (!empty($SESSION->proctoringcheckedquizzes[$this->quiz->id])) {
            unset($SESSION->proctoringcheckedquizzes[$this->quiz->id]);
        }
    }
}
