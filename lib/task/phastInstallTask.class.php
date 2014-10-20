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
        $validator = new sfValidatorString();

        $this->log('');
        $this->log('Phast CMS installation');

        $this->log('');
        $this->log('Configure project');
        $host = $this->askAndValidate('host:', $validator);
        $projectName = $this->askAndValidate('project name:', $validator);

        $this->log('');
        $this->log('Configure admin user');

        $username = $this->ask('username (= admin):', 'QUESTION', 'admin');
        $this->log('');
        $password = $this->askAndValidate('password:', $validator);
        $this->log('');
        $salt = md5(uniqid(mt_rand(), true));

        $this->runTask('phast:database');

        $this->runTask('propel:build-sql');
        $this->runTask('propel:insert-sql');
        $this->runTask('propel:build-model');
        $this->runTask('propel:data-load');


        $filepath = sfConfig::get('sf_config_dir') . '/app.yml';
        $appConfig = file_get_contents($filepath);
        $appConfig = preg_replace('/project\.dev/', $host, $appConfig);
        $appConfig = preg_replace('/Project Name/', $projectName, $appConfig);
        file_put_contents($filepath, $appConfig);

        $filepath = sfConfig::get('sf_config_dir') . '/factories.yml';
        file_put_contents($filepath, preg_replace('/#token#/', $salt, file_get_contents($filepath)));

        $this->runTask('phast:create-user', [
            'username' => $username,
            'password' => $password,
            'groups' => ['admin'],
        ]);

        $this->log('');
        $this->log('Installation is successfully completed');


    }
}
