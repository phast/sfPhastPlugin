<?php

class PhastUserGroupSectionPeer extends BasePeer{

    const ASSIGN_MODE_NONE = 0;
    const ASSIGN_MODE_FIRST = 1;
    const ASSIGN_MODE_CHAIN = 2;
    const ASSIGN_MODE_MULTI = 3;

    protected static $assignAutoList = [
        0 => 'нет',
        1 => 'каждую минуту',
        10 => 'каждые 10 минут',
        30 => 'каждые 30 минут',
        60 => 'каждые час',
        240 => 'каждые четыре часа',
        1440 => 'каждый день',
        10080 => 'каждую неделю',
        302400 => 'каждый месяц',
    ];

    protected static $assignModeList = [
        self::ASSIGN_MODE_NONE => 'нет',
        self::ASSIGN_MODE_FIRST => 'первое совпадение',
        self::ASSIGN_MODE_CHAIN => 'цепочка совпадений',
        self::ASSIGN_MODE_MULTI => 'множественный выбор',
    ];

    public static function getAssignAutoList(){
        return self::$assignAutoList;
    }

    public static function getAssignModeList(){
        return self::$assignModeList;
    }

    public static function getAssignAutoCaption($value){
        return isset(self::$assignAutoList[$value]) ? self::$assignAutoList[$value] : '';
    }

    public static function getAssignModeCaption($value){
        return isset(self::$assignModeList[$value]) ? self::$assignModeList[$value] : '';
    }

}