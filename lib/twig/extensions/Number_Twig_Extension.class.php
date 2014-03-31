<?php

class Number_Twig_Extension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'format_number' => new Twig_Filter_Function('format_number'),
            'format_currency' => new Twig_Filter_Function('format_currency'),
        );
    }

    public function getName()
    {
        return 'number';
    }
}
