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

    public function setValue($value){
        $field = $this->getSettingField();
        $fieldValue = $this;

        if($field->getTypeName() == 'file') {
            $fieldValue->setFile($value);
        }else if($field->getTypeName() == 'image'){
            $fieldValue->setImage($value);
        }else if($field->getTypeName() == 'gallery'){
            $fieldValue->setGallery($value);
        }else if($field->getTypeName() == 'select'){
            $fieldValue->setSettingOption($value);
        }elseif($field->getTypeName() == 'checkbox'){
            $fieldValue->setText(!!$value);
        }else{
            $fieldValue->setText($value);
        }

        $fieldValue->save();
    }

}
