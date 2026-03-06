<?php
/**
 * HOMM Social Feed plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2026 HOMM interactive
 */

namespace homm\hommform\variables;


use homm\hommform\HOMMForm;

/**
 * @author    Benjamin Ammann
 * @package   HOMMForm
 * @since     4.0.0
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

    public function recaptcha(): string
    {
        return HOMMForm::$plugin->submitService->recaptcha();
    }
}
