<?php

class sfPhastUser extends sfBasicSecurityUser
{
	const PASSWORD_GRAVEL = '98;lsdk.jfg_Susf6sdf/sdf.isdfh';
	const PASSWORD_MERCURY = 's4.844sOe/pOdirn/www5xue,,eoZ';
	const USERID_NAMESPACE = 'symfony/user/sfUser/userid';
	const AUTHTIME_NAMESPACE = 'symfony/user/sfUser/authtime';
	const CREDENTIALS_CHECK_NAMESPACE = 'symfony/user/sfUser/credentialCheck';
	protected $user;

	public function initialize(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
	{
		parent::initialize($dispatcher, $storage, $options);

		if(!$this->isAuthenticated()){
			$cookie_username = base64_decode(sfContext::getInstance()->getRequest()->getCookie('__utna'));
			$cookie_password = sfContext::getInstance()->getRequest()->getCookie('__utnb');

			if($cookie_username && $cookie_password){
				if(($user = UserPeer::retrieveByUsername($cookie_username)) && $user->getHash() === $cookie_password){
					$this->authorize($user);
				}else{
					$this->terminate();
				}
			}
		}else{
			if($this->storage->read(self::CREDENTIALS_CHECK_NAMESPACE) < time() - 60){
				if($this->getVisible()){
					$this->storage->write(self::CREDENTIALS_CHECK_NAMESPACE, time());
					$this->clearCredentials();
					$this->addCredentials($this->getPermissions());
				}else{
					$this->terminate();
				}
			}
		}

	}

	public function getObject(){
		if(!$this->isAuthenticated()) return;
		$object = $this->user ? $this->user : $this->user = UserPeer::retrieveByPK($this->storage->read(self::USERID_NAMESPACE));
		if($object){
			return $object;
		}else{
			$this->terminate();
			throw new sfPhastException('Error getting the object user');
		}

	}

	public function create($username, $password){
		$salt = $this->generateHash();
		$user = new User();
		$user->setUsername($username);
		$user->setPassword($this->generatePassword($password, $salt));
		$user->setSalt($salt);
		$user->save();
		return $user;
	}

	public function generatePassword($password, $salt){
		return hash('sha256', self::PASSWORD_GRAVEL . sha1($salt.$password) . self::PASSWORD_MERCURY);
	}

	public function generateHash($md5 = false){
		$hash = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
		$numberChars = strlen($chars) - 1;
		for($i = 0; $i < 32; $i++) $hash .= $chars[mt_rand(0, $numberChars)];
		return $md5 ? md5($hash . self::PASSWORD_MERCURY) : $hash;
	}

	public function verify($username, $password){
		if($user = UserPeer::retrieveByUsername($username)){
			if($this->generatePassword($password, $user->getSalt()) == $user->getPassword()) return $user;
		}
		return false;
	}

	public function verifyBy($column, $value, $password){
		if($user = UserQuery::create()->findOneBy($column, $value)){
			if($this->generatePassword($password, $user->getSalt()) == $user->getPassword()) return $user;
		}
		return false;
	}

	public function authorize($user, $generateHash = false, $remember = true){

		if($user->getHash() && !$generateHash){
			$hash = $user->getHash();
		}else{
			$user->setHash($this->generateHash(true));
			$user->save();
		}

		$this->terminate();
		$this->storage->write(self::USERID_NAMESPACE, $user->getId());
		$this->storage->write(self::CREDENTIALS_CHECK_NAMESPACE, time());
		$this->setAuthenticated(true);
		$this->addCredentials($this->getPermissions());

		if($remember){
			$host = '.' . sfConfig::get('host');
			$response = sfContext::getInstance()->getResponse();
			$request =  sfContext::getInstance()->getRequest();
			sfContext::getInstance()->getResponse()->setCookie('__utna', base64_encode($user->getUsername()), time() + 315360000, '/', $host);
			sfContext::getInstance()->getResponse()->setCookie('__utnb', $user->getHash(), time() + 315360000, '/', $host);
		}
	}

	public function terminate(){
		$this->setAuthenticated(false);
		$host = '.' . sfConfig::get('host');
		$response = sfContext::getInstance()->getResponse();
		$request =  sfContext::getInstance()->getRequest();
		$response->setCookie('__utna', '', time()-1, '/', $host);
		$response->setCookie('__utnb', '', time()-1, '/', $host);
	}

	public function removeAttribute($attr){
		$this->getAttributeHolder()->remove($attr);
	}

	public function __call($method, $arguments){
		if($user = $this->getObject()){
			return call_user_func_array(array($user, $method), $arguments);
		}
	}

}