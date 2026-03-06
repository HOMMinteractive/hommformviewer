<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2026 HOMM interactive
 */

use craft\helpers\App;

/**
 * HOMMForm config.php
 *
 * This file exists only as a template for the HOMMForm settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'hommform.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    'enableCpSection' => true,

    // where uploaded files should be stored; accepts aliases
    'storagePath' => App::env('HOMM_FORM_STORAGE_PATH') ?? '@storage/form',

    // list of extra file extensions that may be uploaded
    'allowedFileTypes' => ['jpg', 'jpeg', 'eps', 'png', 'svg', 'pdf', 'doc', 'docx', 'xlsx', 'xls', 'odt', 'ods', 'odp', 'txt', 'csv', 'rtf'],

    // reCAPTCHA integration (site/secret keys can be pulled from env)
    'recaptchaSiteKey' => App::env('RECAPTCHA_SITE_KEY'),
    'recaptchaSecret' => App::env('RECAPTCHA_SECRET_KEY'),
    'recaptchaScoreThreshold' => App::env('RECAPTCHA_SCORE_THRESHOLD'),

    // custom templates for the notification emails
    'htmlMailTemplatePath' => null,
    'textMailTemplatePath' => null,
];
