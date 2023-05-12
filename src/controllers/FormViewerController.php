<?php
/**
 * HOMMFormViewer plugin for Craft CMS 4.x
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

        $csv = '';
        foreach ($data as $item) {
            $csv .= implode(';', $item) . PHP_EOL;
        }

        return Craft::$app->response->sendContentAsFile($csv, $form . '.csv', 'text/csv');
    }
}
