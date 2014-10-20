<?php

use Cron\CronExpression;

class PhastMailingSchedule extends BaseObject
{

    public function getNextRunDate($format = true){
        $cron = CronExpression::factory($this->timetable);
        $next = $cron->getNextRunDate();

        if($format){
            return sfPhastUtils::date($next->getTimestamp()) . ', ' . $next->format('H:i');
        }

        return $next;
    }

    public function isDue(){
        $cron = CronExpression::factory($this->timetable);
        return $cron->isDue();
    }

    public function getSubscribers(){
        if($channels = MailingScheduleRelQuery::create()->filterByMailingSchedule($this)->select(['ChannelId'])->find()->toArray()){
            return MailingSubscriberQuery::create()
                ->joinMailingSubscriberRel()
                ->addJoinCondition('MailingSubscriberRel', 'MailingSubscriberRel.ChannelId IN ?', $channels, Criteria::IN)
                ->find();
        }else{
            return [];
        }

    }

}
