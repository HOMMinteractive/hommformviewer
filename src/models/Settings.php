<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform\models;


use craft\base\Model;

/**
 * HOMM Form Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Domenik Hofer
 * @package   HOMMForm
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public bool $enableCpSection = true;

    public ?string $storagePath = '@storage/form';

    public ?string $recaptchaSiteKey = null;

    public ?string $recaptchaSecret = null;

    public float $recaptchaScoreThreshold = 0.5;

    public ?string $htmlMailTemplatePath = null;

    public ?string $textMailTemplatePath = null;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ['enableCpSection', 'boolean'],
            ['enableCpSection', 'default', 'value' => true],

            ['storagePath', 'string'],
            ['storagePath', 'default', 'value' => '@storage/form'],

            ['recaptchaSiteKey', 'string'],
            ['recaptchaSecret', 'string'],

            ['recaptchaScoreThreshold', 'number', 'min' => 0, 'max' => 1],
            ['recaptchaScoreThreshold', 'default', 'value' => 0.5],

            ['htmlMailTemplatePath', 'string'],
            ['htmlMailTemplatePath', 'default', 'value' => null],

            ['textMailTemplatePath', 'string'],
            ['textMailTemplatePath', 'default', 'value' => null],
        ];
    }
}
