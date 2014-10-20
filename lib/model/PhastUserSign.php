<?php

class PhastUserSign extends BaseObject{

    /**
     * @param bool $remember
     * @param bool $update
     * @return UserSession
     */
    public function authorize($remember = true, $update = true){
        return sfContext::getInstance()->getUser()->authorize($this, $remember, $update);
    }

    public function getCreatedDate(){
        return $this->getCreatedAt('d.m.Y H:i');
    }

    public function getUpdatedDate(){
        return $this->getCreatedAt('d.m.Y H:i');
    }

}