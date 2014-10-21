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
        return '';
    }

    public function getImageHtml($object, $options){
        $output = '';
        if($object->getFullsize()) $output .= '<a target="_blank" href="'.$object->getURI().'" rel="prettyPhoto['.$this->getHolderId().']" class="widget-image-link" title="'.$object->getTitle().'">';
        $output .= '<img class="widget-image" src="'. $object->getWidgetUri() .'" style="'. $options['style'] .'">';
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
