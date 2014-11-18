<?php

class sfPhastException extends sfException
{

    protected $context;

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

}

class sfPhastFormException extends sfException
{
    protected $errors = [];
    public function setErrors($errors){
        $this->errors = $errors;
        return $this;
    }

    public function getErrors(){
        return $this->errors;
    }

}

