<?php

class sfPhastRequest extends sfWebRequest
{

	protected $box;

	public function setBox($box)
	{
		$this->box = $box;
	}

	public function autofill($item, $ignore = array())
	{
		$table = get_class($item);
		$tablePeer = $table . 'Peer';
		$tableMap = $tablePeer::getTableMap();

		foreach ($this->box->getFields() as $key => $field) {
			if (!$tableMap->hasColumn($key) || in_array($key, $ignore)) continue;
            $key = strtolower($key);
            $column = $tableMap->getColumn($key);

            switch ($field->getType()) {
                case 'text':
                case 'textarea':
                case 'textedit':
                case 'gallery':
                    $item->setByName($column->getPhpName(), trim($this[$key]));
                    break;

                case 'calendar':
                    if($time = sfPhastUtils::strtotime($this[$key])){
                        if('DATE' == $column->getType()){
                            $time = date('Y-m-d', $time);
                        }
                    }else{
                        $time = null;
                    }
                    $item->setByName($column->getPhpName(), $time);
                    break;
                
                case 'select':
                case 'choose':
                    if($field->getAttribute('multiple')) continue;
                    $item->setByName($column->getPhpName(), '' === $this[$key] ? null : $this[$key]);
                    break;

                case 'checkbox':
                    $item->setByName($column->getPhpName(), $this[$key] ? true : false);
                    break;

            }
		}
	}

	protected $xmlHttpRequestToggle = false;

	public function isXmlHttpRequest($toggle = null)
	{
		if (null !== $toggle) {
			$this->xmlHttpRequestToggle = $toggle;
		}
		return $this->xmlHttpRequestToggle ? true : ($this->getHttpHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
	}

	public function isMethod($method)
	{
		return $this->getMethod() === strtoupper($method);
	}

	public function isTrigger($parameter, $method = null, $xhr = false)
	{
		if ($xhr && !$this->isXmlHttpRequest())
			return false;

        if($parameter === null && $method){
            return $this->isMethod($method);
        }

		return null === $method ? $this->hasParameter($parameter) : $this->isMethod($method) && $this->hasParameter($parameter);
	}

	public function getParameter($name, $default = null)
	{
		if ('#' == $name[0]) {
			$parameters = $this->getParameter('$parameters');
			if ($parameters && is_array($parameters)) {
				$name = substr($name, 1);
				if (isset($parameters[$name]))
					return $parameters[$name];
			}
			return $default;
		}

		return $this->parameterHolder->get($name, $default);
	}


	public function hasParameter($name)
	{
		if ('#' == $name[0]) {
			$parameters = $this->getParameter('$parameters');
			if ($parameters && is_array($parameters)) {
				if (isset($parameters[substr($name, 1)]))
					return true;
			}
			return false;
		}

		return $this->parameterHolder->has($name);
	}


	public function setParameter($name, $value)
	{
		if ('#' == $name[0]) {
			$parameters = $this->getParameter('$parameters', array());
			$parameters[substr($name, 1)] = $value;
			$name = '$parameters';
			$value = $parameters;
		}

		$this->parameterHolder->set($name, $value);
	}

	public function getItem($table, $allowEmpty = false, $name = '#pk')
	{
		if ($pk = $this->getParameter($name)) {
			$peer = $table . 'Peer';
			$pk = explode(',', $pk);
            		if(isset($pk[1])) return $peer::retrieveByPK($pk[0], $pk[1]);
            		return $peer::retrieveByPK($pk[0]);
		} else if ($allowEmpty) {
			return new $table();
		}
		return false;
	}

    public function hasFile($name){
        $file = $this->getFiles($name);
        return !empty($file['tmp_name']);
    }

    public function extract($fields){
        if(is_string($fields)) $fields = explode(',', $fields);
        $result = [];

        foreach($fields as $field){
            $field = explode(':', $field);
            $type = isset($field[1]) ? trim($field[1]) : 'text';
            $field = trim($field[0]);
            $value = $this->getParameter($field);

            switch($type){
                case 'trim':
                case 'html':
                case 'text':
                case 'textarea':
                    $value = trim($value);
                    break;
            }

            switch($type){
                case 'textarea':
                case 'text':
                    $value = htmlspecialchars($value, ENT_QUOTES);
                    break;
                case 'int':
                case 'integer':
                    $value = (int) $value;
                    break;
                case 'float':
                    $value = (float) $value;
                    break;
                case 'bool':
                case 'boolean':
                    $value = !!$value;
                    break;
                case 'date':
                    $value = strtotime($value);
                    break;
                case 'array':
                    $value = is_array($value) ? $value : [];
            }

            $result[$field] = $value;
        }

        return $result;
    }

}
