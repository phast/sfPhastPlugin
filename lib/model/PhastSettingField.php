<?php


class PhastSettingField extends BaseObject
{

	public function getTypeName(){
		return SettingFieldPeer::getTypeName($this->type_id);
	}

}
