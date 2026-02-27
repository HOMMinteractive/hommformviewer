<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use homm\hommform\models\Settings;
use homm\hommform\services\ViewerService;
use homm\hommform\services\SubmitService;
use homm\hommform\variables\HOMMFormVariable;
use yii\base\Event;

/**
 * Class HOMMForm
 *
 * @author    Domenik Hofer
 * @package   HOMMForm
 * @since     1.0.0
 *
 * @property  ViewerService $viewerService
 * @property  SubmitService $SubmitService
 */
class HOMMForm extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var HOMMForm
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.1.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool
     */
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'viewerService' => ViewerService::class,
            'submitService' => SubmitService::class,
        ]);

        $this->hasCpSection = HOMMForm::$plugin->getSettings()->enableCpSection;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'homm\hommform\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['hommform/download/<id>/<file>'] = 'hommform/viewer/download';
                $event->rules['hommform/export'] = 'hommform/viewer/export';
                $event->rules['hommform/delete'] = 'hommform/viewer/delete';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['hommform/submit'] = 'hommform/submit/index';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('hommform', HOMMFormVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'hommform',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('hommform', 'Form requests');
        return $item;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'hommform/settings',
            ['settings' => $this->getSettings()]
        );
    }
}
