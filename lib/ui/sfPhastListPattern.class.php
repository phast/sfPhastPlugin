<?php


class sfPhastListPattern
{

	const CRITERIA_RESET_RELATION = 1;
	const CRITERIA_RESET_SORT = 2;

	protected
		$list = null,
		$table = 'Table',
		$tablePeer,
		$tableMap,
		$tableQuery,
		$alias,
		$primaryColumns,
		$fields = array(),
		$controls = array(),
		$template = '',
		$relations = array(),
		$attachedRelations = array(),
		$recursiveRelation,
		$independent = true,
        $independent_locked = false,
		$flex,
		$limit,
		$sort,
		$sortItem,
		$icon,
		$action,
		$deleteValidator,
		$visibleValidator,
		$sortValidator,
		$criteria,
		$decorator,
		$class,
		$handlers = array(),
		$scripts = array(),
		$attributes = array(),
		$data = array(),
        $custom;

	public function __construct($list, $table, $alias = NULL)
	{

		$this->list = $list;
		$this->table = $table;
		$this->tablePeer = $this->table . 'Peer';
		$this->tableQuery = $this->table . 'Query';
        if('%' == $table[0]){
            $table = substr($table, 1);
            $this->custom = function(){return [[]];};
        }else{
            if (!$this->tableMap = call_user_func(array($this->tablePeer, 'getTableMap')))
                throw new sfPhastException(sprintf('TableMap для таблицы %s отсутствует', $this->table));
            $this->primaryColumns = $this->tableMap->getPrimaryKeyColumns();
        }
        $this->alias = NULL !== $alias ? $alias : $table;
    }

	public function getList() { return $this->list; }

	public function getPattern($pattern) { return $this->list->getPattern($pattern); }

	public function getTable($normalize = false)
	{
		return true === $normalize ? strtolower($this->table) : $this->table;
	}

	public function getTableMap() { return $this->tableMap; }

	public function getAlias() { return $this->alias; }

	public function setFlex($value)
	{
		$this->flex = $value;
		return $this;
	}

	public function getFlex() { return $this->flex; }

	public function setClass($value)
	{
		$this->class = $value;
		return $this;
	}

	public function getClass() { return $this->class; }

	public function setAction($value)
	{
		$this->action = sfPhastUI::parseScript($value, array('model' => array('list: list'), 'parameters' => array('pk: item.$pk')));
		return $this;
	}

	public function setLimit($value)
	{
		$this->limit = $value;
		return $this;
	}

	public function getLimit() { return $this->limit; }

	public function setSort($value)
	{
		$this->sort = $value;
		return $this;
	}

	public function getSort() { return $this->sort; }

	public function setSortItem($closure) {
		$this->sortItem = $closure;
		return $this;
	}

	public function getSortItem($item){
		$closure = $this->sortItem;
		return $closure($item);
	}

	public function hasSortItem(){
		return !!$this->sortItem;
	}

	public function setIcon($value)
	{
		$this->icon = $value;
		return $this;
	}

	public function getIcon() { return $this->icon; }

	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
		return $this;
	}

	public function getAttribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function setIndependent($value, $lock = false)
	{
        if(!$this->independent_locked){
            $this->independent = $value;
            if($lock){
                $this->independent_locked = true;
            }
        }

		return $this;
	}

	public function isIndependent()
	{
		return $this->independent;
	}

	public function setRecursiveRelation(sfPhastListPatternRelation $relation)
	{
		$this->recursiveRelation = $relation;
		return $this;
	}

	public function getRecursiveRelation()
	{
		return $this->recursiveRelation;
	}

	public function haveRecursiveRelation()
	{
		return !!$this->recursiveRelation;
	}

	public function setRelation($rel)
	{
		$this->relations[$rel] = new sfPhastListPatternRelation($this, $rel);
		return $this;
	}

	public function hasRelations()
	{
		return !!$this->relations;
	}

	public function fixRelations()
	{
		if ($this->hasRelations()){
			foreach ($this->relations as $rel => $relation)
				$relation->fix();
		}

	}

	public function attachRelation(sfPhastListPatternRelation $relation)
	{
		$this->attachedRelations[] = $relation;
	}

	public function getAttachedRelations()
	{
		return $this->attachedRelations;
	}

	public function sortAttachedRelations()
	{
		$recursiveStack = array();

		foreach($this->attachedRelations as $i => $rel){
			if($rel->getRecursive()){
				$cut = array_splice($this->attachedRelations, $i, 1);
				$recursiveStack[] = $cut[0];
			}
		}

		foreach ($recursiveStack as $rel) {
			$this->attachedRelations[] = $rel;
		}

	}

	public function setHandler($action, $closure){
		$this->handlers[$action] = $closure;
		return $this;
	}

	public function setScript($name, $code){
		$this->scripts[$name] = sfPhastUI::parseScript($code, array('model' => array('list: list')));
		return $this;
	}

    public function setCustom(Closure $closure)
    {
        $this->custom = $closure;
        return $this;
    }

	public function setCriteria(Closure $closure)
	{
		$this->criteria = $closure;
		return $this;
	}

	public function setDecorator(Closure $closure)
	{
		$this->decorator = $closure;
		return $this;
	}

	public function setDeleteValidator(Closure $closure)
	{
		$this->deleteValidator = $closure;
		return $this;
	}

	public function deleteValidate($item){
		return ($closure = $this->deleteValidator) ? $closure($item) : true;
	}

	public function setVisibleValidator(Closure $closure)
	{
		$this->visibleValidator = $closure;
		return $this;
	}

	public function visibleValidate($item){
		return ($closure = $this->visibleValidator) ? $closure($item) : true;
	}

	public function setSortValidator(Closure $closure)
	{
		$this->sortValidator = $closure;
		return $this;
	}

	public function sortValidate($item){
		return ($closure = $this->sortValidator) ? $closure($item) : true;
	}

	public function addControl($options)
	{
		if(isset($options['action'])){
			$options['action'] = sfPhastUI::parseScript($options['action'], array('model' => array('list: list'), 'parameters' => array('relation: item.$pk')));
		}

		$control = array();
		if (isset($options['icon'])) $control[] = "icon: '{$options['icon']}'";
		if (isset($options['caption'])) $control[] = "caption: '{$options['caption']}'";
		if (isset($options['action'])) $control[] = "action: function(item, node, list, pattern, event){{$options['action']}}";
		$this->controls[] = '{' . implode(', ', $control) . '}';
	    return $this;
	}

	public function setTemplate($template)
	{
		$this->template = preg_replace_callback('/([^,]+)/', array($this, 'parseTemplate'), $template);
	}

	public function parseTemplate($match)
	{
		$output = trim($match[1]);
		if (preg_match('/^[\w][\w\d]+(?:\:[\w\d_]+(?:\(\))?)?$/', $output))
			$output = $this->setField($output, true)->getName();

		else if('.visible' == $output){

			$this->setField('visible');
			$this->setHandler('.visible', function($pattern, $request, $item){
				if(!$item)
					return array('error' => 'Элемент не найден');

				if(true === $error = $pattern->visibleValidate($item)){
					$item->setVisible(!$item->getVisible());
					$item->save();
					return array('success' => time());
				}else{
					return array('error' => $error ? $error : 'Элемент не может быть удален');
				}
			});

		} else if('.delete' == $output){

			$this->setHandler('.delete', function($pattern, $request, $item){
				if(!$item)
					return array('error' => 'Элемент не найден');

				if(true === $error = $pattern->deleteValidate($item)){
					$item->delete();
					return array('success' => time());
				}else{
					return array('error' => $error ? $error : 'Элемент не может быть удален');
				}

			});

		}

		return "'{$output}'";
	}

	public function setFields($template)
	{
		preg_replace_callback('/([^,]+)/', array($this, 'parseTemplate'), $template);
	}

	public function setField($template, $returnField = false)
	{
		if (!preg_match('/^([\w\d_]+)(?:\:([\w\d_]+)(?:\(\))?)?$/', $template, $match))
			throw new sfPhastException(sprintf('SetField » Неверный шаблон %s ', $template));

		$name = strtolower($match[1]);
		$method = isset($match[2]) ? $match[2] : null;

		$this->fields[$name] = $field = new sfPhastListPatternField($name, $this);

		if ($method) {
			$field->setMethod($method);
		} else if(!$this->custom && $this->tableMap->hasColumn($name)) {
			$field->setColumn($this->tableMap->getColumn($name));
		}

		if ($returnField) return $field;

		return $this;
	}

	public function loadData($rel = null, $relId = null, $level = 0)
	{

        $mask = $this->getMask($rel, $relId);

        if($this->custom){
            $closure = $this->custom;
            $items = $closure($this, $rel, $relId, $level);

        }else{
            $c = new $this->tableQuery;
            $criteriaResult = null;


            if (!$rel && $this->haveRecursiveRelation())
                $rel = $this->getRecursiveRelation();

            if ($this->criteria) {
                $closure = $this->criteria;
                $criteriaResult = $closure($c, $this, $rel, $relId);
            }

            if ($rel && (!$criteriaResult || !$criteriaResult & static::CRITERIA_RESET_RELATION)) {
                if($rel->getColumn() !== null)
                    $c->addAnd(
                        $rel->getColumn()->getFullyQualifiedName(),
                        $relId,
                        $relId === null ? Criteria::ISNULL : Criteria::EQUAL
                    );
            }

            if ($this->tableMap->hasColumn('position')) {
                $c->addAscendingOrderByColumn($this->tableMap->getColumn('position')->getFullyQualifiedName());
            }

            if ($this->getLimit()) {
                $items = $c->paginate(($page = $this->list->getPage($mask)) ? $page : 1, $this->getLimit());
                $this->pushPages($items->getLastPage(), $rel, $relId);
            } else {
                $items = $c->find();
            }
        }


		foreach ($items as $item) {
			$data = $this->pushData($item, $rel, $relId, $level);

			if ($this->getFlex() && !$this->list->isOpened($mask . ' ' . $data['$pk']))
				continue;

			foreach ($this->getAttachedRelations() as $relation)
                $relation->getPattern()->loadData($relation, $relation->getRelColumn() ? $item->getByName($relation->getRelColumn()->getPhpName()) : null, $level+1);
		}

	}

	public function getMask($rel, $relId)
	{
		return $mask = $rel ? "{$this->getAlias()} {$rel->getRelPattern()->getAlias()} {$relId}" : "{$this->getAlias()} .";
	}

	public function getData()
	{
		return $this->data;
	}

	public function haveData()
	{
		return !!$this->data;
	}

	protected function pushData($item, $rel, $relId, $level)
	{
        if($rel && $rel->getColumn() === null){
            $nodeName = $rel->getRelPatternAlias() . ' %';
        }else{
            $nodeName = $rel && $relId ? $rel->getRelPatternAlias() . ' ' . $relId : '.';
        }
        if (!isset($this->data[$nodeName])) $this->data[$nodeName] = array();
        return $this->data[$nodeName][] = $this->decorateData($item, $rel, $relId, $level);
	}

	protected function pushPages($total, $rel, $relId)
	{
        if($rel && $rel->getColumn() === null){
            $nodeName = $rel->getRelPatternAlias() . ' %';
        }else{
            $nodeName = $rel && $relId ? $rel->getRelPatternAlias() . ' ' . $relId : '.';
        }
		if ($total > 1) $this->data[$nodeName]['pages'] = $total;
	}


	protected function decorateData($item, $rel, $relId, $level)
	{

        if(is_array($item)){
            $output = $item;
            if(!isset($output['$pk'])) $output['$pk'] = '%';

        }else{
            $output = array();

            foreach ($this->fields as $field) {
                $output[$field->getName()] = $field->getValue($item);
            }
        }

		if($this->class) $output['$class'] = $this->class;

		if($this->decorator){
			$closure = $this->decorator;
			$closure($output, $item, $this, $rel, $relId, $level);
		}

        if($this->primaryColumns){
            foreach ($this->primaryColumns as $column)
                $output['$pk'][] = $item->getByName($column->getPhpName());
            $output['$pk'] = implode(',', $output['$pk']);
        }

		return $output;
	}


	public function render()
	{

		$relationMap = array();
		foreach ($this->attachedRelations as $rel)
			$relationMap[] = "{
				source: '{$rel->getRelPattern()->getAlias()}',
				source_field: '" . strtolower($rel->getRelColumn() ? $rel->getRelColumn()->getName() : '%') . "',
				target: '{$rel->getPattern()->getAlias()}',
				target_field: '" . strtolower($rel->getColumn() ? $rel->getColumn()->getName() : '%') . "'
			}";
		$relationMap = implode(', ', $relationMap);

		$controls = implode(',', $this->controls);

		if($this->scripts){
			foreach($this->scripts as $name => $code)
				$scripts[] = "{$name}: function(item, node, list, pattern, event){{$code}}";
			$scripts = implode(', ', $scripts);
		}else{
			$scripts = '';
		}

		$output = "\n\t\t'{$this->alias}': {";
		if ($this->icon) $output .= "\n\t\t\ticon: '{$this->icon}',";
		if ($this->flex) $output .= "\n\t\t\tflex: true,";
		if ($this->sort) $output .= "\n\t\t\tsort: true,";
		if ($this->action) $output .= "\n\t\t\taction: function(item, node, list, pattern, event){{$this->action}},";
		if ($relationMap) $output .= "\n\t\t\trelations: [{$relationMap}],";
		$output .= "\n\t\t\tcontrols: [{$controls}],";
		$output .= "\n\t\t\tscripts: {{$scripts}},";
		$output .= "\n\t\t\ttemplate: [{$this->template}]";
		$output .= "\n\t\t}";
		return $output;

	}


	public function handle($action, $request, $item){
		if(isset($this->handlers[$action])){
			$closure = $this->handlers[$action];
			return $closure($this, $request, $item);
		}
		return array('error' => 'Handler не найден');
	}

}
