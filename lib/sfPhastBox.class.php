<?php

class sfPhastBox
{

	protected
		$id,
		$receive = false,
		$receiveAction,
		$saveAction,
		$verificationAction,
		$table,
		$buttons = array(),
		$fields = array(),
		$multipart = false,
		$events = array(),
		$auto = null,
        $attachSelector,
        $closeDisabled,
		$afterOpen = '',
		$afterRender = '',
        $autoClose = false,
		$crop = '',
		$resize = '';

	protected $template = '';

	public function __construct($id)
	{
		$this->id = $id;
		sfPhastUI::attach($id, $this);
		$this->setButton('Default', '
			<button type="submit" class="phast-ui-button phast-box-save">Сохранить</button>
			<button type="button" class="phast-ui-button phast-box-close">Закрыть</button>
		');
        $this->setButton('Send', '
			<button type="submit" class="phast-ui-button phast-box-send">Отправить</button>
		');
		$this->setButton('Close', '
			<button type="button" class="phast-ui-button phast-box-close">Закрыть</button>
		');
	}

    public function attach($selector)
    {
        $this->attachSelector = $selector;
        return $this;
    }

	public function addSystemEvent($event, $script){
		switch($event){
			case 'afterOpen':
			case 'afterRender':
				$this->$event .= sfPhastUI::parseScript($script, array('model' => array('box: box')));
				break;
			default:
				throw new sfPhastException(printf('Системное событие %s не зарезервировано', $event));
		}
	}

    public function setAutoClose($value = true){
        $this->autoClose = $value;
        return $this;
    }

    public function setCrop($crop){
        $this->crop = $crop;
        return $this;
    }

    public function getCrop(){
        return $this->crop;
    }

    public function setResize($resize){
        $this->resize = $resize;
        return $this;
    }

    public function getResize(){
        return $this->resize;
    }

	public function setURI($parts){
		if(!is_array($parts)){
			$parts = array();
			foreach (func_get_args() as $part) {
				$parts[] = $part;
			}
		}
        $this->uri = $parts;
        return $this;
    }

    public function getURI(){
        return $this->uri;
    }

	public function setTable($table){
		$this->table = $table;
		$this->receive = true;
		return $this;
	}

    public function getTable(){
        return $this->table;
    }

	public function setVerification(Closure $action){
		$this->verificationAction = $action;
		return $this;
	}

	public function setReceive(Closure $action)
	{
		$this->receiveAction = $action;
		$this->receive = true;
		return $this;
	}

	public function setSave(Closure $action)
	{
		$this->saveAction = $action;
		return $this;
	}

	public function setButton($id, $code)
	{
		$this->buttons[$id] = $code;
		return $this;
	}

	public function getButton($id)
	{
		return isset($this->buttons[$id]) ? "<div class=\"phast-box-buttons\">{$this->buttons[$id]}</div>" : null;
	}

	public function setTemplate($template)
	{
		$this->template = preg_replace_callback('/\{ *([\w\d_]+):?(\w+)? *(?:, *([^\n\r\}]+))?' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'parseTemplate'), $template);
		return $this;
	}

	public function parseTemplate($match)
	{

		$key = $match[1];
		$type = $match[2] ? $match[2] : 'text';
		$parameters = $match[4] ? $match[4] : '';

		$field = $this->setField($key, $type);
		if (preg_match('/file/', $type)) $this->multipart = true;
		if ($match[3]) $field->setLabel($match[3]);
		if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $param) {
				$name = $param[1];
				$value = trim(isset($param[3]) ? $param[3] : $param[2]);

				switch ($name) {

					case 'required':
						$field->setRequired($value);
						break;

					case 'validate':
						$field->setValidate($value);
						break;

					case 'class':
						$field->setClass($value);

					case 'style':
						$field->setStyle($value);

					case 'notice':
						$field->setNotice($value);

					case 'name':
					case 'type':
						break;

					default:
                        $field->setAttribute($name, $value);
				}

			}

		}

		return "{:{$key}:}";
	}

	public function enableAuto($open = true, $options = ''){
		$this->auto = array(
			'open' => $open,
			'options' => $options
		);
        return $this;
	}

	public function disableClose(){
		$this->closeDisabled = true;
        return $this;
    }

	public function setEvent($event, $action){
		$this->events[$event] = sfPhastUI::parseScript($action, array('model' => array('box: box')));
        return $this;
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


	public function render()
	{

		$template = $this->template;
		$template = preg_replace_callback('/\{#section\s*([^\n\r]+)((?:\s*(?:#\s*)?[@:]? *[\w\d_]+ *[^\n\r]+)?)\s*\}/', array($this, 'renderSection'), $template);
		$template = preg_replace_callback('/\{#button\s*([\w\d_]+)\}/', array($this, 'renderButton'), $template);
		$template = preg_replace_callback('/\{#list *([\w\d_]+)' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'renderList'), $template);
		$template = preg_replace_callback('/\{#gallery*' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'renderGalleryList'), $template);
		$template = preg_replace_callback('/\{#image*' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'renderSingleImageList'), $template);
		$template = preg_replace_callback('/\{#event' . sfPhastUI::REGEX_ATTRIBUTES_GENERAL_TEMPLATE . '\}/s', array($this, 'renderEvent'), $template);
		$template = preg_replace_callback('/\{:([\w\d_]+):\}/', array($this, 'renderField'), $template);
		$template = trim(str_replace(array("\n", "\r"), '', $template));

		if($this->events){
			$events = array();
			foreach ($this->events as $event => $action)
				$events[] = "{$event}: function(box, node){{$action}}";
			$events = implode(', ', $events);
		}
		if($this->uri){
			foreach($this->uri as $part){
				$uri[] = "'$part'";
			}
			$uri = implode(', ', $uri);
		}

		$output = '';
		$output .= "$$.Box.register({";
		$output .= "\n\tid: '{$this->id}',";
		$output .= "\n\tmode: 'custom',";
		if ($this->closeDisabled) $output .= "\n\t\$closeDisabled: true,";
		if ($this->multipart) $output .= "\n\tmultipart: true,";
		if ($this->receive) $output .= "\n\treceive: true,";
		if ($this->uri) $output .= "\n\turi: [{$uri}],";
		if ($this->events) $output .= "\n\tevents: {{$events}},";
		if ($this->afterOpen) $output .= "\n\t\$afterOpen: function(box, node){{$this->afterOpen}},";
		if ($this->afterRender) $output .= "\n\t\$afterRender: function(box, node){{$this->afterRender}},";
		$output .= "\n\ttemplate: '{$template}'";
		$output .= "\n});";

		if(null !== $this->auto){
			$output .= "$(function(){";
			$output .= "\n\n$$.Box.create('{$this->id}', {{$this->auto['options']}})";
			if($this->auto['open']) $output .= '.open()';
			$output .= '});';
		}

        if ($this->attachSelector) {
            $output .= "\n$(function(){\$$.Box.create('{$this->id}', {attach: '{$this->attachSelector}'}).open();})";
        }

		return $output;
	}

	public function renderEvent($match)
	{
		$parameters = $match[1] ? $match[1] : '';
		if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $param) {
				$name = $param[1];
				$value = trim(isset($param[3]) ? $param[3] : $param[2]);

				$this->setEvent($name, $value);
			}

		}

		return '';
	}

	public function renderSection($match)
	{
		$caption = $match[1];
		$parameters = $match[2];
		$button = null;

		if ($parameters && preg_match_all('/[\n\r][^#@:]*?[@:] *([\w\d_]+) *([^\n\r]+)/', $parameters, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $param) {
				$name = $param[1];
				$value = trim($param[2]);

				switch ($name) {

					case 'button':
						if (!$button = $this->getButton($value))
							throw new sfPhastException(sprintf('Render » Кнопка %s.%s не назначена', $this->id, $key));
						break;

				}

			}

		}

		return "<div class=\"phast-box-section\">{$button}{$caption}</div>";
	}

	public function renderList($match)
	{
		$id = $match[1];
		$parameters = $match[2];
		$guid = str_replace('.', '', uniqid($id, true));

		$wait = '';
		$caption = '';
		$autoload = 'false';
		$ignorePk = 'false';
        $params = '';

		if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $param) {
				$name = $param[1];
				$value = trim(isset($param[3]) ? $param[3] : $param[2]);

				switch($name){
					case 'wait':
						$wait = $value;
						break;

					case 'caption':
						$caption = $value;
						break;

					case 'autoload':
						$autoload = $value;
						break;

                    case 'ignorePk':
                        $ignorePk = $value;
						break;

                    case 'parameters':
                        $params = $value;
                        break;
				}

			}

		}

		$this->afterOpen .= "this.attachList($$.List.create('{$id}', {attach: this.getNode().find('div.{$guid}'), box: this, autoload: {$autoload}, ignorePk: {$ignorePk}, parameters: {{$params}}, wait: '{$wait}'}));";

		return "<dl><dt>{$caption}</dt><dd><div class=\"{$guid}\"></div></dd></dl>";
	}

    public function renderGalleryList($match)
    {
        $parameters = $match[1];

        $wait = '';
        $caption = '';
        $autoload = 'false';
        $ignorePk = 'false';
        $params = '';
        $id = '_GalleryList';

        if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

            foreach ($matches as $param) {
                $name = $param[1];
                $value = trim(isset($param[3]) ? $param[3] : $param[2]);
                switch($name){
                    case 'wait':
                        $wait = $value;
                        break;

                    case 'caption':
                        $caption = $value;
                        break;

                    case 'autoload':
                        $autoload = $value;
                        break;

                    case 'ignorePk':
                        $ignorePk = $value;
                        break;

                    case 'parameters':
                        $params = $value;
                        break;

                    case 'list':
                        $id = $value;
                        break;
                }

            }


        }
        if($params){
            $params .= ',';
        }

        $params .= 'box:"'.$this->id.'"';

        $guid = str_replace('.', '', uniqid($id, true));



        $this->afterOpen .= "this.attachList($$.List.create('{$id}', {attach: this.getNode().find('div.{$guid}'), box: this, autoload: {$autoload}, ignorePk: {$ignorePk}, parameters: {{$params}}, wait: '{$wait}'}));";

        return "<dl><dt>{$caption}</dt><dd><div class=\"{$guid}\"></div></dd></dl>";
    }

    public function renderSingleImageList($match)
    {
        $parameters = $match[1];

        $wait = '';
        $caption = '';
        $autoload = 'false';
        $ignorePk = 'false';
        $params = '';
        $id = '_SingleImageList';

        if ($parameters && preg_match_all(sfPhastUI::REGEX_ATTRIBUTES_PARSE, $parameters, $matches, PREG_SET_ORDER)) {

            foreach ($matches as $param) {
                $name = $param[1];
                $value = trim(isset($param[3]) ? $param[3] : $param[2]);
                switch($name){
                    case 'wait':
                        $wait = $value;
                        break;

                    case 'caption':
                        $caption = $value;
                        break;

                    case 'autoload':
                        $autoload = $value;
                        break;

                    case 'ignorePk':
                        $ignorePk = $value;
                        break;

                    case 'parameters':
                        $params = $value;
                        break;

                    case 'list':
                        $id = $value;
                        break;
                }

            }


        }
        if($params){
            $params .= ',';
        }

        $params .= 'box:"'.$this->id.'"';

        $guid = str_replace('.', '', uniqid($id, true));



        $this->afterOpen .= "this.attachList($$.List.create('{$id}', {attach: this.getNode().find('div.{$guid}'), box: this, autoload: {$autoload}, ignorePk: {$ignorePk}, parameters: {{$params}}, wait: '{$wait}'}));";

        return "<dl><dt>{$caption}</dt><dd><div class=\"{$guid}\"></div></dd></dl>";
    }


	public function renderButton($match)
	{
		$key = $match[1];
		if (!$button = $this->getButton($key))
			throw new sfPhastException(sprintf('Render » Кнопка %s.%s не назначена', $this->id, $key));

		return $button;
	}

	public function renderField($match)
	{
		$key = $match[1];
		if (!$field = $this->getField($key))
			throw new sfPhastException(sprintf('Render » Поле %s.%s не назначено', $this->table, $key));

		return $field->render();
	}

	public function request(sfPhastRequest $request)
	{
		$request->setBox($this);
		$response = new sfPhastUIResponse($this);

		if ($request->isTrigger('receive')) {
			$this->receive($request, $response);
		} else {
			$this->validate($request, $response);
			$this->save($request, $response);
		}

		$response['$time'] = time();
		return $response->get();
	}

	protected function validate(sfPhastRequest $request, sfPhastUIResponse $response)
	{
		foreach ($this->fields as $key => $field) {

			$key = strtolower($key);
			$value = $request->getParameter($key);

			switch ($field->getType()) {
				case 'text':
				case 'textarea':
				case 'textedit':
					if ($value) $value = trim($value);
					break;
                case 'select':
                    if($field->getAttribute('multiple')){
                        if(!is_array($value)){
                            $value = [];
                        }
                    }else if($value){
                        $value = trim($value);
                    }
					break;
			}

			$request->setParameter($key, $value);


			if($field->getType() == 'checkgroup' || $field->getType() == 'radiogroup') continue;

			$parameter = $request[$key];
			if ($required = $field->getRequired() and ($parameter === null || $parameter === '')) {
				$response->error($required);
				continue;
			}

			foreach ($field->getValidate() as $validate) {
				if (!preg_match($validate['regex'], $parameter))
					$response->error($validate['error']);
			}

		}
	}

	protected function receive(sfPhastRequest $request, sfPhastUIResponse $response)
	{
		$closure = $this->receiveAction;

		if($this->table){

			if(false !== $item = $request->getItem($this->table)){
				if($item){
					$response->autofill($item);
				}else{
					return $response->notfound();
				}
			}

			if($verificate = $this->verificationAction){
				$verificate($request, $response, $item);

				if($response->error())
					return;
			}

			if($response->error())
				return;

		}else{
			$item = null;
		}

		if($closure) $closure($request, $response, $item);
	}

	protected function save(sfPhastRequest $request, sfPhastUIResponse $response)
	{
		$closure = $this->saveAction;

		if($this->table){

			if(!$item = $request->getItem($this->table, true)){
				return $response->notfound();
			}

			if($verificate = $this->verificationAction){
				$verificate($request, $response, $item);

				if($response->error())
					return;
			}

			if(!$closure){
				if(!$response->error()){
					$request->autofill($item);
					$item->save();
					$response->pk($item->getId());
				}
			}

		}else{
			$item = null;
		}

        try{
		    if($closure) $closure($request, $response, $item);
        }
        catch(sfPhastException $e){
            return $e->getMessage() ? $response->error($e->getMessage()) : null;
        }

        if($this->autoClose) $response->closeBox();
	}

}