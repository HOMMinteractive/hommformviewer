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
        $filePath = Craft::getAlias(HOMMForm::$plugin->getSettings()->storagePath) . '/' . $id . '/' . $file;

        if (! file_exists($filePath)) {
            throw new \yii\web\NotFoundHttpException(Craft::t('hommform', 'File not found.'));
        }

        return Craft::$app->response->sendFile($filePath, $file, ['inline' => true]);
    }

    public function actionExport()
    {
        $request = Craft::$app->getRequest();

        $form = $request->getQueryParam('formId');
        $entries = HOMMForm::$plugin->viewerService->entries($form);

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
        HOMMForm::$plugin->viewerService->delete($form);

        return $this->redirectToPostedUrl();
    }
}
