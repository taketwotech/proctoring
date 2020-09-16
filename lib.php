<?php
/**
 * library functions for quizaccess_proctoring plugin.
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('QUIZACCESS_PROCTORING_NOFACEDETECTED', 'nofacedetected');
define('QUIZACCESS_PROCTORING_MULTIFACESDETECTED', 'multifacesdetected');
define('QUIZACCESS_PROCTORING_FACESNOTMATCHED', 'facesnotmatched');
define('QUIZACCESS_PROCTORING_EYESNOTOPENED', 'eyesnotopened');
define('QUIZACCESS_PROCTORING_FACEMATCHTHRESHOLD', 90);

/**
 * Serves the quizaccess proctoring files.
 *
 * @package  quizaccess_proctoring
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */

 function quizaccess_proctoring_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, 
    array $options=array()) {
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/quizaccess_proctoring/$filearea/$relativepath";
    //echo $fullpath;die;
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function camera_task_start($cmid,$attemptid,$quizid) {
    global $DB,$PAGE, $OUTPUT;
    $interval = $DB->get_record('quizaccess_proctoring',array('quizid'=>$quizid));
    $PAGE->requires->js_call_amd('quizaccess_proctoring/add_camera', 'init',[$cmid, false, true, $attemptid,$interval->time_interval]);
}

function storeimage($data, $cmid, $attemptid, $quizid, $mainimage){
    global $USER, $DB;

    $user = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
    //we are all good, store the image
    if ( $mainimage ) {
        if ($qpd = $DB->get_record('quizaccess_proctoring_data', array('userid' => $user->id, 'quizid' => $quizid, 'attemptid' => $attemptid, 'image_status' => 'M' ))) {
            $DB->delete_records('quizaccess_proctoring_data', array('id' => $qpd->id));
        }
        if ($qpd = $DB->get_record('quizaccess_proctoring_data', array('userid' => $user->id, 'quizid' => $quizid, 'attemptid' => $attemptid, 'image_status' => 'I' ))) {
            $DB->delete_records('quizaccess_proctoring_data', array('id' => $qpd->id));
        }
    }

    $record = new stdClass();
    $record->userid = $user->id;
    $record->quizid = $quizid;
    $record->image_status = $mainimage ? 'I' : 'A';
    $record->aws_response = 'aws';
    $record->timecreated = time();
    $record->timemodified = time();
    // $record->userimg = $attemptid."_".$user->id.'_myimage.png';
    $record->userimg = $data;
    $record->attemptid  = $attemptid;
    $id = $DB->insert_record('quizaccess_proctoring_data', $record);

    $tmpdir = make_temp_directory('quizaccess_proctoring/captured/');
    file_put_contents($tmpdir . 'myimage.png', $data);
    $fs = get_file_storage(); 
    // Prepare file record object
    $context = context_module::instance($cmid);
    $fileinfo = array(
        'contextid' => $context->id,
        'component' => 'quizaccess_proctoring',
        'filearea'  => 'cameraimages',   
        'itemid'    => $id,
        'filepath'  => '/',
        'filename'  => $attemtpid . "_" . $USER->id . '_myimage.png');
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    if ($file) {
        $file->delete();
    }
    $fs->create_file_from_pathname($fileinfo, $tmpdir . 'myimage.png');
    @unlink($tmpdir . 'myimage.png');
}