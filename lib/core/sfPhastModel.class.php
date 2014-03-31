<?php

class sfPhastModel
{

	static public function position($option, $object, $c = null){	
		if(!$c) $c = new Criteria();
		$peer = get_class($object) . 'Peer';
		if(is_array($option)){
			$prop = array_slice($option, 1);
			$option = $option[0];
		}
		
		switch($option){
			// Вставить после элемента
			case 'after': 
				$c->addAscendingOrderByColumn($peer::POSITION);
				$c->add($peer::POSITION, $prop[0]->getPosition(), Criteria::GREATER_THAN);
				
				if(($next = $peer::doSelectOne($c)) && $prop[0]->getPosition() >= $next->getPosition()-1){
					$c_update = new Criteria();
					$c_update->add($peer::POSITION, $peer::POSITION . ' + 1', Criteria::CUSTOM_EQUAL);
					BasePeer::doUpdate($c, $c_update, Propel::getConnection($peer::DATABASE_NAME));
				}
				
				$object->setPosition($prop[0]->getPosition() + 1);
				$object->save();
				break;
			// Поднять вверх
			case 'up': 
				$c->add($peer::POSITION, $object->getPosition(), Criteria::LESS_THAN);
				$c->addDescendingOrderByColumn($peer::POSITION);
				if($item = $peer::doSelectOne($c)){
					$position = $item->getPosition();
					$item->setPosition($object->getPosition());
					$item->save();
					$object->setPosition($position);
					$object->save();
				}else{
					$object->setPosition($object->position('low')+1);
					$object->save();
				}
				break;
			// Опустить вниз	
			case 'down':
				$c->add($peer::POSITION,$object->getPosition(),Criteria::GREATER_THAN);
				$c->addAscendingOrderByColumn($peer::POSITION);
				if($item = $peer::doSelectOne($c)){
					$position = $item->getPosition();
					$item->setPosition($object->getPosition());
					$item->save();
					$object->setPosition($position);
					$object->save();
				}else{
					$object->setPosition($object->position('high')-1);
					$object->save();
				}
				break;
			// Позиция верхнего элемента
			case 'high':
				$c->addAscendingOrderByColumn($peer::POSITION);
				if($item = $peer::doSelectOne($c)) return $item->getPosition(); else return 0;
				break;
			// Позиция нижнего элемента	
			case 'low':
				$c->addDescendingOrderByColumn($peer::POSITION);
				if($item = $peer::doSelectOne($c)) return $item->getPosition(); else return 0;
				break;
			// Установка значения позиции при добавлении элемента
			case 'insert':
				$object->setPosition($object->position());
			// Порядковый номер при добавлении элемента
			default:
				return $object->position('low')+1;
				break;
		}
	}

}
