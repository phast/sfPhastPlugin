<?php


class sfPhastExecutionFilter extends sfExecutionFilter
{

	protected function executeAction($action)
	{

		$request = $this->context->getRequest();

	    if($this->context->has('appControl')){
		    $appControl = $this->context->get('appControl');
	    }else{
		    $this->context->set('appControl', $appControl = new sfAppControl());
	    }


		if(!$appControl->inProcess()){
			$appControl->inProcess(true);

            $action->sf_page = $sf_page = new sfPhastPage($action);
            $action->sf_settings = $sf_settings = new sfPhastSettings();


            $this->context->getEventDispatcher()->connect('template.filter_parameters', function(sfEvent $event, $parameters) use ($sf_page, $sf_settings){
                $parameters['sf_page']  = $sf_page;
                $parameters['sf_settings']  = $sf_settings;
                return $parameters;
            });


            if('backend' == sfConfig::get('sf_app')){
                $this->backendBeforeExecute($action);
            }
			$appControl->beforeExecute($request, $action);
		}

        $actionStack = $this->context->getActionStack();
        if(($actionStackSize = $actionStack->getSize()) > 1){
            $lastAction = $actionStack->getEntry($actionStackSize-2)->getActionInstance();
            $action->getVarHolder()->add($lastAction->getVarHolder()->getAll());
        }

		$appControl->preExecute($request, $action);
		$action->preExecute();

        try{
            $viewName = $action->execute($request);
        } catch (sfPhastFormException $e){
            $viewName = ['error' => $e->getErrors()];
        }

        $action->postExecute();
		$appControl->postExecute($request, $action, $viewName);
		$appControl->afterExecute($request, $action, $viewName);

		if(!is_array($viewName) && $output = sfPhastUI::listen()){
			$viewName = $output;
		}

		if($request->isXmlHttpRequest() && is_array($viewName)){
			$response = $this->context['response'];
			$response->setContent(json_encode($viewName));
			return sfView::NONE;
		}

		if(is_array($viewName)){
			$action->forward404();
		}

		return null === $viewName ? sfView::SUCCESS : $viewName;

	}

    protected function backendBeforeExecute($action){
        $request = $action->getRequest();
        $user = $action->getUser();

        $action->modules = sfPhastAdmin::getModules();
        
        if(!$user->hasCredential('cp_access')){
            ($action->getModuleName() == 'sfPhastAdmin' && $action->getActionName() == 'auth') or $action->forward('sfPhastAdmin', 'auth');

        }else if($action->getModuleName() != 'sfPhastAdmin'){

            if(
                !$module = sfPhastAdmin::getModule($action->getModuleName()) or
                isset($module['credential']) && !$user->hasCredential($module['credential'])
            ){
                $action->forward('sfPhastAdmin', 'forbidden');
            }

        }
    }

}
