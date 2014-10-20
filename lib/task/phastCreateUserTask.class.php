<?php

class phastCreateUserTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ));

        $this->addArgument('username', sfCommandArgument::REQUIRED, 'Username');
        $this->addArgument('password', sfCommandArgument::REQUIRED, 'Password');
        $this->addArgument('groups', sfCommandArgument::IS_ARRAY, 'List of groups');

        $this->namespace = 'phast';
        $this->name = 'create-user';
        $this->briefDescription = '';
        $this->detailedDescription = <<<EOF
The [mailing:schedule|INFO] task does things.
Call it with:

  [php symfony mailing:schedule|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
        sfContext::createInstance($this->configuration);

        $user = new User();
        $user->setName($arguments['username']);
        $user->save();

        $groups = [];
        foreach($arguments['groups'] as $group){
            if($group = UserGroupQuery::create()->findOneByName($group)){
                $groups[] = $group->getId();
            }
        }

        if($groups){
            $user->setGroup($groups);
        }

        $user->createSign($arguments['username'], $arguments['password']);

    }
}
