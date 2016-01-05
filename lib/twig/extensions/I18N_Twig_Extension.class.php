<?php


class I18N_Twig_Extension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            't' => new Twig_Filter_Function('__'),
            'format_number_choice' => new Twig_Filter_Function('format_number_choice'),
            'format_country' => new Twig_Filter_Function('format_country'),
            'format_language' => new Twig_Filter_Function('format_language'),
        );
    }

    public function getFunctions()
    {
        $twig_functions = parent::getFunctions();
        $twig_functions['__'] = new Twig_SimpleFunction('__', function($text, $domain = 'messages', $args = null) {
            $context = sfContext::getInstance();
            $translation = $context->get('i18n')->__($text, $args, $domain);
            return $translation;
        });
        return $twig_functions;
    }

    public function getName()
    {
        return 'i18n';
    }
}
