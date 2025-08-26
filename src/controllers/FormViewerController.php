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

/**
 * @author    Domenik Hofer
 * @package   HOMMFormViewer
 * @since     0.0.1
 */
class FormViewerController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionExport()
    {
        $request = Craft::$app->getRequest();

        $form = $request->getQueryParam('form');
        $data = HOMMFormViewer::$plugin->formViewerService->getData($form);
        $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');

        $csv = '';
        foreach ($data as $key => $row) {
            if ($key === array_key_first($data)) {
                $csv .= implode(';', $row) . PHP_EOL;
            } else {
                $csv .= '"' . implode('";"', $row) . '"' . PHP_EOL;
            }
        }

        return Craft::$app->response->sendContentAsFile($csv, $form . '.csv', ['mimeType' => 'text/csv']);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $form = $request->getBodyParam('form');
        HOMMFormViewer::$plugin->formViewerService->deleteData($form);

        return $this->redirectToPostedUrl();
    }
}
