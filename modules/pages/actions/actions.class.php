<?php

class pagesActions extends sfActions
{
	public function executeIndex(sfWebRequest $request)
	{
		$infoDecorator = function ($item) {
			$uri = $item->getURI();
			$info = '';
			if ($item->getRouteOptions())
				$info .= "<div class=\"notice\">{$item->getRouteOptions()}</div>";
			if (!$uri)
				$info .= '';
			else if ($item->getRoutePattern())
				$info .= "<b>{$uri}<span style=\"color:red\">{$item->getRoutePattern()}</span></b>";
			else if ('^' == $uri[0])
				$info .= "<span style=\"color:blue;font-size:10px\">" . mb_substr($uri, 1) . "</span>";
			else if ('#' == $uri[0])
				$info .= "<span style=\"color:green\">{$uri}</span>";
			else
				$info .= $uri;
			return $info;
		};

		/**
		 * @param sfPhastUser $user
		 */
		$user = $this->getUser();
		sfPhastUIWidget::initialize([
            'prefix' => 'page',
            'widgets' => ['image', 'video']
        ]);


		$list = new sfPhastList('PageList');
		$list->attach('#list');


		$list->setColumns('Заголовок', 'URI', '.', '.')
			->setLayout('
			{Page
				@relation Page.PARENT_ID
				@template title, info, .visible, .delete
				@action &PageEditor;
				@sort on
				@icon edit
			}
		')
		->addControl(['caption' => 'Добавить страницу', 'icon' => 'page-add', 'action' => '&PageEditor'])
		->usePattern('Page', function() use ($request, $infoDecorator){
			$this
			->addControl(['caption' => 'Добавить страницу', 'icon' => 'page-add', 'action' => '&PageEditor'])
			->setCriteria(function (Criteria $criteria) use ($request) {
				if ($request['#pk'])
					$criteria->addAnd(PagePeer::ID, $request['#pk'], Criteria::NOT_EQUAL);
			})
			->setDecorator(function (&$output, $item) use ($request, $infoDecorator) {
				$output['info'] = $infoDecorator($item);
				if ($item->getId() == $request['#relation'])
					$output['$class'] = 'active';
			});
		});



		(new sfPhastBox(
		//----------------------------------
			'PageEditor'
		//----------------------------------
		))
			->setTable('Page')
			->setTemplate('
			{#section Страница
				@button Default
			}

			{parent_id:choose, Предок
				@list PageParentIdChoose
				@caption $item->getParent()->getTitle()
				@empty Корневая страница
				@header Выберите родительскую страницу
			}

			{title, Заголовок
				@required Введите заголовок
			}
			{uri, URI}
			{content:textedit, Содержание}

			{#list WidgetList
			    @caption Список виджетов
			    @ignorePk true
			}

			{#section META-информация
				@button Default
			}

			{seo_title, Title}
			{seo_description, Description}
			{seo_keywords, Keywords}

            {#section Системные настройки
                @button Default
            }
            {route_pattern, Шаблон}
            {route_options, Опции}
            {route_requirements, Требования}

			{#button Default}


			{#event
				@afterRender{
					transliterateHandler(node);
				}
			}
		')
		->setReceive(function ($request, $response, $item) {

			if (!$item) {

				if ($request['#relation'] && $relation = PagePeer::retrieveByPK($request['#relation'])) {
					$response['parent_id'] = $relation->getId();
					$response['phast_choose_parent_id'] = $relation->getTitle();
				}
			}

			sfPhastUIWidget::getHolder($request, $response, $item);

		})
		->setSave(function ($request, $response, Page $item) use ($user) {

			if ($request['uri'] && strstr($request['uri'],'//') === false) {
				if ($request['uri'] != '/' && $request['uri'][0] != '#' && $request['uri'][0] != '^') $request['uri'] = '/' . trim($request['uri'], '/') . '/';
				$request['route_pattern'] = strtolower(trim($request['route_pattern'], '/'));
			}

			if ($request['uri'] && ($item->isNew() || $item->getURI() != $request['uri'] || $item->getRoutePattern() != $request['route_pattern']) && PagePeer::retrieveByRoute($request['uri'], $request['route_pattern'], $request['route_requirements']))
				$response->error('«URI страницы» должнен быть уникальным');

			if (!$response->error()) {
				$request->autofill($item);

				if ($item->isNew() || $item->isColumnModified(PagePeer::PARENT_ID)) {
					$item->fixPath();
				}

				$item->save();
				sfPhastUIWidget::setHolder($request, $response, $item);
				$response->pk($item->getId());
			}

		});


		(new sfPhastList(
		//----------------------------------
			'PageParentIdChoose'
		//----------------------------------
		))
        ->setColumns('Заголовок', 'URI')
        ->setLayout('
			{Page
				@relation Page.PARENT_ID
				@template title, info
				@action list.choose(item.$pk, item.title);
				@icon edit
			}
		')
        ->usePattern('Page', function() use ($request, $infoDecorator){
			$this
				->setCriteria(function (Criteria $criteria) use ($request) {
				if ($request['#pk'])
					$criteria->addAnd(PagePeer::ID, $request['#pk'], Criteria::NOT_EQUAL);
			})
				->setDecorator(function (&$output, $item) use ($request, $infoDecorator) {
				$output['info'] = $infoDecorator($item);
				if ($item->getId() == $request['#relation'])
					$output['$class'] = 'active';
			});
		});

	}
}
