<?php

class PhastHolderPeer extends BaseObject 
{

    public static function retrieveFor($object){
        $classname = get_class($object);
        $map = HolderPeer::getTableMap();
        foreach($map->getColumns() as $column){
            /**
             * @param ColumnMap $column
             */
            if($relation = $column->getRelation()){
                if($classname != $relation->getForeignTable()->getPhpName()) continue;
                if($holder = HolderQuery::create()->{'filterBy'.$relation->getForeignTable()->getPhpName()}($object)->findOne()){
                    return $holder;
                }else{
                    return static::createFor($object);
                }
            }
        }

        throw new sfPhastException($classname . ' не может иметь Holder');
    }

    public static function createFor($object){
        $holder = new Holder();
        $holder->setObject($object);
        return $holder;
    }

}
