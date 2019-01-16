<?php
	/**
		* hommjuicer plugin for Craft CMS 3.x
		*
		* Homm Juicer
		*
		* @link      homm.ch
		* @copyright Copyright (c) 2018 Domenik Hofer
	*/
	
	namespace homm\hommformviewer\variables;
	
	use homm\hommformviewer\Hommformviewer;
	
	use Craft;

	
	/**
		* hommjuicer Variable
		*
		* Craft allows plugins to provide their own template variables, accessible from
		* the {{ craft }} global variable (e.g. {{ craft.hommjuicer }}).
		*
		* https://craftcms.com/docs/plugins/variables
		*
		* @author    Domenik Hofer
		* @package   Hommjuicer
		* @since     0.0.1
	*/
	class HommformviewerVariable 
	{
		// Public Methods
		// =========================================================================
		
		/**
			* Whatever you want to output to a Twig template can go into a Variable method.
			* You can have as many variable functions as you want.  From any Twig template,
			* call it like this:
			*
			*     {{ craft.hommjuicer.exampleVariable }}
			*
			* Or, if your variable requires parameters from Twig:
			*
			*     {{ craft.hommjuicer.exampleVariable(twigValue) }}
			*
			* @param null $optional
			* @return string
		*/
		public function tables()
		{
			
			$tables = \homm\hommformviewer\Hommformviewer::getInstance()->services->getTables();
			
			
			return $tables;
		}
	}
