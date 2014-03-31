<?php

class Url_Twig_Extension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'link_to2' => new Twig_Function_Function('link_to2'),
            'link_to1' => new Twig_Function_Function('link_to1'),
            'url_for2' => new Twig_Function_Function('url_for2'),
            'url_for1' => new Twig_Function_Function('url_for1'),
            'url_for' => new Twig_Function_Function('url_for'),
            'link_to' => new Twig_Function_Function('link_to'),
            'url_for_form' => new Twig_Function_Function('url_for_form'),
            'form_tag_for' => new Twig_Function_Function('form_tag_for'),
            'link_to_if' => new Twig_Function_Function('link_to_if'),
            'link_to_unless' => new Twig_Function_Function('link_to_unless'),
            'public_path' => new Twig_Function_Function('public_path'),
            'button_to' => new Twig_Function_Function('button_to'),
            'form_tag' => new Twig_Function_Function('form_tag'),
            'mail_to' => new Twig_Function_Function('mail_to'),
        );
    }

    public function getName()
    {
        return 'url';
    }
}
