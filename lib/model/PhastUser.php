<?php

class PhastUser extends BaseObject{

    public function createSign($key, $password){
        $user = sfContext::getInstance()->getUser();

        $sign = new UserSign();
        $sign->setUser($this);
        $sign->setSalt($user->generateHash());
        $sign->setKey($key);
        $sign->setPassword($user->generatePassword($password, $sign->getSalt()));
        $sign->save();
        return $sign;
    }

    public function getCredentials(){
        return ['cp_access'];
    }

}