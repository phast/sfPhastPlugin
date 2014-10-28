<?php

class sfPhastUIResponse extends ArrayObject
{
	protected $data, $box;

	public function __construct($box = null){
		$this->box = $box;
	}

	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}

	public function placeholder($field, $value){
		$this->data['$placeholder'][$field] = $value;
	}

	public function error($message = null)
	{
		if(NULL === $message) return isset($this->data['error']);
		$this->data['error'][] = $message;
	}

    public function check(){
        if($this->error()) throw new sfPhastException('');
    }

    public function save($request, $item){
        $this->check();
        $request->autofill($item);
        $item->save();
        $this->pk($item);
    }

	public function notfound($message = 'Элемент не найден'){
		$this['$notfound'] = 1;
		$this->error($message);
	}

	public function success($message){
		$this['$success'] = $message;
	}

	public function closeBox(){
		$this['$closeBox'] = 1;
	}

	public function noRefresh(){
		$this['$noRefresh'] = 1;
	}

	public function documentReload(){
		$this['$documentReload'] = 1;
	}

	public function pk($value){
		$this->parameter('pk', $value instanceof BaseObject ? $value->getPrimaryKey() : $value);
	}

	public function parameter($name, $value){
		$this->data['$parameters'][$name] = $value;
		sfContext::getInstance()->getRequest()->setParameter('#' . $name, $value);
	}

	public function select($fieldName, $options, $methodValue = null, $methodCaption = null, $insertEmpty = null){
		if($methodValue){
			$items = $options;
			$options = array();

			if($insertEmpty)
				$options[''] = true === $insertEmpty ? '' : $insertEmpty;

			foreach ($items as $item) {
				$options[$item->$methodValue()] = $item->$methodCaption();
			}
		}else{
            if($insertEmpty)
                $options = array('' => true === $insertEmpty ? '' : $insertEmpty) + $options;
        }

		if($field = $this->box->getField($fieldName)){
			$this->data['$' . $field->getType()][$fieldName] = $options;
		}else{
			$this->data['$select'][$fieldName] = $options;
		}
	}

	public function autofill($item)
	{
		$table = get_class($item);
		$tablePeer = $table . 'Peer';
		$tableMap = $tablePeer::getTableMap();

		foreach ($this->box->getFields() as $key => $field) {
			if('password' == $field->getType()) continue;

            if($crop = $field->getAttribute('crop')){
                $this['phast_crop_' . $key] = $item->getByName($crop, BasePeer::TYPE_FIELDNAME);
            }

            if('file' == $field->getType()){
                if($receive = $field->getAttribute('receive')){
                    $closure = create_function('$item', 'return ' . $receive . ';');
                    $this['phast_file_' . $key] = $closure($item);
                }
                continue;

            }else if($field->getAttribute('custom')){
	            $closure = create_function('$item', 'return ' . $field->getAttribute('custom') . ';');
	            $this[$key] = $closure($item);

            }else if($tableMap->hasColumn($key)){

				$column = $tableMap->getColumn($key);
				$this[$key] = $item->getByName($column->getPhpName());

				if('calendar' == $field->getType()){
                    if('TIMESTAMP' == $column->getType() || 'DATE' == $column->getType()){
                        $this[$key] = $this[$key] ? sfPhastUtils::date(strtotime($this[$key])) : '';
                    }else{
                        $this[$key] = $this[$key] ? sfPhastUtils::date($this[$key]) : '';
                    }
                    $this[$key] = $this[$key] ? sfPhastUtils::date($this[$key]) : '';

				}else if('choose' == $field->getType()){

					if($this[$key]){
						$closure = create_function('$item', 'return ' . $field->getAttribute('caption') . ';');
						$this['phast_choose_' . $key] = $closure($item);
					}else{
						$this['phast_choose_' . $key] = $field->getAttribute('empty');
					}

				}
			}
		}
	}

	public function get(){
		return $this->error() ? ['error' => $this->data['error']] : $this->data;
	}

}
