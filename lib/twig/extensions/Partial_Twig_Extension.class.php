<?php

class Partial_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'include_component_slot' => new Twig_Function_Function('include_component_slot'),
            'get_component_slot' => new Twig_Function_Function('get_component_slot'),
            'has_component_slot' => new Twig_Function_Function('has_component_slot'),
            'include_component' => new Twig_Function_Function('include_component'),
            'get_component' => new Twig_Function_Function('get_component'),
            'include_partial' => new Twig_Function_Function('include_partial'),
            'get_partial' => new Twig_Function_Function('get_partial'),
            'slot' => new Twig_Function_Function('slot'),
            'end_slot' => new Twig_Function_Function('end_slot'),
            'has_slot' => new Twig_Function_Function('has_slot'),
            'include_slot' => new Twig_Function_Function('include_slot'),
            'get_slot' => new Twig_Function_Function('get_slot'),
        );
    }

    public function getName()
    {
        return 'partial';
    }
}
