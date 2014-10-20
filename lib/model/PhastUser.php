<?php

class PhastUser extends BaseObject{

    public function createSign($key, $password){
        $user = sfContext::getInstance()->getUser();

        $sign = new UserSign();
        $sign->setUser($this);
        $sign->setSalt($user->generateHash());
        $sign->setKey($key);
        $sign->setPassword($user->generatePassword($password, $sign->getSalt()));
        $sign->save();
        return $sign;
    }

    public function getCredentials(){
        return UserCredentialQuery::create()
                ->useUserCredentialRelQuery()
                ->useUserGroupQuery()
                ->useUserGroupRelQuery()
                ->filterByUserId($this->id)
                ->endUse()
                ->endUse()
                ->endUse()
                ->select('Name')
                ->find()
                ->toArray();
    }

    public function setGroup($groups, $consist = null){
        if($this->isNew())
            throw new sfPhastException('Save item before use setGroup()');

        if($groups instanceof UserGroup)
            $groups = array($groups->getId());

        if(!is_array($groups))
            $groups = array($groups);

        if($consist instanceof UserGroupSection) {

            $sectionGroups = UserGroupQuery::create()->filterByUserGroupSection($consist)->select(['Id'])->find()->toArray();

            $groups_ex = array();
            foreach($this->getUserGroupRels(UserGroupRelQuery::create()->filterByGroupId($sectionGroups)) as $rel){
                if(in_array($rel->getGroupId(), $groups)){
                    $groups_ex[] = $rel->getGroupId();
                }else{
                    $rel->delete();
                }
            }

            foreach(array_diff($groups, $groups_ex) as $group_id){
                $rel = new UserGroupRel();
                $rel->setUserId($this->getId());
                $rel->setGroupId($group_id);
                $rel->save();
            }

        }else if($consist !== null){

            foreach($groups as $group_id){
                $rel = UserGroupRelQuery::create()->filterByUserId($this->getId())->filterByGroupId($group_id)->findOne();

                if($consist){
                    if(!$rel){
                        $rel = new UserGroupRel();
                        $rel->setUserId($this->getId());
                        $rel->setGroupId($group_id);
                        $rel->save();
                    }
                }else{
                    if($rel)
                        $rel->delete();
                }
            }

        }else{

            $groups_ex = array();
            foreach($this->getUserGroupRels() as $rel){
                if(in_array($rel->getGroupId(), $groups)){
                    $groups_ex[] = $rel->getGroupId();
                }else{
                    $rel->delete();
                }
            }

            foreach(array_diff($groups, $groups_ex) as $group_id){
                $rel = new UserGroupRel();
                $rel->setUserId($this->getId());
                $rel->setGroupId($group_id);
                $rel->save();
            }

        }

    }

}
