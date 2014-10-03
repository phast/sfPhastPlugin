<?php

class mailingScheduleTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ));

        $this->namespace = 'mailing';
        $this->name = 'schedule';
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

        foreach(MailingScheduleQuery::create()->find() as $schedule){
            if(!$schedule->isDue()) continue;

            $next = $schedule->getNextRunDate(false);

            if(MailingTaskQuery::create()->findOneByStartedAt($next)) continue;

            $task = new MailingTask();
            $task->setMailingSchedule($schedule);
            $task->setStatus(MailingTask::STATUS_WAITING);
            $task->setStartedAt($next);
            $task->save();

            $this->log('+ Task #' .  $task->getId() . ' ' . $schedule->getComposer());
        }

        $this->log('');


        foreach(MailingTaskQuery::create()->findByStatus(MailingTask::STATUS_WAITING) as $task){
            $result = $task->execute() ? 'success' : 'fail';
            $this->log('>> Executing task #' . $task->getId() . ' ' . $result);
        }

    }
}
