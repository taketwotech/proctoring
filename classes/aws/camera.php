<?php
/**
 * API methods from AWS rekognition to detect camera pictures
 *
 * @package    quizaccess
 * @subpackage proctoring
 * @copyright  2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_proctoring\aws;

defined('MOODLE_INTERNAL') || die();
define('AWS_VERSION', 'latest');
define('AWS_REGION', 'us-east-1');

require_once($CFG->dirroot . '/mod/quiz/accessrule/proctoring/lib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/proctoring/libraries/aws/aws-autoloader.php');

/**
 * API exposed by AWS, to be used by camera images.
 *
 * @copyright 2020 Mahendra Soni <ms@taketwotechnologies.com> {@link https://taketwotechnologies.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class camera {
    private static $amazon_api_key = null;
    private static $amazon_api_secret = null;
    private static $client = null;

    /**
     * Initialize key and token
     *
     * @return null 
     */
    public static function init() {
        global $CFG;
        self::$amazon_api_key = $CFG->quizaccess_proctoring_aws_key;
        self::$amazon_api_secret = $CFG->quizaccess_proctoring_aws_secret;
        self::$client = new \Aws\Rekognition\RekognitionClient([
            'version' => AWS_VERSION,
            'region' => AWS_REGION,
            'credentials' => [
                'key' => self::$amazon_api_key,
                'secret' => self::$amazon_api_secret
            ]
        ]);
    }    

    /**
     * Validate the image captured
     *
     * @return null 
     */
    public static function validate($source, $target = '') {
        global $CFG;
        $result = self::detect_faces($source);

        if (isset($result["FaceDetails"]) && count($result["FaceDetails"]) > 0) {
            $count = count($result["FaceDetails"]);
            if ($count  > 1) {
                return QUIZACCESS_PROCTORING_MULTIFACESDETECTED;
            } else if ($count == 1) {
                $eyesOpen = $result['FaceDetails'][0]['EyesOpen']['Value'];
                if (!$eyesOpen) {
                    return QUIZACCESS_PROCTORING_EYESNOTOPENED;
                } else if ($target !== '') {
                    $compareresult = self::compare_faces($source, $target);
                    if (!$compareresult || $compareresult < QUIZACCESS_PROCTORING_FACEMATCHTHRESHOLD) {
                        return QUIZACCESS_PROCTORING_FACESNOTMATCHED;
                    }
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return QUIZACCESS_PROCTORING_NOFACEDETECTED;
        }
    }

    /** 
     * Detect faces in an image
     *
     * @param $source
     * @return bool|int
     */
    public static function detect_faces($source) {
        $result = self::$client->detectFaces([
            'Image' => [
                'Bytes' => $source
            ],
            'Attributes' => ['ALL']
        ]);

        return $result;
        if (isset($result["FaceDetails"]) && count($result["FaceDetails"]) > 0) {
            return count($result["FaceDetails"]);
        }
        return false;
    }

    /** 
     * Compare faces in source and target image
     *
     * @param $source
     * @return bool|int
     */
    public static function compare_faces($source, $target) {
        $result = self::$client->CompareFaces([
            'SourceImage' => [
                'Bytes' => $source
            ],
            'TargetImage' => [
                'Bytes' => $target
            ]
        ]);
        if (isset($result["FaceMatches"]) && isset($result["FaceMatches"][0]) && isset($result["FaceMatches"][0]["Similarity"])) {
            return $result["FaceMatches"][0]["Similarity"];
        }
        return false;
    }
}