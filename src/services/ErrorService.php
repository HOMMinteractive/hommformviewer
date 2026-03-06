<?php

/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2026 HOMM interactive
 */

namespace homm\hommform\services;

use Craft;
use craft\base\Component;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
 */
class ErrorService extends Component
{
    public const RECAPTCHA_VERIFICATION_FAILED = 'C955-JTS0';

    public const SEND_EMAIL_FAILED = 'WFQT-OMCT';

    public const FILE_TYPE_NOT_ALLOWED = 'PYZF-XFZK';

    public const FILE_UPLOAD_FAILED = '1RUU-EUNT';

    public const FOLDER_CREATION_FAILED = 'AL1R-ZCW3';

    public const DATABASE_ERROR = '1DGS-46UW';

    public function recaptchaValidationFailed(): array
    {
        return [
            'code' => self::RECAPTCHA_VERIFICATION_FAILED,
            'error' => 'RECAPTCHA_VERIFICATION_FAILED',
            'message' => Craft::t('hommform', 'Failed to verify reCAPTCHA response'),
        ];
    }

    public function sendEmailFailed(): array
    {
        return [
            'code' => self::SEND_EMAIL_FAILED,
            'error' => 'SEND_EMAIL_FAILED',
            'message' => Craft::t('hommform', 'Failed to send email notification'),
        ];
    }

    public function fileTypeNotAllowed(): array
    {
        return [
            'code' => self::FILE_TYPE_NOT_ALLOWED,
            'error' => 'FILE_TYPE_NOT_ALLOWED',
            'message' => Craft::t('hommform', 'File type not allowed'),
        ];
    }

    public function fileUploadFailed(): array
    {
        return [
            'code' => self::FILE_UPLOAD_FAILED,
            'error' => 'FILE_UPLOAD_FAILED',
            'message' => Craft::t('hommform', 'Failed to upload file'),
        ];
    }

    public function folderCreationFailed(): array
    {
        return [
            'code' => self::FOLDER_CREATION_FAILED,
            'error' => 'FOLDER_CREATION_FAILED',
            'message' => Craft::t('hommform', 'Failed to create upload folder'),
        ];
    }

    public function databaseError(): array
    {
        return [
            'code' => self::DATABASE_ERROR,
            'error' => 'DATABASE_ERROR',
            'message' => Craft::t('hommform', 'Failed to insert form data'),
        ];
    }
}
