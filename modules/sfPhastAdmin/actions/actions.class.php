<?php

class sfPhastAdminActions extends sfActions{

	public function executeIndex(sfPhastRequest $request){

		foreach(sfPhastAdmin::getModules() as $key => $module){
			if($key == 'index') continue;
			if(isset($module['credential']) && !$this->getUser()->hasCredential($module['credential'])) continue;
			$this->redirect("/admin/{$key}/");
		}

		$this->forward('sfPhastAdmin', 'forbidden');

	}

	public function executeAuth(sfPhastRequest $request){

        $this->aa = '<b>dfgdfg</b>';

		$user = $this->context['user'];

		$box = new sfPhastBox('SignIn');
		$box->enableAuto();
		$box->disableClose();
		$box->setTemplate(
			'
				{#section Авторизация}
				{key:text, Логин
					@required Введите логин
				}
				{password:password, Пароль
				    @required Введите пароль
				}

				<div class="phast-box-buttons">
					<button type="submit" class="phast-ui-button">Отправить</button>
				</div>
			'
		);
		$box->setSave(function($request, $response) use ($user){

			if($response->error()) return;

			if($sign = $user->verify($request['key'], $request['password'])){
				//if(!$userObject->getVisible() || !in_array('cp_access', $userObject->getPermissions())) return $response->error('У вас нет доступа в панель управления');
                $sign->authorize();
			}else{
				return $response->error('Неверный логин или пароль');
			}

			$response->documentReload();

		});

		$this->setTemplate('auth');
	}

	public function executeForbidden(sfPhastRequest $request){
		$this->setTemplate('forbidden');
	}

	public function executeRemodule(sfPhastRequest $request){
		sfPhastAdmin::getModules(true);
		return array('success' => true);
	}

	public function executeSignout(sfPhastRequest $request){
		$this->getUser()->terminate();
		return array('success' => true);
	}

	public function executeIconset(){
        $sections = [];

        $files = sfFinder::type('file')->name('*.png')->in(sfConfig::get('sf_web_dir') . '/sfPhastPlugin/iconset');
        foreach($files as $file){
			$file = explode('/', $file);
			array_splice($file, 0, -2);
			$name = str_replace('_', '-', substr($file[1], 0, -4));
			$filename = '/sfPhastPlugin/iconset/' . implode('/', $file);

            preg_match('/^(\w+)/', $name, $match);

            $sections[$match[1]][] = ['class' => $file[0] . '-' . $name, 'filename' => $filename];
		}

        $this->sections = $sections;

	}

}