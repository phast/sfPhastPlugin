<?php

class sfPhastListPatternField{

	protected
		$name,
		$method,
		$column
	;

	public function __construct($name){
		$this->name = $name;
	}

	public function getName(){
		return $this->name;
	}

	public function setMethod($methodName){
		 $this->method = $methodName;
	}

	public function setColumn(ColumnMap $column){
		$this->column = $column;
	}

	public function getValue($item){
		if($this->method){
			return call_user_func(array($item, $this->method));
		}else if($this->column){
			return $item->getByName($this->column->getPhpName());
		}else{
			return '';
		}
	}

}