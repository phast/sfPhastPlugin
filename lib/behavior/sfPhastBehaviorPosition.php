<?php

class sfPhastBehaviorPosition extends SfPropelBehaviorBase
{

	protected $column = 'position';

	public function preInsert()
	{
		if ($this->isDisabled()) {
			return;
		}

		if($this->getTable()->hasColumn($this->column)){

			return <<<EOF
if ( !\$this->positionBehaviorDisabled && !\$this->isColumnModified({$this->getTable()->getColumn($this->column)->getConstantName()}))
{
  \$this->position('insert');
}

EOF;
		}
	}

	public function preSave()
	{
		if ($this->isDisabled()) {
			return;
		}

		if($this->getTable()->hasColumn($this->column)){

			$string = "
				if(!\$this->positionBehaviorDisabled && \$this->needPositionChange()){
					\$this->position('insert');
				}
			";

		}


		return $string;
	}


	public function objectMethods()
	{
		if ($this->isDisabled())
		{
			return;
		}

		$script = '';


		if($description = $this->getTable()->getDescription()){
			preg_match_all('/~([\w\.]+)/', $description, $matches, PREG_SET_ORDER);
			foreach($matches as $match){

				if('holder' == $match[1]){

					$script .= "
protected \$holderObject;
public function getHolder(){
	if(\$this->holderObject !== null) return \$this->holderObject;
	return \$this->holderObject = HolderPeer::retrieveFor(\$this);
}
";

				}

			}
		}

		if($this->getTable()->hasColumn($this->column)){

			$script .= <<<EOF
public \$positionBehaviorDisabled;
public function disablePositionBehavior(){
	\$this->positionBehaviorDisabled = true;
	return \$this;
}

public function needPositionChange(){
  if(\$this->isModified() && \$mask = \$this->getPhastSetting('positionMask')){
	\$peer = get_class(\$this) . 'Peer';
	\$map = \$peer::getTableMap();
	foreach(\$mask as \$column){
	  if(\$this->isColumnModified(\$map->getColumn(\$column)->getFullyQualifiedName())){
		return true;
	  }
	}
  }
  return false;
}

public function position(\$options = '')
{
  \$criteria = new Criteria();
  if(\$mask = \$this->getPhastSetting('positionMask')){
    \$peer = get_class(\$this) . 'Peer';
    \$map = \$peer::getTableMap();
    foreach(\$mask as \$column){
      \$criteria->add(\$map->getColumn(\$column)->getFullyQualifiedName(), \$this->getByName(\$map->getColumn(\$column)->getPhpName()));
    }
  }

  return sfPhastModel::position(\$options, \$this, \$criteria);
}
EOF;
		}

		$script .= <<<EOF
\n\n
public function getPhastSetting(\$name)
{
  return (isset(\$this->phastSettings) && isset(\$this->phastSettings[\$name]) && \$value = \$this->phastSettings[\$name]) ? \$value : null;
}
EOF;


		return $script;
	}

}
