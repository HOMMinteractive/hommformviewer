<?php

/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform\services;

use Craft;
use craft\db\Query;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use homm\hommform\HOMMForm;
use yii\web\Request;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
 */
class SubmitService extends Component
{
    private const ALLOWED_FILE_TYPES = [
        'jpg', 'jpeg', 'eps', 'png', 'svg',
        'pdf', 'doc', 'docx', 'xlsx', 'xls', 'odt', 'ods', 'odp',
        'txt', 'csv', 'rtf',
    ];

    private const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private function slugify(string $string): string
    {
        // Convert the string to lowercase
        $slug = mb_strtolower($string);

        // Replace spaces with hyphens
        $slug = str_replace(' ', '-', $slug);

        // Remove special characters
        $slug = preg_replace('/[^\w\-]/', '', $slug);

        // Remove consecutive hyphens
        $slug = preg_replace('/\-\-+/', '-', $slug);

        // Trim leading and trailing hyphens
        $slug = trim($slug, '-');

        return $slug;
    }

    public function validateReCaptcha(string $recaptcha_response): bool
    {
        $secret = App::parseEnv(HOMMForm::$plugin->getSettings()->recaptchaSecret);
        $scoreThreshold = HOMMForm::$plugin->getSettings()->recaptchaScoreThreshold;

        if (! $secret) {
            // If no secret is set, skip reCAPTCHA validation
            return true;
        }

        try {
            $response = Craft::createGuzzleClient()->get(self::RECAPTCHA_URL, [
                'query' => [
                    'secret' => $secret,
                    'response' => $recaptcha_response,
                ],
            ]);
        } catch (\Throwable $th) {
            Craft::error('Failed to call reCAPTCHA API: ' . $th->getMessage(), __METHOD__);

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $body = json_decode($response->getBody());

        if (
            ! isset($body->success)
            || ! $body->success
            || ! isset($body->score)
            || $body->score < $scoreThreshold
        ) {
            return false;
        }

        return true;
    }

    public function parseBodyParams(Request $request, DateTime $dateCreated): array
    {
        $payload = $request->getBodyParams();

        unset($payload['CRAFT_CSRF_TOKEN']);
        unset($payload['redirect']);

        $formId = $request->getBodyParam('formId');
        unset($payload['formId']);

        $receivers = $request->getValidatedBodyParam('receivers');
        unset($payload['receivers']);

        $subject = $request->getValidatedBodyParam('subject');
        unset($payload['subject']);

        $replyto = $payload[$request->getValidatedBodyParam('replyto')] ?? null;
        unset($payload['replyto']);

        $ip = $request->getUserIP();

        $recaptcha_response = $request->getBodyParam('recaptcha_response');
        unset($payload['recaptcha_response']);

        $confirmation = $request->getBodyParam('confirmation');
        unset($payload['confirmation']);

        $submissionId = $dateCreated->format('Ymd-His') . '-' . bin2hex(random_bytes(4));
        foreach (array_keys($_FILES) as $key) {
            $file = UploadedFile::getInstancesByName($key)[0] ?? null;

            if (! ($file instanceof UploadedFile)) {
                continue;
            }

            $payload[$key] = [
                'file' => $file,
                'name' => $this->slugify($file->baseName) . '.' . $file->extension,
                'folder' => $submissionId,
                'storage' => HOMMForm::$plugin->getSettings()->storagePath,
            ];
        }

        foreach ($payload as $key => $item) {
            if (is_string($item) && json_validate($item)) {
                $payload[$key] = json_decode($item, true);
            }
        }

        return [
            $formId,
            $receivers,
            $subject,
            $replyto,
            $recaptcha_response,
            $ip,
            $payload,
            $confirmation,
        ];
    }

    public function save(string $formId, string $receivers, string $replyto, string $subject, array $payload, string $ip, DateTime $dateCreated): array
    {
        $errors = [];

        foreach ($payload as $key => $value) {
            $file = $value['file'] ?? null;
            $filepath = isset($value['folder']) && isset($value['name']) && isset($value['storage'])
                ? Craft::getAlias($value['storage'] . '/' . $value['folder'] . '/' . $value['name'])
                : null;
            $folderpath = dirname($filepath);

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! in_array(strtolower($file->extension), self::ALLOWED_FILE_TYPES)) {
                $errors[] = [
                    'error' => 'file_type_not_allowed',
                    'name' => $file->name,
                    'message' => Craft::t('hommform', 'File type not allowed'),
                ];

                continue;
            }

            if (! FileHelper::createDirectory($folderpath)) {
                $errors[] = [
                    'error' => 'directory_creation_failed',
                    'name' => $file->name,
                    'message' => Craft::t('hommform', 'Failed to create directory'),
                ];

                continue;
            }

            if (! $file->saveAs($filepath)) {
                $errors[] = [
                    'error' => 'upload_failed',
                    'name' => $file->name,
                    'message' => Craft::t('hommform', 'Failed to upload file'),
                ];

                continue;
            }

            unset($payload[$key]['file']);
        }

        if (count($errors)) {
            return $errors;
        }

        try {
            $insert = (new Query())
                ->createCommand()
                ->insert('{{%homm_form_submissions}}', [
                    'formId' => $formId,
                    'receivers' => $receivers,
                    'replyto' => $replyto,
                    'subject' => $subject,
                    'payload' => $payload,
                    'ip' => $ip,
                    'dateCreated' => $dateCreated->format('Y-m-d H:i:s'),
                ])
                ->execute();

            if ($insert <= 0) {
                $errors[] = [
                    'error' => 'database_error',
                    'message' => Craft::t('hommform', 'Failed to insert form data'),
                ];
            }
        } catch (\Throwable $th) {
            $errors[] = [
                'error' => 'database_error',
                'message' => Craft::t('hommform', 'Failed to save form data'),
            ];
        }

        return $errors;
    }

    public function getHtmlBody(array $payload, ?string $confirmation = null): string
    {
        $templatePath = HOMMForm::$plugin->getSettings()->htmlMailTemplatePath;

        return Craft::$app->view->renderTemplate(
            $templatePath ?? 'hommform/email/html',
            ['payload' => $payload, 'confirmation' => $confirmation],
            $templatePath ? View::TEMPLATE_MODE_SITE : View::TEMPLATE_MODE_CP
        );
    }

    public function getTextBody(array $payload, ?string $confirmation = null): string
    {
        $templatePath = HOMMForm::$plugin->getSettings()->textMailTemplatePath;

        return Craft::$app->view->renderTemplate(
            $templatePath ?? 'hommform/email/text',
            ['payload' => $payload, 'confirmation' => $confirmation],
            $templatePath ? View::TEMPLATE_MODE_SITE : View::TEMPLATE_MODE_CP
        );
    }

    public function send(string $receivers, string $replyto, string $subject, array $payload, string $confirmation): bool
    {
        $sent = Craft::$app->getMailer()->compose()
            ->setTo(explode(',', $receivers))
            ->setReplyTo($replyto)
            ->setSubject($subject)
            ->setTextBody($this->getTextBody($payload))
            ->setHtmlBody($this->getHtmlBody($payload))
            ->send();

        if (! $confirmation) {
            return $sent;
        }

        return Craft::$app->getMailer()->compose()
            ->setTo($replyto)
            ->setSubject($subject)
            ->setTextBody($this->getTextBody($payload, $confirmation))
            ->setHtmlBody($this->getHtmlBody($payload, $confirmation))
            ->send();
    }
}
