<?php


class PhastPage extends BaseObject
{

    const PARENT_PKS = 2;
    const PARENT_REVERSE = 4;

    protected $phastSettings = array(
        'positionMask' => array('parent_id')
    );

    public function getParent(){
        return $this->getPageRelatedByParentId();
    }

    public function getParents($mode = 1){

        if(!$this->parent_id) return [];

        if($mode & self::PARENT_PKS){

            $pks = explode('/', trim($this->path, '/'));
            ~$mode & self::PARENT_REVERSE && $pks = array_reverse($pks);

            return $pks;

        }else{

            PageQuery::create()->findById($pks = $this->getParents($mode | self::PARENT_PKS));

            $objects = [];
            foreach($pks as $pk){
                $objects[] = PagePeer::retrieveByPK($pk);
            }
            return $objects;

        }

    }

    public function getChildren($level = 0, $criteria = null){

        $query = new PageQuery;

        if($level instanceof Criteria){
            $criteria = $level;
            $level = 0;
        }

        if(null !== $criteria){
            $query->mergeWith($criteria);
        }

        if ($level < 1){
            return $query->filterByPath("{$this->getSelfPath()}%", ModelCriteria::LIKE)->find();

        } else if ($level > 1){
            $path = '^' . $this->getSelfPath() . '([0-9]+/){0,'.($level-1).'}$';
            return $query->filterByPath($path, ' REGEXP ')->find();

        } else {
            return $query->findByParentId($this->getId());
        }
    }

    public function getURL(){
        return $this->uri[0] == '^' ? mb_substr($this->uri[0], 1) : $this->uri;
    }

    public function fixPath(){
        $path = $this->parent_id ? $this->getParent()->getSelfPath() : '/';
        if(!$this->isNew()){
            $self = $this->getSelfPath();
            $size = mb_strlen($self);
            $connection = Propel::getConnection();
            $connection->exec("
				UPDATE `page`
				SET
					`path` = CONCAT('$path{$this->id}', SUBSTRING(`path`, $size)),
					`level` = LENGTH(`path`)-LENGTH(REPLACE(`path`, '/', ''))-1

				WHERE `path` LIKE '$self%'
			");
        }
        $this->setLevel(substr_count($path, '/') - 1);
        $this->setPath($path);
    }

    public function getSelfPath(){
        return $this->path . $this->id . '/';
    }

}
