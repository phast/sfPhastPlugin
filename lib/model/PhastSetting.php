<?php

class PhastSetting extends BaseObject
{
	protected $row_template = '
		<dl class="phast-box-field-{id} phast-box-type-field-{type}">
			<dt>{title}</dt>
			<dd>{field}</dd>
		</dl>
		';


	public function setRowTemplate($template){
		$this->row_template = $template;
	}

	public function getFieldType(){

	}



	public function getFields(){
		return SettingFieldQuery::create()
			->filterByVisible(true)
			->filterBySettingId($this->id)
			->filterByVisible(true)
			->orderByPosition()
			->find();
	}

	public function buildRow(SettingField $item, $resultId, $admin = false){
		$type = $item->getTypeId();
		$field = '';
		$type_name = SettingFieldPeer::getTypeName($type);
        $type_field = $type_name;
		$fieldId = 'field_'.$item->getId();
		$value = null;
		$resultValue = null;
		if($resultId){
			$resultValue = SettingValueQuery::create()->filterByResultId($resultId)->filterByFieldId($item->getId())->findOne();
		}

		if($type_name == 'textarea'){
			if($resultValue) $value = $resultValue->getText();
			$field = '<textarea name="'.$fieldId.'" id="'.$fieldId.'">'.($value ? $value : '').'</textarea>';
		}elseif($type_name == 'textedit'){
			if($resultValue) $value = $resultValue->getText();
			$field = '<textarea name="'.$fieldId.'" id="'.$fieldId.'" class="phast-box-textedit">'.($value ? $value : '').'</textarea>';
		}elseif($type_name == 'checkbox'){
			if($resultValue) $value = $resultValue->getText();
			$field = '<label><input '.($value ? 'checked="checked"' : '').' type="checkbox" class="" style="" name="'.$fieldId.'"> '.$item->getTitle().'</label>';
		}elseif($type_name == 'text'){
			if($resultValue) $value = $resultValue->getText();
			$field = '<input value="'.($value ? $value : '').'" type="'.$type_name.'" name="'.$fieldId.'" id="'.$fieldId.'">';
		}elseif($type_name == 'select'){
			if($resultValue) $value = $resultValue->getOptionId();
			$field = '<select name="'.$fieldId.'" id="'.$fieldId.'">
					  <option value=""></option>';

			foreach($item->getSettingOptions() as $option){
				$field .= '<option '.($value == $option->getId() ? 'selected="selected"' : '').' value="'.$option->getId().'">'.$option->getTitle().'</option>';
			}

			$field .= '</select>';
		}elseif($type_name == 'file'){
            $field = "<input type=\"file\" name=\"{$fieldId}\">";
            if($resultValue and $resultValue->getFileId()){
                $field .= '<div>' . $resultValue->getFile()->getFileInfo() . '</div>';
            }
        }
        elseif($type_name == 'image'){
            $type_field = 'file';
            $field = "<input type=\"file\" name=\"{$fieldId}\">";
            if($resultValue and $resultValue->getImageId()){
                $field .= '<div>' . $resultValue->getImageTag(50, 50) . '</div>';
            }
        }else if($type_name == 'gallery'){
            if(!$resultValue or !$resultValue->getGalleryId()){
                $gallery = new Gallery();
                $gallery->save();
            }else{
                $gallery = $resultValue->getGallery();
            }
            $field .= "<input type=\"hidden\" name=\"{$fieldId}\" value=\"{$gallery->getId()}\"><div class=\"WidgetGalleryList\" data-gallery-id=\"{$gallery->getId()}\"></div>";
        }

		$row = strtr($this->row_template,
			[
				'{field}' => $field,
				'{title}' => $type_name == 'checkbox' ? '' : $item->getTitle() . ($admin ? ' <span class="phast-box-notice" style="display:inline">('.$item->getKey().')</span>' : ''),
				'{id}' => $fieldId,
				'{type}' => $type_field
			]
		);

		return $row;
	}

	public function renderForm($result = null, $admin = false){
		$form = '';
		$fields = $this->getFields();
		if($fields->count()){
			foreach($fields as $item){
				$form .= $this->buildRow($item, $result ? $result->getId() : null, $admin);
			}

			$form .= '<input name="result_id" type="hidden" value="'.($result ? $result->getId() : '').'">';
		}

		return $form;
	}

	public function saveFields($result){
		$request = sfContext::getInstance()->getRequest();
		$resultId = $request['result_id'];


		foreach($this->getSettingFields() as $field){
			$fieldId = 'field_'.$field->getId();

			if(!$resultValue = SettingValueQuery::create()->filterByFieldId($field->getId())->filterByResultId($request['result_id'])->findOne()){
				$resultValue = new SettingValue();
			}


            $resultValue->setOptionId(null);
            $resultValue->setText('');

            if($field->getTypeName() == 'image') {

                if ($request->hasFile($fieldId)) {
                    $resultValue->uploadImage($fieldId, '/settings/');
                }

                $resultValue->setFileId(null);
                $resultValue->setGalleryId(null);

            }else if($field->getTypeName() == 'file'){

                if($request->hasFile($fieldId)){
                    $upload = new sfPhastUpload($fieldId);
                    $upload->path(sfConfig::get('sf_upload_dir') . '/settings');
                    $upload->deny(['php']);
                    $upload->save();

                    if($file = $resultValue->getFile()){
                        $file->updateFromUpload($upload);
                    }else{
                        $file = File::createFromUpload($upload);
                    }

                    $resultValue->setFile($file);
                }

                $resultValue->setImageId(null);
                $resultValue->setGalleryId(null);

            }else if($field->getTypeName() == 'gallery'){
                $resultValue->setGalleryId($request[$fieldId]);

                $resultValue->setFileId(null);
                $resultValue->setImageId(null);

            }else{

                $value = $request[$fieldId];

                $resultValue->setFileId(null);
                $resultValue->setImageId(null);
                $resultValue->setGalleryId(null);

                if($field->getTypeName() == 'select'){
                    $resultValue->setOptionId($value ? $value : null);
                }elseif($field->getTypeName() == 'checkbox'){
                    $resultValue->setText($value ? 1 : '');
                }else{
                    $resultValue->setText($value);
                }
            }

            $resultValue
                ->setResultId($result->getId())
                ->setFieldId($field->getId())
                ->save();

		}
	}

    public function getResults(){
        return SettingResultQuery::create()->filterBySetting($this)->orderByPosition()->filterByVisible(true)->find();
    }

	public function getResult($params = []){
		$query = SettingResultQuery::create()->filterBySettingId($this->id);
		if($params){
			//$query->filterBySomeThing();
		}

		$result = $query->findOne();

		if(!$result){
			$result = new SettingResult();
			$result->setSettingId($this->id);
			if($params){
				//$result->setSomeThing();
			}

			$result->save();

		}

		return $result;
	}

	public function getResultFields($params = []){
		$result = $this->getResult($params);
		foreach($result->getSettingValuesJoinSettingField() as $item){
			$item->getSettingField()->getTitle();
			$item->getValue;
		}
	}

	protected $results = [];
	public function getValue($key, $params = [], $default = null){
		$resultIndex = serialize($params);
		if(!isset($this->results[$resultIndex])){
			$this->results[$resultIndex]['result'] = $this->getResult($params);
		}

		if(!isset($this->results[$resultIndex][$key])) {
            $field = SettingFieldQuery::create()->filterBySettingId($this->id)->filterByKey($key)->findOne();
            if (!$field) {
                $this->results[$resultIndex][$key] = $default;
                return $this->results[$resultIndex][$key];
            }
            $fieldValue = SettingValueQuery::create()->filterByResultId($this->results[$resultIndex]['result']->getId())->filterByFieldId($field->getId())->findOne();
            if (!$fieldValue) {
                $this->results[$resultIndex][$key] = $default;
                return null;
            }

			$this->results[$resultIndex][$key] = $fieldValue->getValue();

		}

		return $this->results[$resultIndex][$key];
	}


}
