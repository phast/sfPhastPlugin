<?php


class PhastGallery extends BaseObject
{

    public function getRels(){
        return GalleryRelQuery::create()->filterByGallery($this)->orderByPosition()->find();
    }

}
