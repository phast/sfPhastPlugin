<?php

class sfPhastAppControl{

	protected $inProcess;

	public function inProcess($toggle = false){
		if($toggle) $this->inProcess = true;
		return $this->inProcess;
	}

	public function beforeExecute($request, $action){

	}

	public function afterExecute($request, $action, $viewName){

	}

	public function preExecute($request, $action){

	}

	public function postExecute($request, $action, $viewName){

	}

}


function sfPhastUI(){

}