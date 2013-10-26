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

}
