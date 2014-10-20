<?php

class phastInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ));

        $this->namespace = 'phast';
        $this->name = 'install';
        $this->briefDescription = '';
        $this->detailedDescription = <<<EOF
The [mailing:schedule|INFO] task does things.
Call it with:

  [php symfony mailing:schedule|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {

        $this->log('');
        $this->log('Phast CMS installation');
        $this->runTask('phast:database');
        $this->runTask('propel:build-sql');
        $this->runTask('propel:insert-sql');
        $this->runTask('propel:build-model');
        $this->runTask('propel:data-load');

        $validator = new sfValidatorString();

        $this->log('');
        $this->log('Configure project');
        $host = $this->askAndValidate('host:', $validator);

        $this->log('>> Write to config/app.yml');
        $filepath = sfConfig::get('sf_config_dir') . '/app.yml';
        file_put_contents($filepath, preg_replace('/project\.dev/', $host, file_get_contents($filepath)));


        $this->log('');
        $this->log('Configure admin user');


        $username = $this->ask('username (= admin):', 'QUESTION', 'admin');
        $this->log('');
        $password = $this->askAndValidate('password:', $validator);
        $salt = md5(uniqid(mt_rand(), true));
        $this->log('');


        $this->log('>> Write to config/factories.yml');
        $filepath = sfConfig::get('sf_config_dir') . '/factories.yml';
        file_put_contents($filepath, preg_replace('/#token#/', $salt, file_get_contents($filepath)));

        $this->log(sprintf('>> Create user %s', $username));
        $this->runTask('phast:create-user', [
            'username' => $username,
            'password' => $password,
            'groups' => ['admin'],
        ]);

        $this->log('');
        $this->log('Installation is successfully completed');


    }
}
