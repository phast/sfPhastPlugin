<?php

/**
 * @todo Замутить кеширование теплейтов
 */
class sfPhastList extends ArrayObject
{

	protected
		$id,
		$patterns = array(),
		$controls = array(),
		$template = '',
		$event,
		$attachSelector,
		$prepare,
		$empty,
        $refresh,
		$filters,
		$fields = array(),
		$parameters = array(),
		$columns = array(),
		$pages = array(),
		$opened = array();

	protected function initialize()
	{
	}

	public function __construct($id, $attach = null)
	{
		$this->id = $id;
		sfPhastUI::attach($id, $this);
		$this->initialize();
		if($attach) $this->attach($attach);
	}

	public function offsetSet($key, $value)
	{
		throw new sfPhastException('Событие не предусмотрено');
	}

	/**
	 * @return sfPhastListPattern
	 */
	public function offsetGet($key)
	{
		return $this->getPattern($key);
	}

	public function offsetExists($key)
	{
		return $this->hasPattern($key);
	}

	public function offsetUnset($key)
	{
		throw new sfPhastException('Событие не предусмотрено');
	}

	public function attach($selector)
	{
		$this->attachSelector = $selector;
		return $this;
	}

	public function setFilters($closure){
		$this->filters = $closure;
		return $this;
	}

	public function setEmpty($message){
		$this->empty = $message;
		return $this;
	}


    public function setRefresh($value)
    {
        $this->refresh = $value;
        return $this;
    }

	public function setParameter($key, $value){
		$this->parameters[$key] = $value;
		return $this;
	}

	public function getParameter($key, $default = null){
		return isset($this->parameters[$key]) ? $this->parameters[$key] : $default;
	}

	public function hasParameter($key){
		return isset($this->parameters[$key]);
	}

    public function &getParameters(){
        return $this->parameters;
    }

	public function setPrepare($closure){
		$this->prepare = $closure;
		return $this;
	}

	public function setColumns($columns)
	{
		if(!is_array($columns)){
			$columns = array();
			foreach (func_get_args() as $column) {
				$columns[] = $column;
			}

		}
		$this->columns = $columns;
		return $this;
	}

	public function setLayout($template)
	{
		$this->template = preg_replace_callback('/\{ *(%?[\w\d_]+)(?: *as *([\w\d_]+))?' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'parsePattern'), $template);
		return $this;
	}

	public function setEvent($action){
		$this->event = 'function(event, option, list){switch(event){' . sfPhastUI::parseScript($action) . '}}';
	}

	public function parsePattern($match)
	{
		$table = $match[1];
		$alias = $match[2] ? $match[2] : $table;
		$parameters = $match[3] ? $match[3] : '';

        if('%' == $table[0]){
            $alias = substr($table, 1);
        }

		if ($this->hasPattern($alias)) throw new sfPhastException(sprintf('ParseLayout » Паттерн %s уже существует', $alias));

		$this->patterns[$alias] = $pattern = new sfPhastListPattern($this, $table, $alias);

		if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $param) {
				$source = trim($param[0]);
				$name = $param[1];
				$value = trim(isset($param[3]) ? $param[3] : $param[2]);

				if($source[0] === ':'){

					$pattern->setScript($name, $value);

				}else{
					switch ($name) {

						case 'template':
							$pattern->setTemplate($value);
							break;

						case 'fields':
							$pattern->setFields($value);
							break;

						case 'relation':
							$pattern->setRelation($value);
							break;

						case 'flex':
							$pattern->setFlex(sfPhastUtils::parseBoolean($value));
							break;

						case 'sort':
							$pattern->setSort(sfPhastUtils::parseBoolean($value));
							break;

						case 'limit':
							$pattern->setLimit((int) $value);
							break;

                        case 'independent':
                            $pattern->setIndependent(sfPhastUtils::parseBoolean($value), true);
                            break;

						case 'icon':
							$pattern->setIcon($value);
							break;

						case 'action':
							$pattern->setAction($value);
							break;

						case 'class':
							$pattern->setClass($value);
							break;

						default:
							$pattern->setAttribute($name, $value);
					}
				}

			}

		}

		return '';
	}

	public function addControl($options)
	{
        if(!is_array($options)){
            $args = func_get_args();
            $options = [
                'caption' => $args[0],
                'icon' => $args[1],
                'action' => $args[2],
            ];

        }

		if (isset($options['action'])){
			$options['action'] = sfPhastUI::parseScript($options['action'], array('model' => array('list: list')));
		}

		$control = array();
		if (isset($options['icon'])) $control[] = "icon: '{$options['icon']}'";
		if (isset($options['caption'])) $control[] = "caption: '{$options['caption']}'";
		if (isset($options['action'])) $control[] = "action: function(node, list, event){{$options['action']}}";
		$this->controls[] = '{' . implode(', ', $control) . '}';
		return $this;
	}

	protected function setPages($pages)
	{
		$this->pages = $pages;
	}

	public function getPage($mask)
	{
		return isset($this->pages[$mask]) ? $this->pages[$mask] : 0;
	}

	protected function setOpened($opened)
	{
		$this->opened = $opened;
	}

	public function isOpened($mask)
	{
		return isset($this->opened[$mask]) && $this->opened[$mask];
	}

	public function setField($key, $type, $label = '')
	{
		$key = strtolower($key);
		return $this->fields[$key] = new sfPhastBoxField($key, $type, $label, $this);
	}

	public function getField($key)
	{
		$key = strtolower($key);
		return isset($this->fields[$key]) ? $this->fields[$key] : false;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function hasField($key)
	{
		$key = strtolower($key);
		return isset($this->fields[$key]);
	}


	/**
	 * @return sfPhastListPattern
	 */
	public function getPattern($alias)
	{
		if (!$this->hasPattern($alias)) throw new sfPhastException(sprintf('Паттерн %s отсутствует', $alias));
		return $this->patterns[$alias];
	}

	public function hasPattern($alias)
	{
		return isset($this->patterns[$alias]);
	}

	public function getPatterns()
	{
		return $this->patterns;
	}

	public function configurate($closure){
		$closure = $closure->bindTo($this);
		$closure();
	    return $this;
	}

	public function usePattern($alias, $closure){
		$closure = $closure->bindTo($this->getPattern($alias));
		$closure();
		return $this;
	}

	public function doSort($request){
		if($this->hasPattern($request['pattern'])){

			$pattern = $this->getPattern($request['pattern']);
			if($item = $request->getItem($pattern->getTable(), false, 'pk')
				and (!$pattern->hasSortItem() || $item = $pattern->getSortItem($item))){

				if(true === $error = $pattern->sortValidate($item)){

					if(!$request['next']){
						$item->setPosition($item->position('low')+1);
						$item->save();
					}else if(!$request['prev']){
						$item->setPosition($item->position('high')-1);
						$item->save();
					}else{
						if($prev = $request->getItem($pattern->getTable(), false, 'prev')
							and (!$pattern->hasSortItem() || $prev = $pattern->getSortItem($prev))){
							$item->position(array('after', $prev));
						}else{
							return array('error' => 'Невозможно изменить позицию элемента');
						}
					}

				}else{
					return array('error' => $error ? $error : 'Невозможно изменить позицию элемента');
				}

				return array('$time' => time());

			}else{
				return array('error' => 'Элемент не найден');
			}
		}

		return array('error' => 'Невозможно изменить позицию элемента');
	}

	public function render()
	{
		$this->fixRelations();

		$output = '';

		$controls = implode(',', $this->controls);

		$columns = array();
		foreach ($this->columns as $column) {
			$columns[] = "'{$column}'";
		}
		$columns = implode(', ', $columns);

		$output .= "$$.List.register({";
		$output .= "\n\tid: '{$this->id}',";
		$output .= "\n\tcontrols: [{$controls}],";
		$output .= "\n\tcolumns: [{$columns}],";
		if($this->event) $output .= "\n\tevent: {$this->event},";
		if($this->empty) $output .= "\n\tempty: '{$this->empty}',";
		if($this->refresh) $output .= "\n\trefresh: '{$this->refresh}',";
		$output .= "\n\tlayout: {";

		$i = 0;
		foreach ($this->patterns as $pattern) {
			if ($i++) $output .= ',';
			$output .= $pattern->render();
		}

		$output .= "\n\t" . '}';
		$output .= "\n" . '});';

		if ($this->attachSelector) {
			$output .= "\n$$.List.create('{$this->id}', {attach: '{$this->attachSelector}'});";
		}

		return $output;

	}

	public function request($request)
	{

        try{

		if($action = $request->getParameter('$action')){

			if($this->hasPattern($request['$pattern'])){

				$pattern = $this->getPattern($request['$pattern']);
				return $pattern->handle($action, $request, $pattern->isCustom() ? null : $request->getItem($pattern->getTable(), false, '$pk'));
			}

			return array('error' => 'Pattern не найден');

		}else if($request->hasParameter('sort')){

			return $this->doSort($request);

		}else{
			$this->setPages(($request['$pages'] && is_array($request['$pages'])) ? $request['$pages'] : array());
			$this->setOpened(($request['$opened'] && is_array($request['$opened'])) ? $request['$opened'] : array());

			$response = new sfPhastUIResponse($this);

			$this->fixRelations();

			if($this->prepare){
				$closure = $this->prepare;
				$closure($this);
			}


			if($this->filters && $request['$renderFilters']){
				$closure = $this->filters;
				$list = $this;
				$template = $closure($request, $response);
				$template = preg_replace_callback('/\{ *([\w\d_]+):?(\w+)? *(?:, *([^\n\r\}]+))?' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', function($match) use ($list){

					$key = $match[1];
					$type = $match[2] ? $match[2] : 'text';
					$parameters = $match[4] ? $match[4] : '';

					$field = $list->setField($key, $type);
					if ($match[3]) $field->setLabel($match[3]);
					if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

						foreach ($matches as $param) {
							$name = $param[1];
							$value = trim(isset($param[3]) ? $param[3] : $param[2]);

							switch ($name) {
								case 'class':
									$field->setClass($value);

								case 'style':
									$field->setStyle($value);

								case 'name':
								case 'type':
									break;

								default:
									$field->setAttribute($name, $value);
							}

						}

					}

					return "{:{$key}:}";


				}, $template);

				$template = preg_replace_callback('/\{:([\w\d_]+):\}/', function($match) use ($list)
				{
					$key = $match[1];
					if (!$field = $list->getField($key))
						throw new sfPhastException(sprintf('Render » Поле %s не назначено', $key));

					return $field->render();
				}, $template);
				$template = trim(str_replace(array("\n", "\r"), '', $template));
				$response['$filters'] = $template;
			}

			foreach ($this->patterns as $pattern) $pattern->isIndependent() && $pattern->loadData();

			$items = array();
			foreach ($this->patterns as $pattern)
				if ($pattern->haveData())
					$items[$pattern->getAlias()] = $pattern->getData();

			$response['items'] = $items;



			return $response->get();
		}

        }catch(sfPhastException $e){
            return array('items' => array(), 'error' => $e->getMessage());
        }

	}

	protected function fixRelations()
	{
		foreach ($this->patterns as $pattern) $pattern->fixRelations();
		foreach ($this->patterns as $pattern) $pattern->sortAttachedRelations();
	}

}