<?php


class PhastGalleryRel extends BaseObject
{

    public function getPreviewTag(){
        return $this->getImage()->getTag(50, 50, true, false);
    }

    public function assignCover($coverCheck = false){
        if($coverCheck and !!GalleryRelQuery::create()->filterByGalleryId($this->getGalleryId())->filterByCover(true)->findOne()){
            return;
        }

        GalleryRelQuery::create()->filterByGalleryId($this->getGalleryId())->filterByCover(true)->update(['Cover' => 0]);
        $this->setCover(true);
        $this->save();
    }

}
