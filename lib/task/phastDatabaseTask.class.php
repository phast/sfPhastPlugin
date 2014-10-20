<?php

class phastDatabaseTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ));

        $this->namespace = 'phast';
        $this->name = 'database';
        $this->briefDescription = '';
        $this->detailedDescription = <<<EOF
The [mailing:schedule|INFO] task does things.
Call it with:

  [php symfony mailing:schedule|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {

        $this->log('Configure database');

        $validator = new sfValidatorString();

        $adapter = $this->ask('adapter (= mysql):', 'QUESTION', 'mysql');
        $this->log('');
        $host = $this->ask('host (= localhost):', 'QUESTION', 'localhost');
        $this->log('');
        $dbname = $this->askAndValidate('db name:', $validator);
        $this->log('');
        $username = $this->askAndValidate('username:', $validator);
        $this->log('');
        $password = $this->askAndValidate('password:', $validator);

        $this->log('');
        $this->log('>> Write to config/databases.yml');
        $this->log(sprintf('   %s:host=%s;dbname=%s', $adapter, $host, $dbname));
        $this->log(sprintf('   username: %s', $username));
        $this->log(sprintf('   password: %s', password));

        file_put_contents(sfConfig::get('sf_config_dir') . '/databases.yml',
<<<"EOT"
dev:
  propel:
    param:
      classname:  DebugPDO
      debug:
        realmemoryusage: true
        details:
          time:       { enabled: true }
          slow:       { enabled: true, threshold: 0.1 }
          mem:        { enabled: true }
          mempeak:    { enabled: true }
          memdelta:   { enabled: true }

test:
  propel:
    param:
      classname:  DebugPDO

all:
  propel:
    class:        sfPropelDatabase
    param:
      classname:  PropelPDO
      dsn:        mysql:dbname=$dbname;host=$host
      username:   $username
      password:   $password
      encoding:   utf8
      persistent: true
      pooling:    true

EOT
        );

        $this->runTask('cc');

    }
}
