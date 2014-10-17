<?php

class sfPhastUpload{

    protected $options = array();
    protected $messages = array(
        'required' => 'Выберите файл',
        'max_size' => 'Максимальный размер файла %max_size%B',
        'mime_types' => 'Неверный формат изображения (%mime_type%)'
    );
    protected $requestFile;
    protected $validator;
    protected $validated;
    protected $filename;
	protected $deny = [];

    public function __construct($name){
        $this->requestFile = is_array($name) ? $name : sfContext::getInstance()->getRequest()->getFiles($name);
    }

	public function deny($exts){
		$this->deny = $exts;
	}
    public function path($path){
        $this->options['path'] = $path;
        return $this;
    }

    public function type($value){
        $this->options['mime_types'] = $value;
        return $this;
    }

    public function required($value){
        $this->options['required'] = $value;
        return $this;
    }

    public function clean(){
        if(!isset($this->requestFile['name'])){
			$this->requestFile['name'] = '';
	    }else{
			$this->requestFile['name'] = str_replace("\0", '', $this->requestFile['name']);
		    if($this->deny && in_array(pathinfo($this->requestFile['name'], PATHINFO_EXTENSION), $this->deny)){
			    throw new sfPhastException('Формат не поддерживается');
		    }
	    }

        $this->validator = new sfValidatorFile($this->options, $this->messages);
        return $this->validated = $this->validator->clean($this->requestFile);
    }

    public function save(){
        return $this->filename = $this->clean()->save($this->filename);
    }

    public function setFilename($filename){
        $this->filename = $filename;
    }

    public function getFilename(){
        return $this->filename;
    }

    public function getOriginalFilename(){
        return $this->requestFile['name'];
    }

    /**
     * return sfValidatedFile
     */
    public function getValidated(){
        return $this->validated;
    }

    public function getType(){
        return $this->validated->getType();
    }

    public function getPath(){
        return $this->validated->getPath();
    }

    public function getFilepath(){
        return $this->getPath() . '/' . $this->getFilename();
    }

    public function getWebPath(){
        return str_replace('\\', '/', str_replace(sfConfig::get('sf_web_dir'), '', $this->getPath()));
    }

}