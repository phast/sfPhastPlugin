<?php


class PhastSettingPeer
{
	public static function retrieveByKey($key){
		return SettingQuery::create()->findOneByKey($key);
	}
}
