<?php

class PhastHolder extends BaseObject 
{

    public function setObject($object){
        $this->{'set'.get_class($object)}($object);
        $this->setCompleted(true);
        $this->save();
    }

    public function getAllWidgets(){
        $criteria = WidgetQuery::create()->orderByPosition();
        return $this->getWidgets($criteria);
    }

    public function getObject(){
        $map = HolderPeer::getTableMap();
        foreach($map->getColumns() as $column){
            /**
             * @param ColumnMap $column
             */
            if($relation = $column->getRelation()){
                if(!$this->getByName($column->getPhpName())) continue;
                return $this->{'get'.$relation->getName()}();
            }
        }

    }

}
