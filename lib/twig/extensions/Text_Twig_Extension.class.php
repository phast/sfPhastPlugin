<?php

class Text_Twig_Extension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'truncate_text' => new Twig_Filter_Function('truncate_text'),
            'highlight_text' => new Twig_Filter_Function('highlight_text'),
            'excerpt_text' => new Twig_Filter_Function('excerpt_text'),
            'wrap_text' => new Twig_Filter_Function('wrap_text'),
            'simple_format_text' => new Twig_Filter_Function('simple_format_text'),
            'auto_link_text' => new Twig_Filter_Function('auto_link_text'),
            'strip_links_text' => new Twig_Filter_Function('strip_links_text'),
        );
    }

    public function getName()
    {
        return 'text';
    }
}
