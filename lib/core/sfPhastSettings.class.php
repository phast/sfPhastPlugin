<?php

class sfPhastSettings implements ArrayAccess{

    protected static $instance;

    public function __construct(){
        static::$instance = $this;
    }

    public function offsetGet($var){
        return $this->get($var);
    }

    public function offsetExists($var){
	    return $this->get($var);
    }

    public function offsetSet($var, $value){
        return new sfPhastException('Method unavailable');
    }

    public function offsetUnset($var){
        return false;
    }

	public function get($key, $arg1 = null, $arg2 = null){
		$keys = explode('.', $key);
		if(count($keys) == 1){
			return SettingPeer::retrieveByKey($key);
		}else{
			$setting = SettingPeer::retrieveByKey($keys[0]);
			if(is_array($arg1)){
				return $setting->getValue($keys[1], $arg1, $arg2);
			}else{
				return $setting->getValue($keys[1], [], $arg1);
			}
		}
	}

}
