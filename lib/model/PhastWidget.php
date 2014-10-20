<?php

class PhastHolder extends BaseObject 
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

    public function getGalleryHtml($options){
        return '';
    }

    public function getImageHtml($options){
        if($object->getFullsize()) $output .= '<a target="_blank" href="'.$object->getURI().'" rel="prettyPhoto['.$this->getHolderId().']" class="widget-image-link" title="'.$object->getTitle().'">';
        $output .= '<img class="widget-image" src="'. $object->getWidgetUri() .'" style="'. $options['style'] .'">';
        if($object->getFullsize()) $output .= '</a>';
        return $output;
    }

    public function getFileHtml($options){
        $output .= '
					<div class="widget-file" style="'. $options['style'] .'">
					    <a class="widget-file widget-file-'.$object->getExtensionStyle().'" href="'.$object->getFile().'">'.
            $object->getTitle().
            '</a> ('.$object->getExtension().', '.$object->getSizeCaption().')
                    </div>';

        return $output;
    }

    public function getVideoHtml(){
        return '<div class="widget-video" style="'. $options['style'] .'">'.$object->getCode().'</div>';
    }


    public function getHtml($options = []){

        $object = $this->getObject();
        $type = $this->getType();
        $output = '';

        switch($type){

            case 'gallery':
                return $this->getGalleryHtml($options);

            case 'image':
                return $this->getImageHtml();

            case 'file':
                return $this->getFileHtml();

            case 'video':
                return $this->getVideoHtml();

        }

        return '';

    }

}
