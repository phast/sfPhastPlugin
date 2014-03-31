<?php

class sfPhastPluginConfiguration extends sfPluginConfiguration
{
	public function initialize()
	{
		if('backend' == sfConfig::get('sf_app')){
			$modules = sfConfig::get('sf_enabled_modules', array());
			$modules[] = 'sfPhastAdmin';
			sfConfig::set('sf_enabled_modules', $modules);
            sfConfig::set('sf_app_template_dir', sfConfig::get('sf_plugins_dir') . '/sfPhastPlugin/modules/sfPhastAdmin/templates');
        }

        $helpers = sfConfig::get('sf_standard_helpers', array());
        $helpers[] = 'Phast';

        sfConfig::set('sf_standard_helpers', $helpers);
        sfConfig::set('mod_global_partial_view_class', 'sfPhastTwig');

        require_once sfConfig::get('sf_twig_lib_dir') . '/Twig/Autoloader.php';
        Twig_Autoloader::register();

	}

	public function setup()
	{
	}

	public function configure()
	{
	}

}

function debug($data){
    $data = print_r($data, true);
    print PHP_SAPI == 'cli' ? $data : "<pre>{$data}</pre>";
}