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

    public function recaptcha(array $options = []): string
    {
        $uniqid = uniqid();
        $secret = App::parseEnv(HOMMForm::$plugin->getSettings()->recaptchaSecret);
        $siteKey = App::parseEnv(HOMMForm::$plugin->getSettings()->recaptchaSiteKey);

        if (! $secret || ! $siteKey) {
            return '';
        }

        $options = json_encode($options);

        return <<<HTML
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-{$uniqid}">

            <script src="https://www.google.com/recaptcha/api.js?onload=onloadRecaptcha&render={$siteKey}" async defer></script>
            <script>
                function onloadRecaptcha() {
                    grecaptcha.ready(function () {
                        grecaptcha.execute('{$siteKey}', {$options}).then(function (token) {
                            document.querySelectorAll('[name="g-recaptcha-response"]').forEach(function (el) {
                                el.value = token;
                            });
                        });
                    });
                }

                /* run every 30 seconds to prevent timeout issues */
                setInterval(onloadRecaptcha, 30000);
            </script>
        HTML;
    }

    public function validateReCaptcha(string $response): bool
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
                    'response' => $response,
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

        $recaptchaResponse = $request->getBodyParam('g-recaptcha-response');
        unset($payload['g-recaptcha-response']);

        $confirmation = $request->getBodyParam('confirmation');
        unset($payload['confirmation']);

        $submissionId = $dateCreated->format('Ymd-His') . '-' . bin2hex(random_bytes(4));
        foreach (array_keys($_FILES) as $i => $key) {
            $file = UploadedFile::getInstancesByName($key)[0] ?? null;

            if (! ($file instanceof UploadedFile)) {
                continue;
            }

            $payload[$key] = [
                'file' => $file,
                'name' => $this->slugify($file->baseName) . '-' . ($i + 1) . '.' . $file->extension,
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
            $recaptchaResponse,
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
                $errors[] = HOMMForm::$plugin->errorService->fileTypeNotAllowed();

                continue;
            }

            if (! FileHelper::createDirectory($folderpath)) {
                $errors[] = HOMMForm::$plugin->errorService->folderCreationFailed();

                continue;
            }

            if (! $file->saveAs($filepath)) {
                $errors[] = HOMMForm::$plugin->errorService->fileUploadFailed();

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
                $errors[] = HOMMForm::$plugin->errorService->databaseError();
            }
        } catch (\Throwable $th) {
            $errors[] = HOMMForm::$plugin->errorService->databaseError();
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

    public function send(string $receivers, string $replyto, string $subject, array $payload, ?string $confirmation = null): bool
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
