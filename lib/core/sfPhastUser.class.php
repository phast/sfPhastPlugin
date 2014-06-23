<?php

class sfPhastUser extends sfBasicSecurityUser
{
	const USERID_NAMESPACE = 'symfony/user/sfUser/userid';
	const AUTHTIME_NAMESPACE = 'symfony/user/sfUser/authtime';
	const CREDENTIALS_CHECK_NAMESPACE = 'symfony/user/sfUser/credentialCheck';
	protected $user;

	public function initialize(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
	{
		parent::initialize($dispatcher, $storage, $options);

 		if(!$this->isAuthenticated()){
			$cookie_id = base_convert(sfContext::getInstance()->getRequest()->getCookie('__utna'), 32, 16);
			$cookie_hash = sfContext::getInstance()->getRequest()->getCookie('__utnb');

			if($cookie_id && $cookie_hash){
                if($session = UserSessionQuery::create()->filterById($cookie_id)->filterByHash($cookie_hash)->findOne()){
                    $this->authorize($session->getUserSign());
                }else{
                    $this->terminate();
                }
			}
		}
  
        if($this->isAuthenticated()){
            if($this->storage->read(self::CREDENTIALS_CHECK_NAMESPACE) < time() - 60){
                $this->storage->write(self::CREDENTIALS_CHECK_NAMESPACE, time());
                $this->clearCredentials();
                $this->addCredentials($this->getObjectModel()->getCredentials());
            }
        }


	}

	public function getObjectModel(){
		if(!$this->isAuthenticated()) return;
		$object = $this->user ? $this->user : $this->user = UserPeer::retrieveByPK($this->storage->read(self::USERID_NAMESPACE));
		if($object){
			return $object;
		}else{
			$this->terminate();
			throw new sfPhastException('Object model for user not found');
		}

	}

	public function generatePassword($password, $salt){
		return hash('sha256', sha1($salt.$password) . sfConfig::get('sf_user_password_salt'));
	}

	public function generateHash($md5 = false){
		$hash = '';
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
		$numberChars = strlen($chars) - 1;
		for($i = 0; $i < 32; $i++) $hash .= $chars[mt_rand(0, $numberChars)];
		return $md5 ? md5($hash . sfConfig::get('sf_user_password_salt')) : $hash;
	}

    /**
     * @param $key
     * @param $password
     * @return bool|null|UserSign
     */
    public function verify($key, $password){
        if($sign = UserSignQuery::create()->findOneByKey($key)){
            if($sign->getPassword() == $this->generatePassword($password, $sign->getSalt())){
                return $sign;
            }
            return false;
        }
        return;
	}

    /**
     * @param UserSign $sign
     * @param bool $remember Set cookie
     * @param bool $update Update session
     * @return UserSession
     */
    public function authorize(UserSign $sign, $remember = true, $update = false){

        if(!$session = UserSessionQuery::create()->filterByUserSign($sign)->findOne()){
            $session = new UserSession();
            $session->setUserId($sign->getUserId());
            $session->setUserSign($sign);
        }

        if($session->isNew() or $update){
            $session->setHash($this->generateHash(true));
        }

        $session->save();

		$this->terminate();
		$this->storage->write(self::USERID_NAMESPACE, $sign->getUserId());
		$this->storage->write(self::CREDENTIALS_CHECK_NAMESPACE, time());
		$this->setAuthenticated(true);
		$this->addCredentials($this->getObjectModel()->getCredentials());

		if($remember){
			$host = '.' . sfConfig::get('host');
			$response = sfContext::getInstance()->getResponse();
			$request =  sfContext::getInstance()->getRequest();
			sfContext::getInstance()->getResponse()->setCookie('__utna', base_convert($session->getId(), 16, 32), time() + 315360000, '/', $host);
			sfContext::getInstance()->getResponse()->setCookie('__utnb', $session->getHash(), time() + 315360000, '/', $host);
		}

        return $session;
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
		if($user = $this->getObjectModel()){
			return call_user_func_array(array($user, $method), $arguments);
		}
	}

}