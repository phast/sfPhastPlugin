<?php

use \PhastUserGroupSectionPeer as Section;

class PhastUserGroupSection extends BaseObject{

    public function getAssignCaption(){
        $mode = $this->getAssignMode() ? Section::getAssignModeCaption($this->getAssignMode()) : '';
        $auto = $this->getAssignAuto() ? Section::getAssignAutoCaption($this->getAssignAuto()) : '';
        return !$mode ? '' : (!$auto ? $mode : "$mode ($auto)");
    }

    public function assign(User $user){
        if(Section::ASSIGN_MODE_NONE == $this->assign_mode) return;

        $groups = UserGroupQuery::create()
            ->filterByUserGroupSection($this)
            ->orderByPosition()
            ->find();

        $stack = [];

        foreach($groups as $group){
            /** @var UserGroup $group */
            if($group->evaluateCondition($user)){
                $stack[] = $group->getId();
                if(Section::ASSIGN_MODE_FIRST == $this->assign_mode) break;
            }else{
                if(Section::ASSIGN_MODE_CHAIN == $this->assign_mode) break;
            }
        }

        switch($this->assign_mode){
            case Section::ASSIGN_MODE_FIRST:
                if($stack) $stack = $stack[0];
                break;
            case Section::ASSIGN_MODE_CHAIN:
                if($stack) $stack = array_slice($stack, -1);
                break;
            case Section::ASSIGN_MODE_MULTI:
                break;
        }

        $user->setGroup($stack, $this);

    }

}
