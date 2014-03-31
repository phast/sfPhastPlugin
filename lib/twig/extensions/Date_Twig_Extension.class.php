<?php


class Date_Twig_Extension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'format_daterange' => new Twig_Filter_Function('format_daterange'),
            'format_date' => new Twig_Filter_Function('format_date'),
            'format_datetime' => new Twig_Filter_Function('format_datetime'),
            'distance_of_time_in_words' => new Twig_Filter_Function('distance_of_time_in_words'),
            'time_ago_in_words' => new Twig_Filter_Function('time_ago_in_words'),
        );
    }

    public function getName()
    {
        return 'date';
    }
}
