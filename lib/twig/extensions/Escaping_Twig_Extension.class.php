<?php

class Escaping_Twig_Extension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'esc_entities' => new Twig_Filter_Function('esc_entities'),
            'esc_specialchars' => new Twig_Filter_Function('esc_specialchars'),
            'esc_raw' => new Twig_Filter_Function('esc_raw'),
            'esc_js' => new Twig_Filter_Function('esc_js'),
            'esc_js_no_entities' => new Twig_Filter_Function('esc_js_no_entities'),
        );
    }

    public function getName()
    {
        return 'escaping';
    }
}
