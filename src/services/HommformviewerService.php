<?php
/**
 * hommformviewer plugin for Craft CMS 3.x
 *
 * Show Form-Entries directly from DB
 *
 * @link      http://www.homm.ch
 * @copyright Copyright (c) 2019 Domenik Hofer
 */

namespace homm\hommformviewer\services;

use homm\hommformviewer\Hommformviewer;

use Craft;
use craft\base\Component;
	use craft\db\Query;

/**
 * HommformviewerService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Domenik Hofer
 * @package   Hommformviewer
 * @since     1.0.0
 */
class HommformviewerService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Hommformviewer::$plugin->hommformviewerService->exampleService()
     *
     * @return mixed
     */
    public function getTables()
    {
   
        // Check our Plugin's settings for `someAttribute`
        $tables = explode(',', Hommformviewer::$plugin->getSettings()->someAttribute);

        return $tables;
    }
	
	 public function getEntries($table, $limit = 100)
    {
   
       $results = (new Query()) 
			->select(['*']) 
			->from([$table])
			->limit($limit)
			->all();

        return $results;
    }
	
	public function getCsvData($table)
    {
	$entries = $this->getEntries($table, 9999999);
	$string = '';
	
	foreach($entries[0] as $key2 => $value2){
	$string .= $key2.';';
		
		}
	$string .= PHP_EOL	;
	
	foreach($entries as $key => $value){
	foreach($value as $key2 => $value2){
	$string .= $value2.';';
		
		}
	$string .= PHP_EOL	;
		}
	
	return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
	
	}
}
