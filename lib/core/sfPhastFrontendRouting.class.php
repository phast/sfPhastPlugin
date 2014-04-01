<?php

class sfPhastFrontendRouting extends sfPatternRouting
{

	public function parse($url){

		$context = sfContext::getInstance();
		$request = $context->getRequest();

		$pathinfo = $request->getPathInfoArray();

		if(substr($url, -1) !== '/'){
			$context->getController()->redirect(
				(sfConfig::get('sf_no_script_name') ? '' : $pathinfo['SCRIPT_NAME'])
				. $url . '/'
				. ($pathinfo['QUERY_STRING'] ? '?' . $pathinfo['QUERY_STRING'] : ''));
		}
				
		$tokens = explode('/', $this->normalizeUrl($url));
		$last_token = '/';
		$keys = array();
		foreach($tokens as $token){
			if(!$token) continue;
			$last_token .= $token . '/';		
			$keys[] = $last_token;
		}
		if($keys){
			$keys = array_reverse($keys);
		}else{
			$keys[] = '/';
		}

        $pages = (new PhastPageQuery)->forRouting($keys)->find()->getData();
		usort($pages, function($a, $b) {
			$a = $a->getURI();
			$b = $b->getURI();
			if (mb_strlen($a) < mb_strlen($b)) { return 1; } elseif (mb_strlen($a) == mb_strlen($b)) { return 0; } else { return -1; }
		});
		
		$yaml = new sfYamlParser();
		
		foreach($pages as $key => $page){			
			$pattern = $page->getURI() . ($page->getRoutePattern() ? $page->getRoutePattern() : '');
			if(substr($pattern, -1) !== '/') $pattern .= '/';
			$options = array('module' => 'page', 'action' => 'static');
			if($page->getRouteOptions()){
				$options['action'] = 'index';
				if($route_options = $yaml->parse('parse: {'. $page->getRouteOptions() . '}')){
					$options = array_merge($options, $route_options['parse']);
				}
				
			}
            $requirements = $page->getRouteRequirements() ? $yaml->parse('parse: {'. $page->getRouteRequirements() . '}')['parse'] : [];
            $route = new sfRoute($pattern, $options, $requirements);
			$route->page = $page;
			$this->appendRoute('sf_page_route_'.$key, $route);
		}

		return parent::parse($url);
	}

}