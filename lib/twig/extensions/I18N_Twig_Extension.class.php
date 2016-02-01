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
        $twig_functions['date_human'] = new Twig_SimpleFunction('date_human', function($timestamp, $domain = 'messages', $args = null) {
            $date_now = strtotime(date("Y-m-d", time()) . " 00:00:00");
            $tomorrow = strtotime("+1 day", $date_now);
            $yesterday = strtotime("-1 day", $date_now);
            $date_in = strtotime(date("Y-m-d", $timestamp) . " 00:00:00");
            $context = sfContext::getInstance();
            if ($date_now == $date_in) {
                $translation = $context->get('i18n')->__("Сегодня", $args, $domain);
            }
            else if ($tomorrow == $date_in) {
                $translation = $context->get('i18n')->__("Завтра", $args, $domain);
            }
            else if ($yesterday == $date_in) {
                $translation = $context->get('i18n')->__("Вчера", $args, $domain);
            }
            else {
                $translation = false;
            }
            return $translation;
        });
        return $twig_functions;
    }

    public function getName()
    {
        return 'i18n';
    }
}
