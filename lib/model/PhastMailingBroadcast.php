<?php

use Cron\CronExpression;

class PhastMailingBroadcast extends BaseObject
{

    const STATUS_PREPARE = 0;
    const STATUS_READY = 1;
    const STATUS_SENDED = 2;

    public function getStartedPlan(){
        if($this->getStatus() == self::STATUS_PREPARE){
            return 'Рассылка не подготовлена';
        }

        if($this->getStatus() == self::STATUS_SENDED){
            return 'Отправлено ' . sfPhastUtils::date($this->getUpdatedAt()) . ' в ' . date('H:i');
        }

        if($schedule = MailingScheduleQuery::create()->findOneByComposer('BroadcastMailingComposer')){
            $cron = CronExpression::factory($schedule->getTimetable());
            $next = $cron->getNextRunDate(strtotime($this->getStartedAt()) > time() ? $this->getStartedAt() : null);
            return sfPhastUtils::date($next->getTimestamp()) . ' в ' . $next->format('H:i');

        }else{
            return 'Запуск невозможен — расписание не найдено';
        }
    }

    public function getSubscribers(){
        if($channels = MailingBroadcastRelQuery::create()->filterByMailingBroadcast($this)->select(['ChannelId'])->find()->toArray()){
            return MailingSubscriberQuery::create()
                ->joinMailingSubscriberRel()
                ->addJoinCondition('MailingSubscriberRel', 'MailingSubscriberRel.ChannelId IN ?', $channels, Criteria::IN)
                ->find();
        }else{
            return [];
        }
    }

}
