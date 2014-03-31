<?php

class sfPhastPage{

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

    /**
     * @return sfPhastPage
     */
    public static function getInstance(){
        return static::$instance;
    }

    public function getMenuPages($uri){
        return PagePeer::retrieveByURI($uri)
            ->getChildren(1, PageQuery::create()->forProd());
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

    public function __call($method, $args)
    {
        return call_user_func_array([$this->page, $method], $args);
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