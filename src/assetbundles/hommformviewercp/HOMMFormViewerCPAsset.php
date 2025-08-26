<?php
/**
 * HOMMFormViewer plugin for Craft CMS 5.x
 *
 * Show form requests in the control panel
 *
 * @link      https://github.com/HOMMinteractive
 * @copyright Copyright (c) 2019 HOMM interactive
 */

namespace homm\hommformviewer\assetbundles\hommformviewercp;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Domenik Hofer
 * @package   HOMMFormViewer
 * @since     1.0.0
 */
class HOMMFormViewerCPAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@homm/hommformviewer/assetbundles/hommformviewercp/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/HOMMFormViewerCP.js',
        ];

        $this->css = [
            'css/HOMMFormViewerCP.css',
        ];

        parent::init();
    }
}
