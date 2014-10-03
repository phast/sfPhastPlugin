<?php

class mailingSendTask extends sfBaseTask
{
    protected function configure()
    {

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ));

        $this->namespace = 'mailing';
        $this->name = 'send';
        $this->briefDescription = '';
        $this->detailedDescription = <<<EOF
The [mailing:send|INFO] task does things.
Call it with:

  [php symfony mailing:send|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
        sfContext::createInstance($this->configuration);


        $pks = [];
        $messages = [];

        $success = 0;
        $error = 0;

        foreach(MailingMessageQuery::create()
            ->filterByStatus(MailingMessage::STATUS_WAIT)
            ->orderByPriority(Criteria::DESC)
            ->limit(sfConfig::get('app_mailing_queue', 50))
            ->find() as $message){
            $messages[] = $message;
            $pks[] = $message->getId();
        }

        $total = count($pks);

        if($total){
            $this->log('Sending messages ('. $total .')');
            $this->log('');

            MailingMessageQuery::create()->filterById($pks)->update(['Status' => MailingMessage::STATUS_STARTED]);


            foreach($messages as $message){
                if($result = $message->send()){
                    $success++;
                }else{
                    $error++;
                }

                $this->log('>> to [' . $message->getTo() . '] ' . ($result ? 'success' : 'error'));
            }

            $this->log('');
            $this->log("Success:{$success}, Error: {$error}");

        }else{
            $this->log('No messages');
        }

    }
}
