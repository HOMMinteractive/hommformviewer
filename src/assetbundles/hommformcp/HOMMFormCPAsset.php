<?php
/**
 * HOMMForm plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommform\assetbundles\hommformcp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Domenik Hofer
 * @package   HOMMForm
 * @since     1.0.0
 */
class HOMMFormCPAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@homm/hommform/assetbundles/hommformcp/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/HOMMFormCP.js',
        ];

        $this->css = [
            'css/HOMMFormCP.css',
        ];

        parent::init();
    }
}
