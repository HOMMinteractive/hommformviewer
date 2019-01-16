<?php
	/**
 * hommjuicer plugin for Craft CMS 3.x
 *
 * Homm Juicer
 *
 * @link      homm.ch
 * @copyright Copyright (c) 2018 Domenik Hofer
 */

namespace homm\hommformviewer\controllers;

use homm\hommformviewer\Hommformviewer;

use Craft;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Domenik Hofer
 * @package   Hommjuicer
 * @since     0.0.1
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something', 'update-juicer','download-file'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/hommjuicer/default
     *
     * @return mixed
     */
    public function actionIndex()
    { 
	
		$table = $_GET['table'];
		
		
		$entries = \homm\hommformviewer\Hommformviewer::getInstance()->services->getEntries($table);


	   
	    return json_encode($entries);
    }
	
	 public function actionUpdateJuicer()
    {
       // $result = \homm\hommjuicer\Hommjuicer::getInstance()->services->updateJuicer();

       // return $result;
	   
	   return 'hi';
    }
	
	public function actionDownloadFile()
{
	
	$table = $_GET['table'];
	$entries = \homm\hommformviewer\Hommformviewer::getInstance()->services->getCsvData($table);
	
    // Grab your file content from wherever you have it stored. Here, it's just hard coded into a string.
    $contents = $entries;    

    // Call sendFile, giving it the name of the file to send, the contents and tell it to force a download to the browser.
	

  return Craft::$app->response->sendContentAsFile($contents, $table.'.csv', 'text/csv');
} 

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/hommjuicer/default/do-something
     *
     * @return mixed
     */
	
}
