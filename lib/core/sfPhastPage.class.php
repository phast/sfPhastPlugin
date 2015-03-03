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

    public function getMenuPages($uri = null){
        $page = $uri === null ? $this->page : ($uri instanceof Page ? $uri : PhastPagePeer::retrieveByURI($uri));
        return $page->getChildren(1, PageQuery::create()->forProd());
    }

    public function isContained(Page $page){
        if($page->getUri()){
            if($page->getUri()[0] == '^'){
                return strpos($this->page->getUrl(), $page->getUrl()) === 0;
            }else{
                $parents = $this->page->getParents(Page::PARENT_PKS);
                $parents[] = $this->page->getId();
                return in_array($page->getId(), $parents);
            }
        }
        return false;
    }

    public function addChain($name, $link = null){
        $name = preg_replace('#\< *br */?\ *>#i', '', $name);

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