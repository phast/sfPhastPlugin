<?php

class sfPhastListPatternRelation{

	protected
     	$pattern,
		$column,
		$recursive,
		$relPatternAlias,
		$relPattern,
		$relColumn
	;

	public function __construct(sfPhastListPattern $pattern, $rel){
		if (!preg_match('/^([\w\d_]+)\.([A-Z_]+|%)$/', $rel, $match))
			throw new sfPhastException(sprintf('Relation » Неверный формат %s', $rel));

        if($match[2] == '%'){
            $this->pattern = $pattern;
            $this->column = null;
            $this->relPatternAlias = $match[1];
            $this->relColumn = null;
            $this->pattern->setIndependent(false);

        }else{
            $column = $pattern->getTableMap()->getColumn(strtolower($match[2]));

            if (!$column->getRelatedColumnName())
                throw new sfPhastException(sprintf('Relation » Поле %s не имеет связей', $column->getName()));

            $pattern->setField($column->getName());

            $this->pattern = $pattern;
            $this->column = $column;
            $this->relPatternAlias = $match[1];
            $this->relColumn = $this->column->getRelatedColumn();
            if($this->pattern->getAlias() === $match[1]){
                $this->recursive = true;
                $this->pattern->setRecursiveRelation($this);
            }else{
                $this->pattern->setIndependent(false);
            }
        }


	}

	public function fix(){

        if($this->column === null) {


            $this->pattern->getList()->getPattern($this->relPatternAlias);

            if(!$relPattern = $this->pattern->getList()->getPattern($this->relPatternAlias))
                throw new sfPhastException(sprintf('FixRelations » Паттерн %s не найден', $this->relPatternAlias));

            $this->relPattern = $relPattern;
            $relPattern->attachRelation($this);

        }else{
            $relTableName = $this->column->getRelatedTable()->getPhpname();
            if(!$relPattern = $this->pattern->getList()->getPattern($this->relPatternAlias))
                throw new sfPhastException(sprintf('FixRelations » Паттерн %s не найден', $this->relPatternAlias));

            if($relPattern->getTable() !== $relTableName)
                throw new sfPhastException(sprintf('Relation » Паттерн %s не содержит поле %s.%s ', $relPattern->getAlias(), $relTableName, $this->column->getName()));

            $relPattern->setField($this->relColumn->getName());
            $relPattern->attachRelation($this);

            $this->relPattern = $relPattern;
        }


	}

	public function getRecursive(){
		return $this->recursive;
	}

	public function getPattern(){
		return $this->pattern;
	}

	public function getColumn(){
		return $this->column;
	}

	public function getRelColumn(){
		return $this->relColumn;
	}

	public function getRelPattern(){
		return $this->relPattern;
	}

	public function getRelPatternAlias(){
		return $this->relPatternAlias;
	}

	public function isRecursive(){
	    return $this->recursive;
	}

}