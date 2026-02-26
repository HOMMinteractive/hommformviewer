<?php
/**
 * HOMMFormViewer plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommformviewer;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use homm\hommformviewer\models\Settings;
use homm\hommformviewer\services\FormViewerService;
use homm\hommformviewer\services\FormService;
use homm\hommformviewer\variables\HOMMFormViewerVariable;
use yii\base\Event;

/**
 * Class HOMMFormViewer
 *
 * @author    Domenik Hofer
 * @package   HOMMFormViewer
 * @since     1.0.0
 *
 * @property  FormViewerService $formViewerService
 * @property  FormService $formService
 */
class HOMMFormViewer extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var HOMMFormViewer
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
            'formViewerService' => FormViewerService::class,
            'formService' => FormService::class,
        ]);

        $this->hasCpSection = HOMMFormViewer::$plugin->getSettings()->enableCpSection;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'homm\hommformviewer\console\controllers';
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['hommformviewer/download/<id>/<file>'] = 'hommformviewer/form-viewer/download';
                $event->rules['hommformviewer/export'] = 'hommformviewer/form-viewer/export';
                $event->rules['hommformviewer/delete'] = 'hommformviewer/form-viewer/delete';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['hommformviewer/submit'] = 'hommformviewer/form-viewer/submit';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('hommformviewer', HOMMFormViewerVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'hommformviewer',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('hommformviewer', 'Form requests');
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
            'hommformviewer/settings',
            ['settings' => $this->getSettings()]
        );
    }
}
