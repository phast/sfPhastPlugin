<?php

class sfPhastPluginConfiguration extends sfPluginConfiguration
{
	public function initialize()
	{
        sfConfig::set('app_sfImageTransformPlugin_mime_type', [
            'auto_detect' => true,
            'library' => 'gd_mime_type'
        ]);

		if('backend' == sfConfig::get('sf_app')){
			$modules = sfConfig::get('sf_enabled_modules', array());
			$modules[] = 'sfPhastAdmin';
			sfConfig::set('sf_enabled_modules', $modules);
            sfConfig::set('sf_app_template_dir', sfConfig::get('sf_plugins_dir') . '/sfPhastPlugin/modules/sfPhastAdmin/templates');
        }

        $helpers = sfConfig::get('sf_standard_helpers', array());
        $helpers[] = 'Phast';

        sfConfig::set('sf_standard_helpers', $helpers);
        sfConfig::set('mod_global_partial_view_class', 'sfTwig');
        sfConfig::set('sf_propel_generator_path', sfConfig::get('sf_root_dir') . '/lib/vendor/propel/propel1/generator/lib');

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

function geturl($page, $absolute = false){
    return sfPhastUtils::geturl($page, $absolute);
}

function error($context = null, $message = null){
    return sfPhastUtils::error($context, $message);
}

function flushError($context = null, $message = null){
    if($context){
        if(null === $message){
            $message = $context;
            $context = '#';
        }
        sfPhastUtils::error($context, $message);
    }

    return sfPhastUtils::error();
}
