<?php


class PhastSettingFieldPeer
{

	public static $field_types = [
		1 => 'Текстовое поле',
		2 => 'Многострочный текст',
		3 => 'HTML текст',
		4 => 'Чекбокс',
		5 => 'Выбор из списка значений',
		6 => 'Файл',
		7 => 'Изображение',
		8 => 'Галерея изображений',
	];

	public static $field_type_names = [
		1 => 'text',
		2 => 'textarea',
		3 => 'textedit',
		4 => 'checkbox',
		5 => 'select',
		6 => 'file',
		7 => 'image',
		8 => 'gallery',
	];

    public static function getTypes() {
        return self::$field_types;
    }

	public static function getTypeNames() {
		return self::$field_type_names;
	}

	public static function getTypeName($type){
		return isset(self::getTypeNames()[$type]) ? self::getTypeNames()[$type] : '';
	}

}
