<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2026 HOMM interactive
 */

namespace homm\hommform\controllers;

use Craft;
use craft\web\Controller;
use homm\hommform\HOMMForm;
use yii\base\Response;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
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

        // parse the incoming request into a strongly‑typed object
        $submission = HOMMForm::$plugin->submitService->parseSubmission(Craft::$app->getRequest());

        // verify the captcha first – if it fails we bail out immediately
        if (! HOMMForm::$plugin->submitService->validateReCaptcha($submission->recaptchaResponse)) {
            $errors[] = HOMMForm::$plugin->errorService->recaptchaValidationFailed();
            return $this->sendResponse($errors);
        }

        // persist data + handle any uploads
        $errors = HOMMForm::$plugin->submitService->save($submission);
        if (! empty($errors)) {
            return $this->sendResponse($errors);
        }

        // send notifications (and confirmation mail if requested)
        $sent = HOMMForm::$plugin->submitService->send(
            $submission->receivers,
            $submission->replyto,
            $submission->subject,
            $submission->payload,
            $submission->confirmation
        );

        if (! $sent) {
            $errors[] = HOMMForm::$plugin->errorService->sendEmailFailed();
        }

        return $this->sendResponse($errors);
    }
}
