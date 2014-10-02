<?php

class PhastImage extends BaseObject
{

    protected $phastSettings = array(
        'positionMask' => array('gallery_id')
    );

    public function getSource(){
        return $this->filename ? sfConfig::get('sf_web_dir') . $this->path . '/' . $this->filename : '';
    }

    public function getURI($width = null, $height = null, $scale = null, $inflate = false, $crop = false){
        if(null === $width && null === $height) return $this->path . '/' . $this->filename;
        $webpath = '/generated' . $this->path . '/';
        if($scale === null){
            $filename = "{$width}-{$height}-{$this->getFilename()}";
        }else{
            $filename = "{$width}-{$height}-{$scale}-{$inflate}-{$this->getFilename()}";
        }

        $dirpath = sfConfig::get('sf_web_dir') . $webpath;
        $filepath = $dirpath . $filename;

        if(!is_file($filepath) || $crop){
            if(!is_file($this->getSource())) return '';
            if(!is_file($dirpath)){
                @mkdir($dirpath, 0775, true);
            }
            if($crop){
                $transform = new sfImageCropGD($crop['x'], $crop['y'], $crop['w'], $crop['h']);
                $image = new sfImage($this->getSource());
                $image = $transform->execute($image);
                $image->resize($width, $height);
                $image->setQuality(100);
                $image->saveAs($filepath);

            }else if($scale === null){
                $transform = new sfImageThumbnailGeneric($width, $height, 'center');
                $image = new sfImage($this->getSource());
                $image = $transform->execute($image);
                $image->setQuality(100);
                $image->saveAs($filepath);
            }else{
                $transform = new sfImageResizeGeneric($width, $height, $inflate, $scale);
                $image = new sfImage($this->getSource());
                $image = $transform->execute($image);
                $image->setQuality(100);
                $image->saveAs($filepath);
            }
            if(!$this->hasVirtualColumn('isTemp')) $this->setUpdatedAt(time())->save();
        }

        return $webpath . $filename . '?_=' . base_convert($this->updated_at, 16, 36);
    }

    public function getTag($width = null, $height = null, $scale = null, $inflate = false){
        $src = $this->getURI($width, $height, $scale, $inflate);
        return '<img src="'.$src.'" alt="'.$this->getTitle().'">';
    }

    public function getWidgetTag(){
        return $this->getTag(
            $this->width > 0 ? $this->width : null,
            $this->height > 0 ? $this->height : null,
            $this->width > 0 && $this->height > 0 ? null : true
        );
    }

    public function getWidgetUri(){
        return $this->getURI(
            $this->width > 0 ? $this->width : null,
            $this->height > 0 ? $this->height : null,
            $this->width > 0 && $this->height > 0 ? null : true
        );
    }

    public function getTitleCaption(){
        return $this->getTitle() ? $this->getTitle() : 'Без названия';
    }

    public static function createFromUpload($upload){
        $image = new static();
        $image->setPath($upload->getWebPath());
        $image->setFilename($upload->getFilename());
        $image->setMime($upload->getType());
        $image->save();
        return $image;
    }

    public function updateFromUpload($upload){
        $image = $this;
        $image->cleanSource();
        $image->setPath($upload->getWebPath());
        $image->setFilename($upload->getFilename());
        $image->setMime($upload->getType());
        $image->save();
        return $image;
    }

    public function delete(PropelPDO $con = null){
        $this->cleanSource();
        return parent::delete($con);
    }

    public function cleanSource(){
        if($source = $this->getSource() and file_exists($source)) unlink($source);
    }

}
