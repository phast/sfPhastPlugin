<?php


class Asset_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'auto_discovery_link_tag' => new Twig_Function_Function('auto_discovery_link_tag'),
            'javascript_path' => new Twig_Function_Function('javascript_path'),
            'javascript_include_tag' => new Twig_Function_Function('javascript_include_tag'),
            'stylesheet_path' => new Twig_Function_Function('stylesheet_path'),
            'stylesheet_tag' => new Twig_Function_Function('stylesheet_tag'),
            'use_stylesheet' => new Twig_Function_Function('use_stylesheet'),
            'use_javascript' => new Twig_Function_Function('use_javascript'),
            'decorate_with' => new Twig_Function_Function('decorate_with'),
            'image_path' => new Twig_Function_Function('image_path'),
            'image_tag' => new Twig_Function_Function('image_tag'),
            'include_metas' => new Twig_Function_Function('include_metas'),
            'include_http_metas' => new Twig_Function_Function('include_http_metas'),
            'include_title' => new Twig_Function_Function('include_title'),
            'get_javascripts' => new Twig_Function_Function('get_javascripts'),
            'include_javascripts' => new Twig_Function_Function('include_javascripts'),
            'get_stylesheets' => new Twig_Function_Function('get_stylesheets'),
            'include_stylesheets' => new Twig_Function_Function('include_stylesheets'),
            'dynamic_javascript_include_tag' => new Twig_Function_Function('dynamic_javascript_include_tag'),
            'use_dynamic_javascript' => new Twig_Function_Function('use_dynamic_javascript'),
            'use_dynamic_stylesheet' => new Twig_Function_Function('use_dynamic_stylesheet'),
            'getjavascripts' => new Twig_Function_Function('getjavascripts'),
            'get_javascripts_for_form' => new Twig_Function_Function('get_javascripts_for_form'),
            'include_javascripts_for_form' => new Twig_Function_Function('include_javascripts_for_form'),
            'use_javascripts_for_form' => new Twig_Function_Function('use_javascripts_for_form'),
            'getstylesheets' => new Twig_Function_Function('getstylesheets'),
            'get_stylesheets_for_form' => new Twig_Function_Function('get_stylesheets_for_form'),
            'include_stylesheets_for_form' => new Twig_Function_Function('include_stylesheets_for_form'),
            'use_stylesheets_for_form' => new Twig_Function_Function('use_stylesheets_for_form'),
        );
    }

    public function getName()
    {
        return 'asset';
    }
}
