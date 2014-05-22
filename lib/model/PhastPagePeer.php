<?php


class PhastPagePeer extends BaseObject
{

    public static function retrieveByURI($value){
        return PhastPageQuery::create()->findOneByUri($value);
    }

    public static function retrieveByRoute($uri, $pattern, $requirements){
        return (new PhastPageQuery)->forRetrieveByRoute($uri, $pattern, $requirements)->findOne();
    }

}
