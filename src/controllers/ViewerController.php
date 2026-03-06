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

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
 */
class ViewerController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionDownload(string $id, string $file)
    {
        // prevent path traversal by stripping directory separators from the
        // uploaded file name and ensuring the id is alphanumeric/hyphen.
        $id = preg_replace('/[^a-zA-Z0-9\-]/', '', $id);
        $file = basename($file);

        $filePath = Craft::getAlias(HOMMForm::$plugin->getSettings()->storagePath) . '/' . $id . '/' . $file;

        if (! is_file($filePath)) {
            throw new \yii\web\NotFoundHttpException(Craft::t('hommform', 'File not found.'));
        }

        return Craft::$app->response->sendFile($filePath, $file, ['inline' => true]);
    }

    public function actionExport()
    {
        $request = Craft::$app->getRequest();

        $form = $request->getQueryParam('formId');
        $csv = HOMMForm::$plugin->viewerService->exportCsv($form);

        return Craft::$app->response->sendContentAsFile($csv, $form . '.csv', ['mimeType' => 'text/csv']);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $form = $request->getBodyParam('form');
        HOMMForm::$plugin->viewerService->delete($form);

        return $this->redirectToPostedUrl();
    }
}
