<?php


class PhastGalleryRel extends BaseObject
{

    public function getMedia(){
        if($this->image_id) return $this->getImage();
        if($this->video_id) return $this->getVideo();
    }

    public function getType(){
        if($this->image_id) return 'image';
        if($this->video_id) return 'video';
    }

    public function getTitle(){
        if($media = $this->getMedia()) {
            if ($media instanceof Image) {
                return $media->getTitle();

            }else if($media instanceof Video){
                return $media->getTitle();
            }
        }
        return '';
    }

    public function getPreviewTag(){
        if($media = $this->getMedia()) {
            if ($media instanceof Image) {
                return $media->getTag(50, 50, true, false);

            }else if($media instanceof Video){
                return $media->getPreviewTag(50);
            }
        }
        return '';

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
