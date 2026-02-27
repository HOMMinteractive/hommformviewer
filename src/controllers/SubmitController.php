<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform\controllers;

use Craft;
use craft\web\Controller;
use homm\hommform\HOMMForm;
use yii\base\Response;

/**
 * @author    Domenik Hofer
 * @package   HOMMForm
 * @since     0.0.1
 */
class SubmitController extends Controller
{
    private function sendResponse(array $errors = []): Response
    {
        $request = Craft::$app->getRequest();

        if ($request->getAcceptsJson() || $request->getIsAjax()) {
            return $this->asJson([
                'success' => empty($errors),
                'errors' => $errors,
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $errors = [];

        $this->requirePostRequest();

        $dateCreated = new \DateTime();

        [
            $formId,
            $receivers,
            $subject,
            $replyto,
            $recaptcha_response,
            $ip,
            $payload,
            $confirmation,
        ] = HOMMForm::$plugin->submitService->parseBodyParams(Craft::$app->getRequest(), $dateCreated);

        if (! HOMMForm::$plugin->submitService->validateReCaptcha($recaptcha_response)) {
            $errors[] = [
                'error' => 'recaptcha_verification_failed',
                'message' => Craft::t('hommform', 'Failed to verify reCAPTCHA response'),
            ];

            return $this->sendResponse($errors);
        }

        $errors = HOMMForm::$plugin->submitService->save(
            $formId,
            $receivers,
            $replyto,
            $subject,
            $payload,
            $ip,
            $dateCreated,
            $recaptcha_response
        );

        if (! empty($errors)) {
            return $this->sendResponse($errors);
        }

        $sent = HOMMForm::$plugin->submitService->send(
            $receivers,
            $replyto,
            $subject,
            $payload,
            $confirmation
        );

        if (! $sent) {
            $errors[] = [
                'error' => 'email_sending_failed',
                'message' => Craft::t('hommform', 'Failed to send email notification'),
            ];
        }

        return $this->sendResponse($errors);
    }
}
