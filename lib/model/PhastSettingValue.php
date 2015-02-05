<?php

class PhastSettingValue extends BaseObject
{

    public function getValue(){
        $field = $this->getSettingField();
        $fieldValue = $this;

        if($field->getTypeName() == 'file') {
            $value = $fieldValue->getFile();
        }else if($field->getTypeName() == 'image'){
            $value = $fieldValue->getImage();
        }else if($field->getTypeName() == 'gallery'){
            $value = $fieldValue->getGalleryId() ? $fieldValue->getGallery()->getFrontImages() : [];
        }else if($field->getTypeName() == 'select'){
            $option = $fieldValue->getSettingOption();
            $value = $option ? $option->getTitle() : null;
        }elseif($field->getTypeName() == 'checkbox'){
            $value= $fieldValue->getText() ? true : false;
        }else{
            $value = $fieldValue->getText();
        }

        return $value;
    }

}
