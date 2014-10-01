<?php

class sfPhastPage implements ArrayAccess{

    protected static $instance;

    protected $action;
    protected $page;
    protected $chain;

    public function __construct(sfAction $action){
        $route = $action->getRoute();
        $this->action = $action;
        if(isset($route->page)){
            $this->page = $route->page;
        }
        static::$instance = $this;
    }

    public function setPage(Page $page){
        $this->page = $page;
    }

    /**
     * @return sfPhastPage
     */
    public static function getInstance(){
        return static::$instance;
    }

    public function getMenuPages($uri){
        return PhastPagePeer::retrieveByURI($uri)
            ->getChildren(1, PhastPageQuery::create()->forProd());
    }

    public function addChain($name, $link = null){
        $this->chain[] = [
            'name' => $name,
            'link' => $link
        ];
    }

    public function getChain(){
        return $this->chain;
    }
	
    public function getSetting(){
        return call_user_func_array(['SettingPeer', 'retrieveByName'], func_get_args());
    }

    public function __call($method, $args){
        return call_user_func_array([$this->page, $method], $args);
    }
	
    public function offsetGet($var){
        return $this->action->getVarHolder()->get($var);
    }

    public function offsetExists($var){
        return $this->action->getVarHolder()->has($var);
    }

    public function offsetSet($var, $value){
        return $this->action->getVarHolder()->set($var, $value);
    }

    public function offsetUnset($var){
        return $this->action->getVarHolder()->remove($var);
    }

}

function geturl($page, $absolute = false){
    return sfPhastUtils::geturl($page, $absolute);
}

function error($message, $context = null){
    $exception = new sfPhastException($message);
    $exception->setContext($context);
    throw $exception;
}