<?php
/**
 * AJAX call to save image file and make it part of moodle file
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/proctoring/lib.php');

$img = required_param('imgBase64', PARAM_RAW);
$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$mainimage = optional_param('mainimage', false, PARAM_BOOL);

require_login();

if (!$cm = get_coursemodule_from_id('quiz', $cmid)) {
    print_error('invalidcoursemodule');
}

$data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $img));
$target = '';
if (!$mainimage) {
    // If it is not main image, get the main image data and compare
    if ($mainentry = $DB->get_record('quizaccess_proctoring_data', array('userid' => $USER->id, 'quizid' => $cm->instance, 'image_status' => 'M'))) {
        $target = $mainentry->userimg;
    }
}

//validate image
\quizaccess_proctoring\aws\camera::init();
if ($target !== '') {
    $tdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $target));
    $validate = \quizaccess_proctoring\aws\camera::validate($data, $tdata);
} else {
    $validate = \quizaccess_proctoring\aws\camera::validate($data);
}

switch ($validate) {
    case QUIZACCESS_PROCTORING_NOFACEDETECTED:
        if (!$mainimage) {
            storeimage($img, $cmid, $attemptid, $cm->instance, $mainimage);
        }
        print_error(QUIZACCESS_PROCTORING_NOFACEDETECTED);
        break;
    case QUIZACCESS_PROCTORING_MULTIFACESDETECTED:
        if (!$mainimage) {
            storeimage($img, $cmid, $attemptid, $cm->instance, $mainimage);
        }
        print_error(QUIZACCESS_PROCTORING_MULTIFACESDETECTED);
        break;
    case QUIZACCESS_PROCTORING_FACESNOTMATCHED:
        if (!$mainimage) {
            storeimage($img, $cmid, $attemptid, $cm->instance, $mainimage);
        }
        print_error(QUIZACCESS_PROCTORING_FACESNOTMATCHED);
        break;
    case QUIZACCESS_PROCTORING_EYESNOTOPENED:
        if (!$mainimage) {
            storeimage($img, $cmid, $attemptid, $cm->instance, $mainimage);
        }
        print_error(QUIZACCESS_PROCTORING_EYESNOTOPENED);
        break;
    default:
        //Store only if main image
        if ($mainimage) {
            storeimage($img, $cmid, $attemptid, $cm->instance, $mainimage);
        }
        break;
        
}
return json_encode(array('status' => 'true'));