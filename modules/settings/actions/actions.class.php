<?php

class settingsActions extends sfActions
{
	public function executeIndex(sfWebRequest $request){
		$user = $this->getUser();
        $editConstructor = $user->hasCredential('cp_settings_constructor');
        $devMode = $editConstructor && $request->hasParameter('dev');

        sfPhastUIWidget::initialize([
            'prefix' => 'settings'
        ]);

		$list = (new sfPhastList(
		//----------------------------------
			'SettingList', '#list'
		//----------------------------------
		))
        ->setColumns($editConstructor ? ['Название', 'Идентификатор', '.', '.'] : ['Название'])
		->setLayout('
            {SettingSection
                @relation SettingSection.SECTION_ID
                @template title'. ($editConstructor ? ', *, .visible, .delete' : '') .'
                @icon folder
                '. ($editConstructor && $devMode ? '
                    @sort on
                    @action &SettingSectionEditor
                ' : '') .'
            }

            {Setting
                @relation SettingSection.SECTION_ID
                @fields multiresult
                @template title'. ($editConstructor ? ', key, .visible, .delete' : '') .'
                @icon silk-cog
                @sort on
                @action {
                    if(item.multiresult){
                        $$.Box.createForList(
                            "SettingResultList",
                            "Коллекция элементов",
                            {setting: item.$pk}
                        ).open();
                    }else{
                        &SettingResultEditor(setting: item.$pk)
                    }
                }
            }

        ');

        (new sfPhastList(
        //----------------------------------
            'SettingResultList'
        //----------------------------------
        ))
        ->addControl(['caption' => 'Добавить элемент', 'icon' => 'silk-page-white-add', 'action' => '&SettingResultEditor'])
        ->setColumns('Название', '.', '.')
        ->setLayout('
            {SettingResult
                @template title:getTitle, .visible, .delete
                @action &SettingResultEditor(result:item.$pk)
                @icon silk-page-white
                @sort on
            }
        ')
        ->getPattern('SettingResult')
        ->setCriteria(function($c) use ($request){
            $c->filterBySettingId($request['#setting']);
        });
        
        (new sfPhastBox(
        //----------------------------------
            'SettingResultEditor'
        //----------------------------------
        ))
        ->setAutoClose()
        ->setTemplate('
            {#section Настройка
                @button Default
            }

            <div class="form-section">
                Загрузка...
            </div>

            {#event
                @beforeRender{
                    node.find(".form-section").html(this.data.form);
                    node.find(\'.WidgetGalleryList\').each(function(){
                        var list;
                        box.attachList(list = $$.List.create(\'WidgetGalleryList\', {
                            attach: $(this),
                            box: box,
                            autoload: false,
                            ignorePk: true,
                            parameters: {
                                gallery_id: $(this).data("gallery-id")
                            },
                            wait: \'\'
                        }));
                        list.load();
                    });
                }
            }



            {#button Default}
        ')
        ->setReceive(function($request, $response) use ($editConstructor){
            if(!$setting = $request->getItem('Setting', false, '#setting')){
                return $response->notfound();
            }

            $result = null;
            if($request['#result'] and !$result = $request->getItem('SettingResult', false, '#result')){
                return $response->notfound();
            }

            $result = $result ? $result : ($setting->getMultiresult() ? null : $setting->getResult());
            $response['form'] = ($form = $setting->renderForm($result, $editConstructor)) ? $form : 'Поля настройки не добавлены';
        })
        ->setSave(function ($request, $response) use($editConstructor){

            if(!$setting = $request->getItem('Setting', false, '#setting')){
                return $response->notfound();
            }

            $result = null;
            if($request['#result'] and !$result = $request->getItem('SettingResult', false, '#result')){
                return $response->notfound();
            }

            if($result) {
            }else if($setting->getMultiresult()){
                $result = new SettingResult;
                $result->setSetting($setting);
                $result->save();
            }else{
                $result = $setting->getResult();
            }

            if(!$response->error()){
                $setting->saveFields($result);
                $response->parameter('result', $result->getId());
            }

        });

		if($editConstructor){
            $list
                ->getPattern('Setting')
                ->setHandler('clone', function($pattern, $request, $item){
                    /** @var Setting $item */
                    if(!$item)
                        return array('error' => 'Элемент не найден');

                    $setting = $item->copy(true);
                    $setting->setTitle($setting->getTitle() . ' (копия)');
                    $setting->setKey('');
                    $setting->position(['after', $item]);
                    $setting->save();
                    return ['success' => true];
                });


            if($devMode){
                $list
                    ->addControl(['caption' => 'Добавить раздел', 'icon' => 'folder-add', 'action' => '&SettingSectionEditor'])
                    ->addControl(['caption' => 'Добавить настройку', 'icon' => 'silk-cog-add', 'action' => '&SettingEditor'])
                    ->getPattern('SettingSection')
                    ->addControl(['caption' => 'Добавить раздел', 'icon' => 'folder-add', 'action' => '&SettingSectionEditor'])
                    ->addControl(['caption' => 'Добавить настройку', 'icon' => 'silk-cog-add', 'action' => '&SettingEditor'])
                    ->getList()
                    ->getPattern('Setting')
                    ->addControl(['caption' => 'Управление настройкой', 'icon' => 'fatcow-setting-tools', 'action' => '&SettingEditor(pk:item.$pk)'])
                    ->addControl(['caption' => 'Дублировать настройку', 'icon' => 'silk-cog-go', 'action' => '
                        pattern.request("clone", item.$pk, {success: function(){
							list.load();
						}});
                    ']);

            }

            (new sfPhastBox(
            //-------------------------
                'SettingSectionEditor'
            //-------------------------
            ))
            ->setAutoClose()
            ->setTable('SettingSection')
            ->setTemplate('

                {#section Раздел
                    @button Default
                }

                {section_id:choose, Родительский раздел
                    @list SettingParentIdChoose
                    @caption $item->getSettingSection()->getTitle()
                    @empty Без раздела
                    @header Выберить родительский раздел
                }

                {title, Название
                    @required Введите название
                }

                {#button Default}

		    ')
            ->setReceive(function($request, $response, $item){
                if(!$item){
                    if($request['#relation'] and $relation = SettingSectionPeer::retrieveByPK($request['#relation'])) {
                        $response['section_id'] = $relation->getId();
                        $response['phast_choose_section_id'] = $relation->getTitle();
                    }
                }
            });





            (new sfPhastBox(
            //-------------------------
                'SettingEditor'
            //-------------------------
            ))
            ->setTable('Setting')
            ->setTemplate('

                {#section Настройка
                    @button Default
                }

                {section_id:choose, Родительский раздел
                    @list SettingParentIdChoose
                    @caption $item->getSettingSection()->getTitle()
                    @empty Без раздела
                    @header Выберить родительский раздел
                    @required Выберите раздел
                }

                {title, Название
                    @required Введите название
                }

                {key, Идентификатор
                    @required Введите идентификатор
                }

                {multiresult:checkbox, Коллекция элементов}

                {#list SettingFieldList
                    @wait Для редактирования полей сохраните настройку
                    @caption Настройка полей
                }

                {#button Default}

            ')
            ->setReceive(function($request, $response, $item){
                if(!$item){
                    if($request['#relation'] and $relation = SettingSectionPeer::retrieveByPK($request['#relation'])) {
                        $response['section_id'] = $relation->getId();
                        $response['phast_choose_section_id'] = $relation->getTitle();
                    }
                }
            });



			(new sfPhastList(
			//----------------------------------
				'SettingFieldList'
			//----------------------------------
			))
			->addControl(['caption' => 'Добавить поле', 'icon' => 'silk-add', 'action' => '&SettingFieldEditor'])
			->setColumns('Название', 'Идентификатор', '.', '.')
			->setLayout('
	            {SettingField
	                @template title, key, .visible, .delete
	                @action &SettingFieldEditor
	                @icon fatcow-layout
	                @sort on
	            }

	        ')
			->getPattern('SettingField')
			->setCriteria(function (SettingFieldQuery $query) use ($request) {
				$query->filterBySettingId($request['#owner']);
			})
            ->setDecorator(function(&$output, SettingField $item){
                switch($item->getTypeName()){
                    case 'text':
                        $icon = 'fatcow-text-allcaps';
                        break;
                    case 'textarea':
                        $icon = 'fatcow-text-align-justity';
                        break;
                    case 'textedit':
                        $icon = 'fatcow-text-large-cap';
                        break;
                    case 'select':
                        $icon = 'fatcow-combo-box';
                        break;
                    case 'checkbox':
                        $icon = 'fatcow-check-box';
                        break;
                    case 'image':
                        $icon = 'silk-image';
                        break;
                    case 'gallery':
                        $icon = 'silk-images';
                        break;
                    case 'file':
                        $icon = 'silk-drive';
                        break;
                    default:
                        $icon = 'fatcow-layout';
                }
                $output['$icon'] = $icon;
            });


			(new sfPhastBox(
			//-------------------------
				'SettingFieldEditor'
			//-------------------------
			))
            ->setAutoClose()
			->setTable('SettingField')
			->setTemplate('

			    {#section Элемент настройки
					@button Default
				}

			    {type_id:select, Тип
			       @required Выберите тип
			    }

				{title, Название
					@required Укажите название
				}

				{key, Идентификатор
					@required Укажите идентификатор
				}

				{#list SettingOptionList
					@caption Значения списка
					@wait Сохраните объект
				}

				{#event
					@afterRender{
						node.find("select[name=type_id]").on("change", function(e){
		                    if($(this).val() == 5){
			                    node.find("[class^=\"SettingOptionList\"]").closest("dl").show();
			                }else{
			                    node.find("[class^=\"SettingOptionList\"]").closest("dl").hide();
			                }
						})
						.trigger("change");
					}
				}

				{#button Default}
			')
			->setReceive(function($request, $response, $item){
				$response->select('type_id', SettingFieldPeer::getTypes(), null, null);
			})
			->setSave(function ($request, $response, SettingField $item){
			    $response->check();
				$request->autofill($item);
				$item->setSettingId($request['#owner']);
				$item->save();
				$response->pk($item);

			});


			(new sfPhastList(
			//----------------------------------
				'SettingOptionList'
			//----------------------------------
			))
			->addControl(['caption' => 'Добавить значение', 'icon' => 'silk-application-form-add', 'action' => '&SettingOptionEditor'])
			->setColumns('Название', '.')
			->setLayout('
	            {SettingOption
	                @template title, .delete
	                @action &SettingOptionEditor
	                @icon silk-application-form
	                @sort on
	            }

	        ')
			->getPattern('SettingOption')
			->setCriteria(function (SettingOptionQuery $query) use ($request) {
				$query->filterByFieldId($request['#owner']);
			});


			(new sfPhastBox(
			//-------------------------
				'SettingOptionEditor'
			//-------------------------
			))
			->setTable('SettingOption')
			->setTemplate('

			    {#section Значение для выбора из списка
					@button Default
				}


				{title, Название
					@required Укажите название
				}

				{#button Default}
			')
			->setReceive(function($request, $response, $item){
			})
			->setSave(function ($request, $response, SettingOption $item){
				$response->check();
				$request->autofill($item);
				$item->setFieldId($request['#owner']);
				$item->save();
				$response->pk($item);

			});



			(new sfPhastList(
			//----------------------------------
				'SettingParentIdChoose'
			//----------------------------------
			))
			->setColumns('Заголовок')
			->setLayout('
				{SettingSection
					@relation SettingSection.SECTION_ID
					@template title
					@action list.choose(item.$pk, item.title);
					@icon folder
				}
			');
		}

        if($editConstructor){
            if($devMode){
                $list->addControl(['caption' => 'Обычный режим', 'icon' => 'fatcow-clipboard-invoice', 'action' => 'document.location.href = "?";']);
            }else{
                $list->addControl(['caption' => 'Режим разработчика', 'icon' => 'fatcow-setting-tools', 'action' => 'document.location.href = "?dev";']);
            }
        }
	}
}
