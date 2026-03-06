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
use craft\helpers\StringHelper;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use homm\hommform\HOMMForm;
use homm\hommform\models\Submission;
use yii\web\Request;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
 */
class SubmitService extends Component
{
    private const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private function getAllowedFileTypes(): array
    {
        $settings = HOMMForm::$plugin->getSettings();

        if (! is_array($settings->allowedFileTypes)) {
            return explode(',', $settings->allowedFileTypes);
        }

        return $settings->allowedFileTypes;
    }

    /**
     * Returns the HTML/JS snippet required to render an invisible reCAPTCHA
     * token input.  Options are passed through to the client‑side
     * &#64;grecaptcha.execute call.
     *
     * An empty string is returned when the site key/secret are not configured.
     *
     * @param array $options additional parameters for the JS API, e.g. ['action' => 'submit']
     */
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

    /**
     * Verify a reCAPTCHA token.
     *
     * @param string $response the value from the g-recaptcha-response field
     * @return bool whether the token is valid and meets the score threshold
     */
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

    public function parseSubmission(Request $request): Submission
    {
        $dateCreated = new DateTime();
        $submission = new Submission();
        $submission->dateCreated = $dateCreated;

        $bodyParams = $request->getBodyParams();

        // basic scalar values we care about
        $submission->formId = $request->getBodyParam('formId');
        $submission->receivers = $request->getValidatedBodyParam('receivers');
        $submission->subject = $request->getValidatedBodyParam('subject');

        $replyToField = $request->getValidatedBodyParam('replyto');
        $submission->replyto = $bodyParams[$replyToField] ?? null;

        $submission->ip = $request->getUserIP();
        $submission->recaptchaResponse = $request->getBodyParam('g-recaptcha-response');
        $submission->confirmation = $request->getBodyParam('confirmation');

        // remove the elements that belong in the payload instead of the
        // metadata object
        $discard = [
            'CRAFT_CSRF_TOKEN',
            'redirect',
            'formId',
            'receivers',
            'subject',
            'replyto',
            'g-recaptcha-response',
            'confirmation',
        ];
        foreach ($discard as $key) {
            unset($bodyParams[$key]);
        }

        // Attach upload metadata
        $submissionId = $dateCreated->format('Ymd-His') . '-' . bin2hex(random_bytes(4));

        foreach (array_keys($_FILES) as $i => $key) {
            $file = UploadedFile::getInstancesByName($key)[0] ?? null;
            if (! ($file instanceof UploadedFile)) {
                continue;
            }

            $bodyParams[$key] = [
                'file' => $file,
                'name' => StringHelper::slugify($file->baseName) . '-' . ($i + 1) . '.' . $file->extension,
                'folder' => $submissionId,
                'storage' => HOMMForm::$plugin->getSettings()->storagePath,
            ];
        }

        // attempt to decode JSON strings
        foreach ($bodyParams as $key => $item) {
            if (is_string($item) && json_validate($item)) {
                $bodyParams[$key] = json_decode($item, true);
            }
        }

        $submission->payload = $bodyParams;
        return $submission;
    }

    /**
     * Persist a submission to the database and move any uploaded files to the
     * configured storage location.
     *
     * @param Submission $submission
     * @return array An array of error structures. Empty when the operation succeeded.
     */
    public function save(Submission $submission): array
    {
        $errors = [];
        $allowed = array_map('strtolower', $this->getAllowedFileTypes());

        foreach ($submission->payload as $key => $value) {
            $file = $value['file'] ?? null;
            $filepath = isset($value['folder'], $value['name'], $value['storage'])
                ? Craft::getAlias($value['storage'] . '/' . $value['folder'] . '/' . $value['name'])
                : null;
            $folderpath = $filepath ? dirname($filepath) : null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! in_array(strtolower($file->extension), $allowed, true)) {
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

            // remove the UploadedFile instance so the payload can be JSON encoded
            unset($submission->payload[$key]['file']);
        }

        if (! empty($errors)) {
            return $errors;
        }

        try {
            $insert = (new Query())
                ->createCommand()
                ->insert('{{%homm_form_submissions}}', [
                    'formId' => $submission->formId,
                    'receivers' => $submission->receivers,
                    'replyto' => $submission->replyto,
                    'subject' => $submission->subject,
                    'payload' => $submission->payload,
                    'ip' => $submission->ip,
                    'dateCreated' => $submission->dateCreated->format('Y-m-d H:i:s'),
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

    public function send(string $receivers, ?string $replyto, string $subject, array $payload, ?string $confirmation = null): bool
    {
        // send notification to configured recipients first
        $message = Craft::$app->getMailer()->compose()
            ->setTo(array_filter(explode(',', $receivers)))
            ->setSubject($subject)
            ->setTextBody($this->getTextBody($payload))
            ->setHtmlBody($this->getHtmlBody($payload));

        if ($replyto) {
            $message->setReplyTo($replyto);
        }

        $sent = $message->send();
        if (! $sent) {
            return false;
        }

        // optionally send confirmation to sender
        if ($confirmation && $replyto) {
            return Craft::$app->getMailer()->compose()
                ->setTo($replyto)
                ->setSubject($subject)
                ->setTextBody($this->getTextBody($payload, $confirmation))
                ->setHtmlBody($this->getHtmlBody($payload, $confirmation))
                ->send();
        }

        return true;
    }
}
