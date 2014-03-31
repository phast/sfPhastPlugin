<?php

class Cache_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'cache' => new Twig_Function_Function('cache'),
            'cache_save' => new Twig_Function_Function('cache_save'),
        );
    }

    public function getName()
    {
        return 'cache';
    }
}
