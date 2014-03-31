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