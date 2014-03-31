<?php

class sfPhastValidator{

    public static function validateEmail($string){
        return preg_match('/^([\w_\.-]+)@([\w_\.-]+)\.([a-z\.]{2,6})$/i', $string);
    }

}