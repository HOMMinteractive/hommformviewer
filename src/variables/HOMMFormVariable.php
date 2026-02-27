<?php
/**
 * HOMM Social Feed plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform\variables;


use homm\hommform\HOMMForm;

/**
 * @author    Domenik Hofer
 * @package   Hommjuicer
 * @since     0.0.1
 */
class HOMMFormVariable
{
    /**
     * Get the form types.
     *
     * @return string[]
     */
    public function forms(): array
    {
        return HOMMForm::$plugin->viewerService->getForms();
    }

    public function entries(string $form): array
    {
        return HOMMForm::$plugin->viewerService->entries($form);
    }
}
