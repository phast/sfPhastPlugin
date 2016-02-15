<?php


class PhastSettingResult extends BaseObject
{

    protected $phastSettings = array(
        'positionMask' => array('setting_id')
    );


    public function getTitle(){
        $title = '#' . $this->getId();

        if($titleValue = SettingValueQuery::create()->filterBySettingResult($this)->useSettingFieldQuery()->filterByKey('title%')->endUse()->findOne()){
            if($titleValue->getText())
                return $titleValue->getText();
            else
                return $title;
        }

        if($fieldValue = SettingValueQuery::create()->filterBySettingResult($this)->filterByText('', Criteria::NOT_EQUAL)->findOne()){
            if($fieldValue->getText())
                return $fieldValue->getText();
            else
                return $title;
        }

        return $title;
    }

    public function getValue($field){
        if($fieldValue = SettingValueQuery::create()->filterByResultId($this->getId())->useSettingFieldQuery()->filterByKey($field)->endUse()->findOne()){
            return $fieldValue->getValue();
        }
    }

    public function setValue($field, $value){
        if(!$fieldValue = SettingValueQuery::create()->filterByResultId($this->getId())->useSettingFieldQuery()->filterByKey($field)->endUse()->findOne()){
            $fieldValue = new SettingValue();
            $fieldValue->setSettingField($field);
            $fieldValue->setResultId($this->getId());
            $fieldValue->save();
        }

        $fieldValue->setValue($value);
    }

}
