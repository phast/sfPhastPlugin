<?php

class PhastUserSession extends BaseObject{

    public function getSignKeyTitle(){
        return $this->getUserSign()->getKey();
    }

    public function getCreatedDate(){
        return $this->getCreatedAt('d.m.Y H:i');
    }

    public function getUpdatedDate(){
        return $this->getCreatedAt('d.m.Y H:i');
    }

}