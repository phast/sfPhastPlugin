<?php

class PhastWidget extends BaseObject
{

    protected $phastSettings = array(
        'positionMask' => array('holder_id')
    );

    public function getObject(){
        if($this->getImageId()) return $this->getImage();
        if($this->getGalleryId()) return $this->getGallery();
        if($this->getVideoId()) return $this->getVideo();
        if($this->getFileId()) return $this->getFile();
        return null;
    }

    public function getType(){
        if($this->getImageId()) return 'image';
        if($this->getGalleryId()) return 'gallery';
        if($this->getVideoId()) return 'video';
        if($this->getFileId()) return 'file';
        return 'undefined';
    }

    public function getGalleryHtml($object, $options){
        $output = '';
        $output .= '<div class="widget-gallery" style="'. $options['style'] .'">';
        $criteria = GalleryRelQuery::create()->filterByVisible(true)->orderByPosition();
        foreach($object->getGalleryRels($criteria) as $rel){
            $output .= '<div class="widget-gallery-image">';
            $output .= '<a href="'.$rel->getImageUri().'" rel="image[gallery-'.$this->getId().']" class="widget-gallery-image-link">';
            $output .= '<img src="'. $rel->getImageUri(180, 130) .'" alt="'. $rel->getTitle() .'">';
            $output .= '</a>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function getImageHtml($object, $options){
        $output = '';
        if($object->getFullsize()) $output .= '<a href="'.$object->getURI().'" rel="image" class="widget-image-link">';
        $output .= '<img class="widget-image" src="'. $object->getWidgetUri() .'" style="'. $options['style'] .'" alt="'.$object->getTitle().'">';
        if($object->getFullsize()) $output .= '</a>';
        return $output;
    }

    public function getFileHtml($object, $options){
        $output = '
					<div class="widget-file" style="'. $options['style'] .'">
					    <a class="widget-file widget-file-'.$object->getExtensionStyle().'" href="'.$object->getFile().'">'.
            $object->getTitle().
            '</a> ('.$object->getExtension().', '.$object->getSizeCaption().')
                    </div>';

        return $output;
    }

    public function getVideoHtml($object, $options){
        return '<div class="widget-video" style="'. $options['style'] .'">'.$object->getCode().'</div>';
    }


    public function getHtml($options = []){

        $object = $this->getObject();
        $type = $this->getType();

        switch($type){

            case 'gallery':
                return $this->getGalleryHtml($object, $options);

            case 'image':
                return $this->getImageHtml($object, $options);

            case 'file':
                return $this->getFileHtml($object, $options);

            case 'video':
                return $this->getVideoHtml($object, $options);

        }

        return '';

    }

}
