<?php

class Helper_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'use_helper' => new Twig_Function_Function('use_helper'),
        );
    }

    public function getName()
    {
        return 'helper';
    }
}
