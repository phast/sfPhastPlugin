<?php



class PhastFile extends BaseObject
{
    public function getSource(){
        return $this->filename ? sfConfig::get('sf_web_dir') . $this->path . '/' . $this->filename : '';
    }

    public function getExtensionCaption(){
        return strtoupper($this->getExtension());
    }

    public function getSizeCaption(){
        $size = $this->size;
        if($size > 1048576){
            return round($size / 1048576, 1) . ' Мб';
        }else if($size > 1024){
            return round($size / 1024, 1) . ' Кб';
        }else{
            return $size . ' б';
        }
    }

    public function getFile(){
        return $this->path . '/' . $this->filename;
    }

    public function getURL($absolute = false){
        return geturl('',$absolute).$this->getFile();
    }

    public static function createFromUpload($upload){
        $file = new static();
        $file->setPath($upload->getWebPath());
        $file->setFilename($upload->getFilename());
        $file->setSize(filesize($file->getSource()));
        $file->setExtension(pathinfo($file->getSource(), PATHINFO_EXTENSION));
        $file->save();
        return $file;
    }

    public function updateFromUpload($upload){
        $file = $this;
        $file->cleanSource();
        $file->setPath($upload->getWebPath());
        $file->setFilename($upload->getFilename());
        $file->setSize(filesize($file->getSource()));
        $file->setExtension(pathinfo($file->getSource(), PATHINFO_EXTENSION));
        $file->save();
        return $file;
    }

    public function getFileInfo(){
        if(!$this->filename) return '';
        return "<a href=\"{$this->getFile()}\" target=\"_blank\">{$this->getExtensionCaption()} {$this->getSizeCaption()}</a>";
    }

    public function cleanSource(){
        if($source = $this->getSource() and file_exists($source)) unlink($source);
    }
}
