<?php

class sfPhastBoxField
{

	protected
		$box,
		$key,
		$type,
		$label = '',
		$wrapper,
		$notice,
		$required = false,
		$validate = array(),
		$styles = array(),
		$classes = array(),
		$attributes = array(),
        $croptypes = ['..'];

	public function __construct($key, $type, $label = '', $box)
	{
		$this->key = $key;
		$this->type = $type;
		$this->label = $label;
		$this->box = $box;

        if($type == 'content'){
            ContentPeer::initialize();
        }
	}

    public function addCropTypeFromLine($line){
        $title = '';
        $width = null;
        $height = null;
        $scale = null;
        $inflate = null;

        foreach(explode(',', $line) as $i => $part){
            if('null' == $part){
                $part = null;
            }else if('true' == $part){
                $part = true;
            }else if('false' == $part){
                $part = false;
            }else if($i > 0 && $i < 3){
                $part = (int) $part;
            }else if($i > 2){
                $part = (boolean) $part;
            }

            if($i == 0) $title = $part;
            if($i == 1) $width = $part;
            if($i == 2) $height = $part;
            if($i == 3) $scale = $part;
            if($i == 4) $inflate = $part;
        }

        $this->addCropType($title, $width, $height, $scale, $inflate);
    }

    public function addCropType($title, $width, $height, $scale, $inflate){
        $this->croptypes[] = [
            'title' => $title,
            'width' => $width,
            'height' => $height,
            'scale' => $scale,
            'inflate' => $inflate,
        ];
    }

    public function getCropTypes(){
        return $this->croptypes;
    }

	public function getType(){
		return $this->type;
	}

	public function setRequired($required)
	{
		$this->required = $required;
		return $this;
	}

	public function getRequired()
	{
		return $this->required;
	}

	public function setNotice($value)
	{
		$this->notice = $value;
		return $this;
	}

	public function getNotice()
	{
		return $this->notice;
	}

	public function setValidate($validate)
	{
		preg_match('/^(\/.+\/[smiu]*)\s*(.+)\s*$/', $validate, $match);
		if (count($match) == 3) {
			$this->validate[] = array(
				'regex' => $match[1],
				'error' => $match[2],
			);
		}
		return $this;
	}

	public function getValidate()
	{
		return $this->validate;
	}

	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
		return $this;
	}

	public function getAttribute($name, $default = null)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
	}

    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function setStyle($style)
	{
		$this->styles[] = $style;
		return $this;
	}

	public function setClass($class)
	{
		$this->classes[] = $class;
		return $this;
	}

	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function render()
	{
		$name = strtolower($this->key);
		$class = implode(' ', $this->classes);
		$style = implode(';', $this->styles);
		$notice = $this->notice ? '<span class="phast-box-notice">' . $this->notice . '</span>' : '';

		$output = '';
		$output .= '<dl class="phast-box-field-' . $name . ' phast-box-type-field-' . $this->type . '">';
		if($this->type != 'checkbox') $output .= "<dt>{$this->label}{$notice}</dt>";
		$output .= "<dd>";

		switch ($this->type) {

			case 'text':
			case 'password':
			case 'checkbox':
			case 'file':
			case 'image':
                $inputType = $this->type;
                if($inputType == 'image'){
                    $inputType = 'file';
                }
                $additional = '';
                if($this->type == 'text' && $this->hasAttribute('autocomplete')){
                    $additional .= ' data-autocomplete="'.$this->getAttribute('autocomplete').'"';
                }
				if(!$this->getAttribute('hidden')){
                    if($this->type == 'checkbox') $output .= "<label>";
                    $output .= "<input type=\"{$inputType}\" name=\"{$name}\" style=\"{$style}\" class=\"{$class}\"{$additional}>";
                    if($this->type == 'checkbox') $output .= " {$this->label}</label>";
                }
				if('image' == $this->type || ('file' == $this->type && $this->getAttribute('render'))){
					$guid = str_replace('.', '', uniqid($name, true));
					$output .= "<div class=\"{$guid}\"></div>";
                    $action = ('image' == $this->type || $this->getAttribute('render') == 'on') ? 'return value;' : $this->getAttribute('render');
                    $crop = $this->getAttribute('crop') ? '<a href="#" class="phast-crop-edit"></a>' : '';
					$this->box->addSystemEvent('afterRender', "node.find('.$guid').html(
					    ((function(){var value = box.data.phast_file_{$name}||'';{$action}})()
					    + (box.data.phast_crop_{$name} ? '{$crop}' : '')) || '');
                    ");
		            if($crop){
                        $this->box->addSystemEvent('afterRender', "node.find('.$guid').find('.phast-crop-edit').on('click', function(){
                            $$.Box.create('PhastCrop', {parameters:{box:'{$this->box->getId()}',field:'{$name}',id:box.data.phast_crop_{$name}}}).open();
                            return;
                        });");
                    }

				}
				break;

            case 'content':
                $guid = str_replace('.', '', uniqid('WidgetContentList', true));
                $output .= "<input type=\"hidden\" name=\"{$name}\"><div class=\"{$guid}\"></div>";
                $this->box->addSystemEvent('afterOpen', "this.attachList($$.List.create('WidgetContentList', {attach: this.getNode().find('div.{$guid}'), box: this, autoload: false, ignorePk: true, parameters: {}, makeParameters: function(parameters){parameters.content_id = box.data.$name}, wait: ''}));");
                break;

            case 'gallery':
                $guid = str_replace('.', '', uniqid('WidgetGalleryList', true));
                $output .= "<input type=\"hidden\" name=\"{$name}\"><div class=\"{$guid}\"></div>";
                $this->box->addSystemEvent('afterOpen', "this.attachList($$.List.create('WidgetGalleryList', {attach: this.getNode().find('div.{$guid}'), box: this, autoload: false, ignorePk: true, parameters: {}, makeParameters: function(parameters){parameters.gallery_id = box.data.$name}, wait: ''}));");
                break;

			case 'select':
				if($multiple = $this->getAttribute('multiple')){
					$multiple = "size=\"{$multiple}\" multiple";
                    $name .= '[]';
				}
				$output .= "<select name=\"{$name}\" style=\"{$style}\" class=\"{$class}\" {$multiple}></select>";
				break;

			case 'textarea':
				$output .= "<textarea name=\"{$name}\" style=\"{$style}\" class=\"{$class}\"></textarea>";
				break;

			case 'textedit':
                $mode = 'data-mode="' . $this->getAttribute('mode') . '"';
				$output .= "<textarea name=\"{$name}\" style=\"{$style}\" class=\"phast-box-textedit {$class}\" {$mode}></textarea>";
                break;

			case 'checkgroup':
			case 'radiogroup':
                if($this->getAttribute('oneline')){
                    $class = 'oneline ' . $class;
                }
				$output .= "<ul data-name=\"{$name}\" style=\"{$style}\" class=\"phast-box-selectgroup phast-box-{$this->type} {$class}\"></ul>";
				break;

			case 'choose':
				$empty = $this->getAttribute('empty');
				$output .= "
					<div class=\"phast-box-choose\" data-empty=\"{$empty}\" data-header=\"{$this->getAttribute('header')}\" data-list=\"{$this->getAttribute('list')}\">
						<input type=\"text\" value=\"{$empty}\" name=\"phast_choose_{$name}\" readonly>
						<input type=\"hidden\" name=\"{$name}\" readonly>
				".
					($empty ? '<a href="#clear" class="clear"></a>' : '')
				."
					</div>
				";
				break;

			case 'calendar':
				$output .= "<div class=\"phast-box-calendar\" ".($this->getAttribute('time') ? 'data-time="1"' : '')."><input type=\"text\" name=\"{$name}\" style=\"{$style}\" class=\"{$class}\"><i></i></div>";
				break;

            case 'static':
				$output .= "<div class=\"phast-box-static\" style=\"{$style}\" class=\"{$class}\" data-field={$name}></div>";
				break;

            case 'prettyfile_multiple':
                $output .= '<div class="pretty-file-input"><div class="text">Выбрать</div><div class="loader"></div><input type="file" size="1" multiple name="' . $name . '[]"></div>';
                break;

            case 'prettyfile':
                $output .= '<div class="pretty-file-input"><div class="text">Выбрать</div><div class="loader"></div><input type="file" size="1" multiple name="' . $name . '"></div>';
                break;

            case 'hidden':
                $output .= "<input type=\"{$this->type}\" name=\"{$name}\" style=\"{$style}\" class=\"{$class}\">";
                break;
		}

		$output .= '</dd>';
		$output .= '</dl>';

		return $output;
	}

}