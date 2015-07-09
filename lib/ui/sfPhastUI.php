<?php


/**
 * @todo Написать класс для парсинга внутр. языка элементов
 * @todo Разработать общий синтаксис для всех элементов
 */
class sfPhastUI
{

	const REGEX_ATTRIBUTES_GENERAL_TEMPLATE = '((?:\s*(?:#\s*)?[@:]? *[\w\d_]+ *(?:\{(?:\{(?:\{(?:\{(?:\{(?:\{(?:\{(?:\{.*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|[^\n\r]+))*).*?';
	const REGEX_ATTRIBUTES_PARSE = '/[\n\r][^#@:]*?[@:] *([\w\d_]+) *(\{((?:\{(?:\{(?:\{(?:\{(?:\{(?:\{(?:\{.*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?\}|.)*?)\}|[^\n\r]+)/s';

	protected static
		$objects = array(),
		$stack,
		$initialized = false,
		$request;

	public static function initialize(){
		static::$initialized = true;
		static::$request = sfContext::getInstance()->getRequest();
		static::$stack = new SplStack();

        sfPhastUIWidget::PhastFileBrowserInitialize();
        sfPhastUIWidget::PhastCropInitialize();
	}

	public static function listen(){
		if(static::$initialized && static::$request->isTrigger('phastui', 'post')){
			static::$request->isXmlHttpRequest(true);
			if(!$objectId = static::$request['$id'] or !static::has($objectId))
				return array('error' => sprintf('Объект %s не найден', $objectId));

			return static::get($objectId)->request(static::$request);
		}
	}

	public static function attach($id, $object){
		// Инициализуем Phast UI при первом аттаче объекта
		!static::$initialized && static::initialize();

		if(static::has($id)) throw new sfPhastException(sprintf('PhastUI » Объект %s уже назначен', $id));

		static::$objects[$id] = $object;
		static::$stack[] = $object;
	}

	public static function has($id){
		return isset(static::$objects[$id]);
	}

	public static function get($id){
		if(!static::has($id))
			throw new sfPhastException(sprintf('PhastUI » Объект %s не найден', $id));

		return static::$objects[$id];
	}

    public static function asset(){
        $response = sfContext::getInstance()->getResponse();
        $javascripts = [
            '/sfPhastPlugin/js/jquery/jquery.min.js',
            '/sfPhastPlugin/js/jquery/jquery.populate.js',
            '/sfPhastPlugin/js/jquery/jquery.populate.js',
            '/sfPhastPlugin/js/jquery/jquery.form.js',
            '/sfPhastPlugin/js/jquery/jquery.tablednd.js',
            '/sfPhastPlugin/js/jquery/jquery-ui.custom.min.js',
            '/sfPhastPlugin/js/jquery/jquery.ui.datepicker-ru.js',
            '/sfPhastPlugin/js/jquery/jquery-ui-timepicker-addon.js',
            '/sfPhastPlugin/js/jquery/jquery.Jcrop.min.js',
            '/sfPhastPlugin/js/tinymce/tiny_mce.js?1',
            '/sfPhastPlugin/js/tinymce/jquery.tinymce.js',
            '/sfPhastPlugin/js/phast.js',
        ];
        $stylesheets = [
            '/sfPhastPlugin/css/layout.css',
            '/sfPhastPlugin/css/ui.css',
            '/sfPhastPlugin/css/list.css',
            '/sfPhastPlugin/css/box.css',
            '/sfPhastPlugin/css/icon.css',
            '/sfPhastPlugin/css/jquery-ui/smoothness.css',
            '/sfPhastPlugin/css/jquery.Jcrop.min.css',
        ];

        foreach($javascripts as $javascript){
            $response->addJavascript($javascript);
        }

        foreach($stylesheets as $stylesheet){
            $response->addStylesheet($stylesheet);
        }

    }

	public static function render(){
		if(static::$initialized){
			$output = '<script>';
			while(static::$stack->count() && $object = static::$stack->pop()){
				$output .= "\n" . $object->render();
			}
			$output .= "\n" . '</script>';
			return $output;
		}
	}

	public static function parseScript($script, $extend = array()){
		return preg_replace_callback('/\&([A-Z][\w\d_]+)(?:\[([^)]+)\])?(?:\(([^)]+)\))?/', function($match) use ($extend){

			$model = isset($match[2]) ? explode(',', $match[2]) : array();
			$parameters = isset($match[3]) ? explode(',', $match[3]) : array();

			if(isset($extend['parameters']))
				array_splice($parameters, 0, 0, $extend['parameters']);

			if(isset($extend['model']))
				array_splice($model, 0, 0, $extend['model']);

			return "$$.Box.create('{$match[1]}', {parameters: {" . implode(',', $parameters) . "}" . ($model ? ' ,' . implode(',', $model) : '') . "}).open();";

		}, $script);

    }

}