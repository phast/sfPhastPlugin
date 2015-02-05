<?php


class PhastWidgetPeer
{

    public static function render($source, $field = 'content'){
        $holder = $source->getHolder();
        $peer = $source::PEER;
        $map = $peer::getTableMap();
        $column = $map->getColumn($field);
        $content = $source->getByName($column->getPhpName());
        $content = preg_replace_callback('/(?:<p>)?<widget (?:style="([^"]*)"|data-id="\d+"|data-type="\w+"| *)*>(\d+)<\/widget>(?:<\/p>)?/', function($match) use ($holder){
            if($widget = WidgetQuery::create()->filterByHolder($holder)->findOneById($match[2])){
                return $widget->getHtml(['style' => $match[1]]);
            }else{
                return '';
            }

        }, $content);
        return $content;
    }

}
