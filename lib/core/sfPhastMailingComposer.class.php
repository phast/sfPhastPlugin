<?php

class sfPhastMailingComposer{

    protected
        $task,
        $schedule,
        $subscribers = []
    ;

    public function __construct(MailingTask $task){
        $this->task = $task;
        $this->schedule = $task->getMailingSchedule();
        $this->subscribers = $this->schedule->getSubscribers();
    }

    public function execute(){
        return false;
    }

}