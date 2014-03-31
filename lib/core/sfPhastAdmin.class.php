<?php

class sfPhastAdmin
{
	static $modules;
	static public function getModules($clear_cache = false){
		if(!file_exists(sfConfig::get('sf_app_cache_dir').'/modules.php') || $clear_cache){
			$modules = array();

			foreach(sfFinder::type('dir')->maxdepth(0)->relative()->in(sfConfig::get('sf_app_module_dir')) as $module){
				$modules[$module] = sfYamlConfigHandler::parseYaml(sfConfig::get('sf_app_module_dir').'/'.$module.'/config.yml');
				if(!isset($modules[$module]['position']))
					$modules[$module]['position'] = 0;
			}

			uasort($modules, function($a, $b){
				if($a['position'] == $b['position']) return 0;
				return ($a['position'] < $b['position']) ? -1 : 1;
			});

			$w = fopen(sfConfig::get('sf_app_cache_dir').'/modules.php','w');
			fwrite($w, "<?php\nreturn array(");

			foreach($modules as $module => $options){
				ob_start();
				var_export($options);
				$options = ob_get_contents();
				ob_end_clean();
				fwrite($w, "\n'{$module}' => {$options},\n");
			}

			fwrite($w, ');');
			fclose($w);
		}

		return null !== static::$modules ? static::$modules : static::$modules = include(sfConfig::get('sf_app_cache_dir').'/modules.php');
	}

	static public function getModule($name){
		if(null === static::$modules)
			static::getModules();

		return isset(static::$modules[$name]) ? static::$modules[$name] : null;
	}

}
