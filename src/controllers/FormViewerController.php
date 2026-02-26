<?php
/**
 * HOMMFormViewer plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommformviewer\controllers;

use Craft;
use craft\web\Controller;
use homm\hommformviewer\HOMMFormViewer;
use yii\base\Response;

/**
 * @author    Domenik Hofer
 * @package   HOMMFormViewer
 * @since     0.0.1
 */
class FormViewerController extends Controller
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

    public function actionSubmit()
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
        ] = HOMMFormViewer::$plugin->formService->parseBodyParams(Craft::$app->getRequest(), $dateCreated);

        if (! HOMMFormViewer::$plugin->formService->validateReCaptcha($recaptcha_response)) {
            $errors[] = [
                'error' => 'recaptcha_verification_failed',
                'message' => Craft::t('hommformviewer', 'Failed to verify reCAPTCHA response'),
            ];

            return $this->sendResponse($errors);
        }

        $errors = HOMMFormViewer::$plugin->formService->save(
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

        $sent = HOMMFormViewer::$plugin->formService->send(
            $receivers,
            $replyto,
            $subject,
            $payload,
            $confirmation
        );

        if (! $sent) {
            $errors[] = [
                'error' => 'email_sending_failed',
                'message' => Craft::t('hommformviewer', 'Failed to send email notification'),
            ];
        }

        return $this->sendResponse($errors);
    }

    public function actionDownload(string $id, string $file)
    {
        $filePath = Craft::getAlias(HOMMFormViewer::$plugin->getSettings()->storagePath) . '/' . $id . '/' . $file;

        if (! file_exists($filePath)) {
            throw new \yii\web\NotFoundHttpException(Craft::t('hommformviewer', 'File not found.'));
        }

        return Craft::$app->response->sendFile($filePath, $file, ['inline' => true]);
    }

    public function actionExport()
    {
        $request = Craft::$app->getRequest();

        $form = $request->getQueryParam('formId');
        $entries = HOMMFormViewer::$plugin->formViewerService->entries($form);

        $context = fopen('php://temp', 'r+');

        // First item is the header
        if (! empty($entries)) {
            $headers = array_shift($entries);
            fputcsv($context, $headers);

            // Write data rows
            foreach ($entries as $item) {
                $rowData = [];
                foreach ($headers as $header) {
                    $value = $item[$header] ?? '';

                    // Flatten multidimensional arrays to JSON
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }

                    $rowData[] = $value;
                }
                fputcsv($context, $rowData);
            }
        }

        rewind($context);
        $csv = stream_get_contents($context);
        fclose($context);

        return Craft::$app->response->sendContentAsFile($csv, $form . '.csv', ['mimeType' => 'text/csv']);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $form = $request->getBodyParam('form');
        HOMMFormViewer::$plugin->formViewerService->delete($form);

        return $this->redirectToPostedUrl();
    }
}
