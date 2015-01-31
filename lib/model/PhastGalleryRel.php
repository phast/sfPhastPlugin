<?php


class PhastGalleryRel extends BaseObject
{

    public function getPreviewTag(){
        return $this->getImage()->getTag(50, 50, true, false);
    }

}
