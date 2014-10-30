<?php


class sfPhastUIWidget{

    protected static $options;
	public static function initialize($options = []){

        if(!isset($options['widgets'])){
            $options['widgets'] = ['image', 'gallery', 'video', 'file'];
        }

        static::$options = $options;

		/**
		 * @param sfActions $action
		 * @param sfPhastRequest $request
		 * @param sfPhastUser $user
		 */

		$action = sfContext::getInstance()->getActionStack()->getLastEntry()->getActionInstance();
		$request = $action->getRequest();
		$user = $action->getUser();


        if($request->isTrigger('fileBrowserUpload', 'post')){
            if($request->hasFile('file')){
                try{
                    $path = sfConfig::get('sf_upload_dir') . '/assets';
                    $upload = new sfPhastUpload('file');
                    $upload->path($path);

                    $file = pathinfo($request->getFiles('file')['name']);
                    for($i = 0; true; $i++){
                        if($i){
                            $filename = sfPhastUtils::transliterate($file['filename']) . '('. $i .').' . $file['extension'];
                        }else{
                            $filename = sfPhastUtils::transliterate($file['filename']) . '.' . $file['extension'];
                        }

                        if(file_exists($path .'/'. $filename)){
                            continue;
                        }else{
                            break;
                        }
                    }

                    $upload->setFilename($filename);
                    $upload->deny(['php']);
                    $upload->save();
                }
                catch (Exception $e){
                    die($e->getMessage());
                }
            }
            die('success');
        }


        if($request->isTrigger('widgetGalleryUpload', 'post')){
            if($gallery = GalleryQuery::create()->findOneById($request['widgetGalleryUpload'])){
                if($request->hasFile('image')){
                    try{
                        $upload = new sfPhastUpload('image');
                        $upload->path(sfConfig::get('sf_upload_dir') . '/' . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') .  '/gallery');
                        $upload->type('web_images');
                        $upload->save();

                        $rel = new GalleryRel;
                        $rel->setGallery($gallery);
                        $rel->setImageId(Image::createFromUpload($upload)->getId());
                        $rel->save();
                    }
                    catch (Exception $e){
                        die($e->getMessage());
                    }
                }
            }
            die('success');
        }

        sfContext::getInstance()->getConfiguration()->loadHelpers('Asset');
        use_javascript('/sfPhastPlugin/js/jquery/jquery.damnUploader.js', 'last');

		$widget = new sfPhastList('WidgetList');

        foreach($options['widgets'] as $widgetName){
            switch($widgetName){
                case 'image':
                    $widget->addControl(array('caption' => 'Добавить изображение', 'icon' => 'image-add', 'action' => '&WidgetImage'));
                    break;

                case 'gallery':
                    $widget->addControl(array('caption' => 'Добавить фотогалерею', 'icon' => 'silk-images', 'action' => '&WidgetGallery'));
                    break;

                case 'video':
                    $widget->addControl(array('caption' => 'Добавить видео', 'icon' => 'film-add', 'action' => '&WidgetVideo'));
                    break;

                case 'file':
                    $widget->addControl(array('caption' => 'Добавить файл', 'icon' => 'silk-drive-add', 'action' => '&WidgetFile'));
                    break;

                case 'content':
                    $widget->addControl(array('caption' => 'Добавить блок контента', 'icon' => 'silk-layout-add', 'action' => '&WidgetContent'));
                    break;

            }
        }

		$widget->setColumns('Виджет', '', '.');
		$widget->setLayout('
            {Widget
                @fields id, type:getType, file_id, video_id, image_id, gallery_id, content_id
                @template title, :paste, .delete
                @sort on
                @action{
                    if(item.image_id) return &WidgetImage
                    if(item.gallery_id) return &WidgetGallery
                    if(item.video_id) return &WidgetVideo
                    if(item.file_id) return &WidgetFile
                    if(item.content_id) return &WidgetContent
                }

                :paste{
                    if(list.parameters.edit){
                        if(list.parameters.edit == item.$pk){
                            return $$.List.iconCaption("ok", "Активный");
                        }
                        return $$.List.actionButton(":paste_action", "add", "Выбрать");
                    }
                    return $$.List.actionButton(":paste_action2", "add", "Добавить");
                    return item.id;
                }

                :paste_action{
                    var ed = tinymce.EditorManager.activeEditor;
                    var img = ed.dom.create("img", {
                        src : "/sfPhastPlugin/js/tinymce/themes/advanced/img/trans.gif",
                        "class" : "mceItemWidget",
                        "data-id": item.$pk ,
                        "data-type": item.type
                    });

                    ed.execCommand("mceRepaint");
                    ed.selection.setNode(img);
                    list.box.close(true);
                }

                :paste_action2{
                    var ed = tinymce.EditorManager.activeEditor;
                    var img = ed.dom.create("img", {
                        src : "/sfPhastPlugin/js/tinymce/themes/advanced/img/trans.gif",
                        "class" : "mceItemWidget",
                        "data-id": item.$pk ,
                        "data-type": item.type
                    });

                    ed.execCommand("mceRepaint");
                    ed.selection.setNode(img);
                }
            }
        ');
		$widget->setPrepare(function($list) use ($request){
			if($item = HolderPeer::retrieveByPK($request['#holder'])){
				$list->setParameter('holder', $item->getId());
			}else{
				throw new sfPhastException('Holder не найден');
			}
		});
		$widgetPattern = $widget->getPattern('Widget');
		$widgetPattern->setCriteria(function(Criteria $criteria, $pattern){
			$criteria->addAnd(WidgetPeer::HOLDER_ID, $pattern->getList()->getParameter('holder'));
		});
		$widgetPattern->setDecorator(function(&$output, Widget $item) use ($request){
			if($request['#edit'] == $item->getId()) $output['$class'] = 'selected';
			if($item->getVideoId()){
				$output['$icon'] = 'film';
				$output['title'] = $item->getObject()->getTitle();
			}
			else if($item->getGalleryId()){
				$output['$icon'] = 'silk-images';
				$output['title'] = $item->getObject()->getTitle();
			}
			else if($item->getImageId()){
				$output['$icon'] = 'image';
				$output['title'] = $item->getObject()->getTitle();
			}
			else if($item->getFileId()){
				$output['$icon'] = 'silk-drive';
				$output['title'] = $item->getObject()->getTitle();
			}
            else if($item->getContentId()){
                $output['$icon'] = 'silk-layout';
                $output['title'] = $item->getObject()->getTypeCaption();
            }
			if(!$output['title']) $output['title'] = 'Без названия #' . $item->getId();
		});




		$widgetFile = new sfPhastBox('WidgetFile');
		$widgetFile->setTemplate('
            {#section Файл
                @button Default
            }

            {file:file, Файл
                @receive $item->getFileInfo()
                @render on
            }

            {title, Название
                @required Укажите название
            }
            {content:textarea, Описание}

            {#button Default}
        ');
		$widgetFile->setReceive(function($request, $response){

			if(!HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(false !== $widget = $request->getItem('Widget')){
				if($widget && $object = $widget->getObject()){
					$response->autofill($object);
				}else{
					return $response->notfound();
				}
			}

		});
		$widgetFile->setSave(function(sfPhastRequest $request, $response) use ($user){

			if(!$holder = HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(!$item = $request->getItem('Widget', true)){
				return $response->notfound();
			}

			if(!$response->error()){

				$uploadFile = function($object) use ($holder, $user){
					$upload = new sfPhastUpload('file');
					$upload->path(sfConfig::get('sf_upload_dir') . "/" . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') . '/file');
					$upload->deny(['php']);
					$upload->save();

					$object->cleanSource();
					$object->setPath($upload->getWebPath());
					$object->setFilename($upload->getFilename());
					$object->setSize(filesize($object->getSource()));
					$object->setExtension(pathinfo($object->getSource(), PATHINFO_EXTENSION));
				};


				try{

					if($item->isNew()){
						$object = new File();
						$request->autofill($object);
						$uploadFile($object);
						$object->save();

						$item->setFileId($object->getId());
						$item->setHolderId($holder->getId());
						$item->save();

					}else{

						if($object = $item->getObject()){
							if($request->hasFile('file')){
								$uploadFile($object);
							}
							$request->autofill($object);
							$object->save();
						}else{
							return $response->notfound();
						}

					}

					$response->pk($item->getId());
				}
				catch (Exception $e){
					return $response->error($e->getMessage());
				}
			}

		});




		$widgetImage = new sfPhastBox('WidgetImage');
		$widgetImage->setTemplate('
            {#section Изображение
                @button Default
            }

            {file:file, Изображение
                @receive $item->getWidgetTag()
                @render on
                '.

                    (isset($options['image']['crop']) && $options['image']['crop']
                        ? ("@crop id\n@croptype " . implode("\n@croptype ", $options['image']['crop']))
                        : ''
                    )

                .'
            }


            {width, Ограничение ширины
                @notice в пикселях
            }
            {height, Ограничение высоты
                @notice в пикселях
            }
            {fullsize:checkbox, С возможностью просмотра оригинала}

            {#section Дополнительные параметры
                @button Default
            }

            {title, Название}
            {content:textarea, Описание}

            {#button Default}
        ');
		$widgetImage->setReceive(function($request, $response){

			if(!HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(false !== $widget = $request->getItem('Widget')){
				if($widget && $object = $widget->getObject()){
					$response->autofill($object);
				}else{
					return $response->notfound();
				}
			}else{
				$response['fullsize'] = false;
			}

            $response->placeholder('width', 'без ограничений');
            $response->placeholder('height', 'без ограничений');

            $response['width'] = $response['width'] ? $response['width'] : '';
            $response['height'] = $response['height'] ? $response['height'] : '';

		});
		$widgetImage->setSave(function(sfPhastRequest $request, $response) use ($user){

			if(!$holder = HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(!$item = $request->getItem('Widget', true)){
				return $response->notfound();
			}

            if($item->isNew() && !$request->hasFile('file')) return $response->error('Загрузите изображение');

			if(!$response->error()){

				$uploadImage = function($object) use ($holder, $user){
                    $upload = new sfPhastUpload('file');
                    $upload->path(sfConfig::get('sf_upload_dir') . '/' . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') . '/image');
                    $upload->type('web_images');
                    $upload->save();
                    $object->updateFromUpload($upload);
				};


				try{

					if($item->isNew()){
						$object = new Image();
						$request->autofill($object);
						$uploadImage($object);
						$object->save();

						$item->setImageId($object->getId());
						$item->setHolderId($holder->getId());
						$item->save();

					}else{

						if($object = $item->getObject()){
							if($request->hasFile('file')){
								$uploadImage($object);
							}
							$request->autofill($object);
							$object->save();
						}else{
							return $response->notfound();
						}

					}

					$response->pk($item->getId());
				}
				catch (Exception $e){
					return $response->error($e->getMessage());
				}
			}

		});


        $widgetContent = new sfPhastBox('WidgetContent');
        $widgetContent->setTemplate('
            {#section Блок контента
                @button Default
            }

            {type:select, Тип контента}

            {image1:file, Изображение
                @receive $item->getPreview1Tag()
                @render on
            }

            {image2:file, Изображение
                @receive $item->getPreview2Tag()
                @render on
            }

            {image3:file, Изображение
                @receive $item->getPreview3Tag()
                @render on
            }

            {title, Заголовок}
            {notice, Примечание}
            {content1:textedit, Описание
                @mode link
                @style height:50px
            }
            {content2:textedit, Описание
                @mode link
                @style height:50px
            }
            {content3:textedit, Описание
                @mode link
                @style height:50px
            }
            {#event
                @afterOpen{
                    var node = $(node);
                    node.find(".phast-box-field-type select").on("change", function(){
                        var type = $(this).val();
                        node.find(".phast-box-type-field-file, .phast-box-type-field-text, .phast-box-type-field-textedit").hide();
                        if(type == 1){
                            node.find(".phast-box-field-image1, .phast-box-field-title, .phast-box-field-notice, .phast-box-field-content1").fadeIn(300);
                        }else if(type == 2){
                            node.find(".phast-box-field-image1, .phast-box-field-image2, .phast-box-field-image3, .phast-box-field-content1, .phast-box-field-content2, .phast-box-field-content3").fadeIn(300);
                        }

                    });
                }

                @afterRender{
                    var node = $(node);
                    node.find(".phast-box-field-type select").trigger("change");
                }
            }
            {#button Default}
        ');
        $widgetContent->setReceive(function($request, $response){

			if(!HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(false !== $widget = $request->getItem('Widget')){
				if($widget && $object = $widget->getObject()){
					$response->autofill($object);
				}else{
					return $response->notfound();
				}
			}else{
				$response['type'] = 1;
			}

            $response->select('type', ContentPeer::getTypes());

		});
        $widgetContent->setSave(function(sfPhastRequest $request, $response) use ($user){

			if(!$holder = HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(!$item = $request->getItem('Widget', true)){
				return $response->notfound();
			}

            if(!$request['type']) return $response->error('Укажите тип контента');
            if($request['type'] == 1){
                if($item->isNew() && !$request->hasFile('image1')) return $response->error('Загрузите изображение');
                if(!$request['title']) return $response->error('Введите заголовок');
                if(!$request['content1']) return $response->error('Введите описание');
            }else if($request['type'] == 2){
                if($item->isNew() && (!$request->hasFile('image1') or !$request->hasFile('image2') or !$request->hasFile('image3')))
                    return $response->error('Загрузите изображения');
                if(!$request['content1'] or !$request['content2'] or !$request['content3'])
                    return $response->error('Введите описание');
            }

			if(!$response->error()){

                $uploadImage = function($object) use ($holder, $request){
                    if($request->hasFile('image1')){
                        $upload = new sfPhastUpload('image1');
                        $upload->path(sfConfig::get('sf_upload_dir') . '/' . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') . '/content');
                        $upload->type('web_images');
                        $upload->save();
                        if($object->getImage1Id()){
                            $object->getImageRelatedByImage1Id()->updateFromUpload($upload);
                        }else{
                            $object->setImage1Id(Image::createFromUpload($upload)->getId());
                        }
                    }

                    if($request->hasFile('image2')){
                        $upload = new sfPhastUpload('image2');
                        $upload->path(sfConfig::get('sf_upload_dir') . '/' . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') . '/content');
                        $upload->type('web_images');
                        $upload->save();
                        if($object->getImage2Id()){
                            $object->getImageRelatedByImage2Id()->updateFromUpload($upload);
                        }else{
                            $object->setImage2Id(Image::createFromUpload($upload)->getId());
                        }
                    }

                    if($request->hasFile('image3')){
                        $upload = new sfPhastUpload('image3');
                        $upload->path(sfConfig::get('sf_upload_dir') . '/' . (isset(static::$options['prefix']) ? static::$options['prefix'] : 'widget') . '/content');
                        $upload->type('web_images');
                        $upload->save();
                        if($object->getImage3Id()){
                            $object->getImageRelatedByImage3Id()->updateFromUpload($upload);
                        }else{
                            $object->setImage3Id(Image::createFromUpload($upload)->getId());
                        }
                    }

                };

                try{

                    if($item->isNew()){
                        $object = new Content();
                        $request->autofill($object);
                        $uploadImage($object);
                        $object->save();

                        $item->setContentId($object->getId());
                        $item->setHolderId($holder->getId());
                        $item->save();

                    }else{

                        if($object = $item->getObject()){
                            $uploadImage($object);
                            $request->autofill($object);
                            $object->save();
                        }else{
                            return $response->notfound();
                        }

                    }

                    $response->pk($item->getId());
                }
                catch (Exception $e){
                    return $response->error($e->getMessage());
                }
            }

		});




		$widgetGallery = new sfPhastBox('WidgetGallery');
		$widgetGallery->setTemplate('
            {#section Фотогалерея
                @button Default
            }
            {title, Название}
            {content:textarea, Описание}

            {#list WidgetGalleryList
                @wait Загрузка изображений доступна после сохранения галереи
                @caption Изображения
            }

            {#button Default}
        ');
		$widgetGallery->setReceive(function($request, $response){

			if(!HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(false !== $widget = $request->getItem('Widget')){
				if($widget && $object = $widget->getObject()){
					$response->parameter('gallery_id', $object->getId());
					$response->autofill($object);
				}else{
					return $response->notfound();
				}
			}

		});
		$widgetGallery->setSave(function($request, $response){

			if(!$holder = HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(!$item = $request->getItem('Widget', true)){
				return $response->notfound();
			}

			if(!$response->error()){

				if($item->isNew()){
					$object = new Gallery();
					$request->autofill($object);
					$object->save();

					$item->setGalleryId($object->getId());
					$item->setHolderId($holder->getId());
					$item->save();

				}else{

					if($object = $item->getObject()){
						$request->autofill($object);
						$object->save();
					}else{
						return $response->notfound();
					}

				}

				$response->pk($item->getId());

			}

		});




		$widgetVideo = new sfPhastBox('WidgetVideo');
		$widgetVideo->setTemplate('
            {#section Видео
                @button Default
            }
			{url, Ссылка на видео}
			{title, Название}
			{width, Ширина}
			{height, Высота}
			{autoplay:checkbox, Автостарт}
			{#section Результат
				@button Default
			}
		 	<div class="preview"></div>
			{#event
				@afterRender{
					if(box.data.code){
						node.find("div.preview").html(box.data.code);
					}
				}
			}
        ');
		$widgetVideo->setReceive(function($request, $response){

			if(!HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(false !== $widget = $request->getItem('Widget')){
				if($widget && $object = $widget->getObject()){
					$response->autofill($object);
					$response['code'] = $object->getCode();
				}else{
					return $response->notfound();
				}
			}else{
				$response['width'] = 600;
				$response['height'] = 400;
			}

            $response->placeholder('title', 'автоматически');

		});
		$widgetVideo->setSave(function(sfPhastRequest $request, $response) use ($user){

			if(!$holder = HolderPeer::retrieveByPK($request['#holder']))
				return $response->notfound();

			if(!$item = $request->getItem('Widget', true)){
				return $response->notfound();
			}

			if(!VideoPeer::validateURL($request['url'])){
				return $response->error('Указана неверная ссылка на видео');
			}

			if($item->isNew() && $title = VideoPeer::retrieveTitleFromURL($request['url'])){
			}else if($title = $request['title']){
			}else{
				return $response->error('Укажите название');
			}

			if(!$response->error()){

				if($item->isNew()){
					$object = new Video();
					$request->autofill($object);
					$object->setTitle($title);
					$object->save();

					$item->setVideoId($object->getId());
					$item->setHolderId($holder->getId());
					$item->save();

				}else{

					if($object = $item->getObject()){
						$request->autofill($object);
						$object->setTitle($title);
						$object->save();
					}else{
						return $response->notfound();
					}

				}

				$response->pk($item->getId());

			}

		});



        (new sfPhastList(
        //----------------------------------
            'WidgetGalleryList'
        //----------------------------------
        ))
        ->addControl(['caption' => 'Добавить фото', 'icon' => 'silk-picture-add', 'action' => '
            var $input = $("<input type=\"file\">"),
                $loader,
                total = 0,
                loaded = 0,
                startTimer;

            $input.damnUploader({
                url: "?widgetGalleryUpload="+list.parameters.gallery_id,
                fieldName:  "image",
                onSelect: function(file) {
                    if(!$loader){
                        $loader = node.after("<b style=\"margin-left:10px;\">Загрузка...</b>").next("b")
                    }
                    total++;
                    $input.duAdd({
                        file: file,
                        onComplete: function(successfully, data, errorCode) {
                            loaded++;
                            $loader.text("Загрузка " + loaded + "/" + total);
                        }
                    });

                    clearTimeout(startTimer);
                    startTimer = setTimeout(function(){
                        $input.duStart();
                    }, 100);

                    return false;
                },
                onAllComplete: function() {
                    $input.remove();
                    $loader.remove();
                    list.load();
                }
            }).click();

        '])
        ->setColumns('Название', '.', '.')
        ->setLayout('
            {GalleryRel
                @fields title, image:getPreviewTag
                @template :title, *, .visible, .delete
                @icon none
                @action &GalleryRelEditor
                @sort on

                :title{
                    return "<div>" + item.image + "</div>" + (item.title ? item.title : "Фотография #"+item.$pk);
                }
            }
        ')
        ->getPattern('GalleryRel')
        ->setCriteria(function(GalleryRelQuery $c) use ($request){
            if(!$gallery = GalleryPeer::retrieveByPK($request['#gallery_id'])){
                throw new sfPhastException('Галерея не найдена');
            }
            $c->filterByGallery($gallery);
        });


        (new sfPhastBox(
        //----------------------------------
            'GalleryRelEditor'
        //----------------------------------
        ))
        ->setTable('GalleryRel')
        ->setTemplate('
			{#section Фотография
				@button Default
			}

	        {title, Название}
            {image:file, Изображение
                @render return box.data.image;
                @hidden on
                '.

                (isset($options['gallery']['crop']) && $options['gallery']['crop']
                    ? ("@crop image_id\n@croptype " . implode("\n@croptype ", $options['gallery']['crop']))
                    : ''
                )

                .'
            }

			{#button Default}
		')
        ->setReceive(function ($request, $response, $item) {

            if ($item) {
                $response['image'] = $item->getImage()->getTag(200, 200);
            }

        });

	}

	public static function getHolder($request, $response, $item){
		if($item){
			$holder = $item->getHolder();
		}else{
			$holder = new Holder();
			$holder->save();
		}
		$response->parameter('holder', $holder->getId());
	}

	public static function setHolder($request, $response, $item){
		if(
			$request['#holder'] and
			$holder = HolderPeer::retrieveByPK($request['#holder']) and
			!$holder->getCompleted()
		){
			$holder->setObject($item);
		}
	}


    public static function PhastCropInitialize(){


        (new sfPhastBox(
        //----------------------------------
            'PhastCrop'
        //----------------------------------
        ))
        ->setTemplate('
			{#section Редактирование изображения
				@button Default
			}

			{type:radiogroup, Режим}

			{x:hidden, }
			{y:hidden, }
			{w:hidden, }
			{h:hidden, }

            <div class="phast-crop">
			    <div class="phast-crop-thumbnail"></div>
			    <div class="phast-crop-preview"></div>
			    <div class="phast-crop-original"></div>
			</div>

            {#event
                @afterRender{
                    var width, height;
                    var w = node.find(".phast-box-field-w input"),
                        h = node.find(".phast-box-field-h input"),
                        x = node.find(".phast-box-field-x input"),
                        y = node.find(".phast-box-field-y input");
                    var original = node.find(".phast-crop-original").html(box.data.$image);
                    var thumbnail = node.find(".phast-crop-thumbnail").html(box.data.$image);
                    var preview = node.find(".phast-crop-preview").html(box.data.$image);
                    var jcrop;
                    var showPreview = function (coords)
                    {
                        var rx = width / coords.w;
                        var ry = height / coords.h;
                        var img = original.find("img");

                        preview.find("img").css({
                            width: Math.round(rx * img.width()) + "px",
                            height: Math.round(ry * img.height()) + "px",
                            marginLeft: "-" + Math.round(rx * coords.x) + "px",
                            marginTop: "-" + Math.round(ry * coords.y) + "px"
                        });

                        preview.show();
                        thumbnail.hide();

                        x.val(coords.x);
                        y.val(coords.y);
                        w.val(coords.w);
                        h.val(coords.h);
                    }

                    original.find("img").Jcrop({
                        onChange: showPreview,
                        onSelect: showPreview,
                        aspectRatio: 1
                    }, function(){
                        jcrop = this;

                        node.find(".phast-box-field-type input").on("change", function(){
                            var typeId = $(this).val();
                            var type = box.data.$images[typeId-1];

                            width = type.width;
                            height = type.height;

                            preview
                                .width(type.width)
                                .height(type.height)
                                .hide();

                            thumbnail
                                .width(type.width)
                                .height(type.height)
                                .html(type.tag)
                                .show();


                            jcrop.release();
                            jcrop.setOptions({
                                aspectRatio: width/height
                            });

                        }).eq(0).prop("checked", true).change();
                    });



                }
            }

			{#button Default}
		')
        ->setReceive(function ($request, $response, $item) {

            $box = sfPhastUI::get($request['#box']);
            $field = $box->getField($request['#field']);
            $column = $field->getAttribute('crop');
            $types = $field->getCropTypes();
            if(!$image = ImagePeer::retrieveByPK($request['#id'])){
                throw new sfPhastException('Изображение не найдено');
            }
            $images = [];

            $select = [];
            foreach($types as $i => $type){
                if(!$i) continue;
                $images[] = [
                    'tag' => $image->getTag($type['width'], $type['height'], $type['scale'], $type['inflate']),
                    'width' => $type['width'],
                    'height' => $type['height'],
                ];
                $select[$i] = $type['title'];
            }

            $response['$image'] = $image->getTag();
            $response['$images'] = $images;
            $response->select('type', $select);

        })
        ->setSave(function ($request, $response, $item){
            $box = sfPhastUI::get($request['#box']);
            $field = $box->getField($request['#field']);
            $column = $field->getAttribute('crop');
            $types = $field->getCropTypes();
            if(!$image = ImagePeer::retrieveByPK($request['#id'])){
                throw new sfPhastException('Изображение не найдено');
            }
            $type = $types[$request['type']];

            $image->getURI($type['width'], $type['height'], $type['scale'], $type['inflate'], [
                'x' => $request['x'],
                'y' => $request['y'],
                'w' => $request['w'],
                'h' => $request['h'],
            ]);
        });

    }

    public static function PhastFileBrowserInitialize(){

        (new sfPhastBox(
        //----------------------------------
            'PhastFileBrowser'
        //----------------------------------
        ))
        ->setTemplate('
            {#section Менеджер файлов
                @button Close
            }

            {#list PhastFileBrowserList
                @ignorePk true
                @autoload true
            }

            {#button Close}
        ');


        (new sfPhastList(
        //----------------------------------
            'PhastFileBrowserList'
        //----------------------------------
        ))
        ->addControl(['caption' => 'Загрузить файл', 'icon' => 'silk-add', 'action' => '
            var $input = $("<input type=\"file\">"),
                $loader,
                total = 0,
                loaded = 0,
                startTimer;

            $input.damnUploader({
                url: "?fileBrowserUpload",
                fieldName:  "file",
                onSelect: function(file) {
                    if(!$loader){
                        $loader = node.after("<b style=\"margin-left:10px;\">Загрузка...</b>").next("b")
                    }
                    total++;
                    $input.duAdd({
                        file: file,
                        onComplete: function(successfully, data, errorCode) {
                            loaded++;
                            $loader.text("Загрузка " + loaded + "/" + total);
                        }
                    });

                    clearTimeout(startTimer);
                    startTimer = setTimeout(function(){
                        $input.duStart();
                    }, 100);

                    return false;
                },
                onAllComplete: function() {
                    $input.remove();
                    $loader.remove();
                    list.load();
                }
            }).click();

        '])
        ->setColumns('Название', '.')
        ->setLayout('
            {%File
                @template filename, :delete
                @icon fatcow-document-yellow
                @action{
                    list.box.filebrowser.win.document.forms[0].elements[list.box.filebrowser.field].value = "/uploads/assets/" + item.filename;
                    list.box.close();
                }

                :delete{
                    return $$.List.actionButton(":deleteAction", "silk-delete");
                }

                :deleteAction{
                    if(confirm("Удалить файл «"+item.filename+"»?"))
                    pattern.request("delete", item.filename, {
                        success: function(){
                            list.load();
                        }
                    });
                }
            }
        ')
        ->getPattern('File')
        ->setCustom(function(){

            $finder = new sfFinder();
            $files = $finder->in(sfConfig::get('sf_upload_dir') . '/assets');

            $output = [];
            foreach($files as $path){
                $output[] = [
                    'filename' => basename($path)
                ];
            }

            return $output;
        })
        ->setHandler('delete', function($pattern, $request){
            if(
                $filename = preg_replace('/(^\.+|[\/\\\\])/', '', $request['$pk'])
                and file_exists($filepath = sfConfig::get('sf_upload_dir') . '/assets/' . $filename))
            {
                unlink($filepath);
                return ['success' => 1];
            }
            return ['error' => 'Файл не найден'];
        });

    }

}
