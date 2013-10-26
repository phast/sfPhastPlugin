<?php

class sfPhastPluginConfiguration extends sfPluginConfiguration
{
	public function initialize()
	{

		if('backend' == sfConfig::get('sf_app')){
			$modules = sfConfig::get('sf_enabled_modules', array());
			$modules[] = 'sfPhastAdmin';
			sfConfig::set('sf_enabled_modules', $modules);
		}

	}

	public function setup()
	{
	}

	public function configure()
	{
	}

}
