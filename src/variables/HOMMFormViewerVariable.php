<?php
/**
 * HOMM Social Feed plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommformviewer\variables;


use homm\hommformviewer\HOMMFormViewer;

/**
 * @author    Domenik Hofer
 * @package   Hommjuicer
 * @since     0.0.1
 */
class HOMMFormViewerVariable
{
    /**
     * Get the form types.
     *
     * @return string[]
     */
    public function forms(): array
    {
        return HOMMFormViewer::$plugin->formViewerService->getForms();
    }

    public function data(string $form = null): array
    {
        return HOMMFormViewer::$plugin->formViewerService->getData($form);
    }
}
