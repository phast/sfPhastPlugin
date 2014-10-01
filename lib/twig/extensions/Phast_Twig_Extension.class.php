<?php

class Phast_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'phast_ui_render' => new Twig_Function_Function(['sfPhastUI', 'render']),
            'phast_ui_asset' => new Twig_Function_Function(['sfPhastUI', 'asset']),
            'geturl' => new Twig_Function_Function('geturl'),
            'getpartial' => new Twig_Function_Function(['sfPhastUtils', 'getpartial']),
            'getcomponent' => new Twig_Function_Function(['sfPhastUtils', 'getcomponent']),
            'func' => new Twig_Function_Function([$this, 'func']),
        );
    }

    public function getFilters()
    {
        return array(
            'morph' => new Twig_Filter_Function(['sfPhastUtils', 'morph']),
            'break_words' => new Twig_Filter_Function(['sfPhastUtils', 'break_words']),
            'getlines' => new Twig_Filter_Function(['sfPhastUtils', 'getlines']),
        );
    }

    public function getName()
    {
        return 'phast';
    }

    public function func($callable) {
        $params = func_get_args();
        array_shift($params);
        return call_user_func_array($callable,$params);
    }
}


