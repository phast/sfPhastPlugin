<?php

class Debug_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'log_message' => new Twig_Function_Function('log_message'),
        );
    }

    public function getName()
    {
        return 'debug';
    }
}
