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

        $action->sf_page = new sfPhastPage($action);

		if(!$appControl->inProcess()){
			$appControl->inProcess(true);
            if('backend' == sfConfig::get('sf_app')){
                $this->backendBeforeExecute($action);
            }
			$appControl->beforeExecute($request, $action);
		}

		$appControl->preExecute($request, $action);
		$action->preExecute();

		$viewName = $action->execute($request);
		$action->postExecute();
		$appControl->postExecute($request, $action, $viewName);
		$appControl->afterExecute($request, $action, $viewName);

		if(!is_array($viewName) && $output = sfPhastUI::listen()){
			$viewName = $output;
		}

		if($request->isXmlHttpRequest() && is_array($viewName)){
			$response = $this->context['response'];
			//$response->setContentType('text/json');
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
        $action->asd = 'asd';

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
