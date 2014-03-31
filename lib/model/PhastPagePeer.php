<?php


class PhastPagePeer extends BaseObject
{

    public static function retrieveByURI($value){
        return PageQuery::create()->findOneByUri($value);
    }

    public static function retrieveByRoute($uri, $pattern, $requirements){
        return (new PageQuery)->forRetrieveByRoute($uri, $pattern, $requirements)->findOne();
    }

}
