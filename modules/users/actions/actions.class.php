<?php

class usersActions extends sfActions
{
	public function executeIndex(sfWebRequest $request)
	{

		$user = $this->getUser();

		/**
		 * @list UserList
		 */
		$list = new sfPhastList('PageList');
		$list->attach('#list');
		$list->addControl(array('caption' => 'Группы пользователей', 'icon' => 'group-user', 'action' => '&GroupsEditor'));
		$list->addControl(array('caption' => 'Права доступа', 'icon' => 'key', 'action' => '&PermissionsEditor'));
		$list->addControl(array('caption' => 'Добавить пользователя', 'icon' => 'user-add', 'action' => '&UserEditor'));
		$list->setColumns('Пользователь', 'Дата регистрации', '.');
		$list->setLayout(
			'
				{User
					@template title:getName(), date:getCreatedDate, .delete
					@action &UserEditor
					@icon user
					@limit 100
				}
			'
		);
		$list->setFilters(function ($request, $response) {
			$response->select('group_id', UserGroupQuery::create()->orderByTitle()->find(), 'getId', 'getTitle', 'Все');
			return
				'
					{group_id:select, Группа}
					{text, Поиск}
				';
		});

		/**
		 * @list UserList
		 * @pattern User
		 */
		$list['User']
		->setCriteria(function (Criteria $criteria) use ($request) {
			if($request['#group_id']){
				$criteria->addJoin(UserGroupRelPeer::USER_ID, UserPeer::ID, Criteria::LEFT_JOIN);
				$criteria->add(UserGroupRelPeer::GROUP_ID, $request['#group_id']);
				$criteria->setDistinct();
			}
			if($request['#text']){
				$c = $criteria->getNewCriterion(UserPeer::NAME, '%'.$request['#text'].'%', Criteria::LIKE);
				$c->addOr($criteria->getNewCriterion(UserPeer::EMAIL, '%'.$request['#text'].'%', Criteria::LIKE));
				$criteria->add($c);
			}

		})
        ->setDecorator(function (&$output, User $item) {

        })
		->addControl(array('caption' => 'Группы', 'icon' => 'group-user', 'action' => "
		  	$$.Box.create({
				\$afterOpen: function(){
					this.attachList($$.List.create('UserGroupList', {
						attach: this.getNode().find('div.phast-custom-list-groups'),
						box: this,
						parameters: {pk: item.\$pk}
					}));
					this.attachList($$.List.create('UserCredentialList', {
						attach: this.getNode().find('div.phast-custom-list-permissions'),
						box: this,
						parameters: {pk: item.\$pk}
					}));
				},
				template: $$.Box.template.listcustom('Группы пользователя «'+item.title+'»', '<div style=\"width: 50%; float: right;\" class=\"phast-custom-list-permissions\"></div><div style=\"width: 49%; margin: 0;\" class=\"phast-custom-list-groups\"></div>')
			}).open();
		"))
        ;


		/**
		 * @box UserEditor
		 */
		$editor = new sfPhastBox('UserEditor');
		$editor
		->setTable('User')
		->setTemplate(
			'
				{#section Пользователь
					@button Default
				}

				{name, Имя
					@required Введите имя
				}

				{#list UserSignList
					@caption Способы авторизации
					@wait Сначала сохраните пользователя
				}

				{#list UserSessionList
					@caption Сессии пользователя
					@wait Сначала сохраните пользователя
				}
				{#button Default}
			'
		)
		->setSave(function ($request, $response, User $item) use ($user) {

			if (!$response->error()) {
				$request->autofill($item);
				$item->save();
				$response->pk($item->getId());
			}

		});

		/**
		 * @list UserSignList
		 */
		(new sfPhastList(
			'UserSignList'
		))
		->setColumns('Идентификатор', 'Создан', 'Изменен', '.', '.')
		->addControl(['caption' => 'Добавить способ авторизации', 'icon' => 'fugue-key--plus', 'action' => '&UserSignEditor'])
		->setLayout(
			'
				{UserSign
					@template key, created_at:getCreatedDate, updated_at:getUpdatedDate, .visible, .delete
					@action &UserSignEditor
					@icon fugue-key
				}
			'
		)->getPattern('UserSign')
		->setCriteria(function (UserSignQuery $criteria) use ($request) {
			 $criteria->filterByUserId($request['#owner']);
		});


		/**
		 * @box UserSignEditor
		 */
		$editor = new sfPhastBox('UserSignEditor');
		$editor
			->setTable('UserSign')
			->setTemplate(
			'
				{#section Способ авторизации
					@button Default
				}

				{key, Идентификатор
		        	@required Введите идентификатор
				}

				{password:password, Изменить пароль}
				{repassword:password, Повторите пароль}

				{#button Default}
			'
		)->setSave(function ($request, $response, UserSign $item) use ($user) {
			if($item->isNew() && !$request['password']) return $response->error('Задайте пароль');

			if($item->isNew() || $request['key'] != $item->getKey()){
				if (UserSignQuery::create()->findOneByKey($request['key']))
					return $response->error('Пользователь с указанным идентификатором уже существует');
			}

			if($request['password'] && $request['password'] != $request['repassword']){
				return $response->error('Пароли не совпадают');
			}

			if(!$response->error()){
				$request->autofill($item);

				if($request['password']) {
					$item->setSalt($user->generateHash());
					$item->setPassword($user->generatePassword($request['password'], $item->getSalt()));
				}

				$item->setUserId($request['#owner']);
				$item->setKey($request['key']);
				$item->save();
				$response->pk($item->getId());
			}
		});

		/**
		 * @list UserSessionList
		 */
		(new sfPhastList(
			'UserSessionList'
		))
			->setColumns('Идентификатор способа авторизации', 'Создана', 'Изменена', '.')
			->setLayout(
			'
				{UserSession
					@template key:getSignKeyTitle, created_at:getCreatedDate, updated_at:getUpdatedDate, .delete
					@icon fatcow-session-idle-time
				}
			'
		)->getPattern('UserSession')
			->setCriteria(function (UserSessionQuery $criteria) use ($request) {
			$criteria->filterByUserId($request['#owner']);
		});

		/**
		 * @list UserCredentialList
		 */
		$list = new sfPhastList('UserCredentialList');
		$list->setColumns('Право доступа', 'Идентификатор');
		$list->setLayout(
			'
				{UserCredentialSection
		            @template title, *
		            @sort on
		            @icon silk-folder-key
				}

				{UserCredential
					@relation UserCredentialSection.SECTION_ID
					@template title, name
					@icon key
				}
			'
		);
		$list->setPrepare(function (sfPhastList $list) use ($request) {
			$permissions = array();
			if ($item = $request->getItem('User')) {
				$permissions = $item->getCredentials();
			}
			;
			$list->setParameter('permissions', $permissions);
		});
		$list['UserCredential']
			->setDecorator(function (&$output, $item, $pattern) {
				$permissions = $pattern->getList()->getParameter('permissions');
				if (in_array($item->getName(), $permissions))
					$output['$class'] = 'active';
		});

		/**
		 * @list UserGroupList
		 */
		$list = new sfPhastList('UserGroupList');
		$list->setColumns('Группа', 'Идентификатор');
		$list->setLayout(
			'
			    {UserGroupSection
			        @template title, name
			        @icon folder
			    }

				{UserGroup
				    @relation UserGroupSection.SECTION_ID
					@template title, name
					@action{
						var rowNode = node.closest("tr");
						if(rowNode.hasClass("active")){
							rowNode.removeClass("active");
						}else{
							rowNode.addClass("active");
						}
						this.pattern.request("choose", item.$pk, {success: function(){
							list.box.getInnerList(1).load();
						}});
					}
					@icon group-user
				}
			'
		);
		$list->setPrepare(function (sfPhastList $list) use ($request) {
			$activePks = array();
			if ($item = $request->getItem('User')) {
				foreach ($item->getUserGroupRels() as $rel) {
					$activePks[] = $rel->getGroupId();
				}
			}
			$list->setParameter('activePks', $activePks);
		});
		$list['UserGroup']
		->setHandler('choose', function ($pattern, $request, $group) {
			if ($group && $user = $request->getItem('User')) {
				if ($rel = UserGroupRelQuery::create()->filterByGroupId($group->getId())->filterByUserId($user->getId())->findOne()) {
					$rel->delete();
				} else {
					$rel = new UserGroupRel();
					$rel->setGroupId($group->getId());
					$rel->setUserId($user->getId());
					$rel->save();
				}
			}
			return array('success' => 1);
		})
		->setDecorator(function (&$output, $item, $pattern) {
			$activePks = $pattern->getList()->getParameter('activePks');
			if (in_array($item->getId(), $activePks))
				$output['$class'] = 'active';
		});


		/**
		 * @box PermissionsEditor
		 */
		$editor = new sfPhastBox('PermissionsEditor');
		$editor->setTemplate(
			'
				{#section Права доступа
					@button Close
				}

		        {#list PermissionsList
		            @autoload true
		        }

				{#button Close}
			'
		);

		/**
		 * @list PermissionsList
		 */
		$list = new sfPhastList('PermissionsList');
		$list->addControl(array('caption' => 'Добавить право доступа', 'icon' => 'key-add', 'action' => '&CredentialEditor'));
		$list->addControl(array('caption' => 'Добавить группу прав доступа', 'icon' => 'silk-folder-key', 'action' => '&CredentialSectionEditor'));
		$list->setColumns('Описание', 'Идентификатор', '.');
		$list->setLayout(
			'
				{UserCredentialSection
		            @template title, *, .delete
		            @action &CredentialSectionEditor
		            @sort on
		            @icon silk-folder-key
				}
				{UserCredential
					@relation  UserCredentialSection.SECTION_ID
					@template title, name, .delete
					@action &CredentialEditor
					@icon key
					@sort on
				}
			'
		);
		$list->getPattern('UserCredentialSection')
			->addControl(['caption' => 'Добавить право доступа', 'icon' => 'key-add', 'action' => '&CredentialEditor']);


		/**
		 * @box CredentialEditor
		 */
		$editor = new sfPhastBox('CredentialEditor');
		$editor->setTable('UserCredential');
		$editor->setTemplate(
			'
				{#section Право доступа
					@button Default
				}

		        {section_id:select, Группа прав
		        	@required Укажите группу
		        }

		        {title, Описание
		            @required Введите описание
		        }

		        {name, Идентификатор
	                @required Введите идентификатор
		        }

				{#button Default}
			'
		)->setReceive(function ($request, $response, $item) {
			if (!$item && $request['#relation']) {
				$response['section_id'] =  $request['#relation'];
			}

			$response->select('section_id', UserCredentialSectionQuery::create()->find(), 'getId', 'getTitle', true);
		});

		/**
		 * @box CredentialSectionEditor
		 */
		$editor = new sfPhastBox('CredentialSectionEditor');
		$editor->setTable('UserCredentialSection');
		$editor->setTemplate(
			'
				{#section Группа прав доступа
					@button Default
				}

		        {title, Название}

				{#button Default}
			'
		);


		/**
		 * @box GroupsEditor
		 */
		$editor = new sfPhastBox('GroupsEditor');
		$editor->setTemplate(
			'
				{#section Группы пользователей
					@button Close
				}

		        {#list GroupsList
		            @autoload true
		        }

				{#button Close}

			'
		);

		/**
		 * @list GroupsList
		 */
		$list = new sfPhastList('GroupsList');
		$list->addControl(array('caption' => 'Добавить секцию', 'icon' => 'silk-folder-add', 'action' => '&GroupSectionEditor'));
		$list->addControl(array('caption' => 'Добавить группу', 'icon' => 'group-user-add', 'action' => '&GroupEditor'));
		$list->setColumns('Группа', 'Идентификатор', 'Автоназначение', '.');
		$list->setLayout(
			'
			    {UserGroupSection
			        @template title, name, assing:getAssignCaption, .delete
			        @icon folder
                    @action &GroupSectionEditor
                    @sort on
			    }

				{UserGroup
				    @relation UserGroupSection.SECTION_ID
					@template title, name, condition, .delete
					@action &GroupEditor
					@icon group-user
					@sort on
				}
			'
		);
        $list['UserGroupSection']->addControl(array('caption' => 'Добавить группу', 'icon' => 'group-user-add', 'action' => '&GroupEditor'));

        /**
		 * @list GroupsList
		 * @pattern UserGroup
		 */
		$list['UserGroup']->addControl(array('caption' => 'Права доступа', 'icon' => 'key', 'action' => "
		  	$$.Box.create({
				\$afterOpen: function(){
					this.attachList($$.List.create('GroupPermissionList', {
						attach: this.getNode().find('div.phast-custom-list'),
						box: this,
						parameters: {pk: item.\$pk}
					}));
				},
				template: $$.Box.template.listonly('Права доступа группы «'+item.title+'»')
			}).open();
		"));


		/**
		 * @box GroupEditor
		 */
		$editor = new sfPhastBox('GroupEditor');
		$editor->setTable('UserGroup');
		$editor->setTemplate(
			'
				{#section Группа пользователей
					@button Default
				}

		        {section_id:select, Секция
		        	@required Укажите секцию
		        }

		        {title, Описание}
		        {name, Идентификатор}
		        {condition, Условие назначения}

				{#button Default}

			'
		)
        ->setReceive(function ($request, $response, $item) {
            if (!$item && $request['#relation']) {
                $response['section_id'] =  $request['#relation'];
            }

            $response->select('section_id', UserGroupSectionQuery::create()->orderByPosition()->find(), 'getId', 'getTitle');
        });


        /**
         * @box GroupSectionEditor
         */
        $editor = new sfPhastBox('GroupSectionEditor');
        $editor->setTable('UserGroupSection');
        $editor->setTemplate(
            '
				{#section Секция
					@button Default
				}

		        {title, Описание
		            @required Введите описание
		        }

		        {name, Идентификатор
	                @required Введите идентификатор
		        }

		        {assign_mode:select, Режим назначения}
		        {assign_auto:select, Режим автоназначения}

				{#button Default}
			'
        );
        $editor->setReceive(function($request, $response, $item){
            $response->select('assign_mode', UserGroupSectionPeer::getAssignModeList());
            $response->select('assign_auto', UserGroupSectionPeer::getAssignAutoList());
        });

		/**
		 * @list GroupPermissionList
		 */
		$list = new sfPhastList('GroupPermissionList');
		$list->setColumns('Описание', 'Идентификатор', '.');
        $list->addControl(array('caption' => 'Добавить право доступа', 'icon' => 'key-add', 'action' => '&CredentialEditor'));
        $list->addControl(array('caption' => 'Добавить группу прав доступа', 'icon' => 'silk-folder-key', 'action' => '&CredentialSectionEditor'));
		$list->setLayout(
			'
				{UserCredentialSection
		            @template title, *, .delete
		            @sort on
		            @icon silk-folder-key
				}
				{UserCredential
					@relation UserCredentialSection.SECTION_ID
					@template title, name, .delete
					@action{
						var rowNode = node.closest("tr");
						if(rowNode.hasClass("active")){
							rowNode.removeClass("active");
						}else{
							rowNode.addClass("active");
						}
						this.pattern.request("choose", item.$pk);
					}
					@icon key
				}
			'
		);
		$list->setPrepare(function (sfPhastList $list) use ($request) {
			$activePks = array();
			if ($item = $request->getItem('UserGroup')) {
				foreach ($item->getUserCredentialRels() as $rel) {
					$activePks[] = $rel->getCredentialId();
				}
			}
			$list->setParameter('activePks', $activePks);
		});
        $list['UserCredentialSection']
            ->addControl(['caption' => 'Добавить право доступа', 'icon' => 'key-add', 'action' => '&CredentialEditor'])
            ->addControl(['caption' => 'Редактировать', 'icon' => 'silk-pencil', 'action' => '&CredentialSectionEditor(pk:item.$pk)']);

		$list['UserCredential']
            ->addControl(['caption' => 'Редактировать', 'icon' => 'silk-pencil', 'action' => '&CredentialEditor(pk:item.$pk)'])
			->setHandler('choose', function ($pattern, $request, $permission) {
			if ($permission && $group = $request->getItem('UserGroup')) {
				if ($rel = UserCredentialRelQuery::create()->filterByGroupId($group->getId())->filterByCredentialId($permission->getId())->findOne()) {
					$rel->delete();
				} else {
					$rel = new UserCredentialRel();
					$rel->setGroupId($group->getId());
					$rel->setCredentialId($permission->getId());
					$rel->save();
				}
			}
			return array('success' => 1);
		})
			->setDecorator(function (&$output, $item, $pattern) {
			$activePks = $pattern->getList()->getParameter('activePks');
			if (in_array($item->getId(), $activePks))
				$output['$class'] = 'active';
		});


	}
}
