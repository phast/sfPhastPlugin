<?php


class PhastVideo extends BaseObject
{
	public function getCode($width = null, $height = null){
		if($this->getUrl()){
            if(!$width && $height){
                $width = $height * 1.587301587301587;
            }
            if(!$height && $width){
                $height = $width * 0.63;
            }
            if(!$width){
                $width = $this->getWidth();
            }
            if(!$height){
                $height = $this->getHeight();
            }
			preg_match('/(?:\?v=([\w\d]+)|.be\/([\w\d]+))/i', $this->getUrl(), $match);
			$src = 'http://www.youtube.com/embed/' . ($match[1] ? $match[1] : $match[2]);
			return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$src}\" frameborder=\"0\" allowfullscreen></iframe>";
		}
	}

    public function getPreviewTag($width = null, $height = null, $type = 0){
        return '<img src="'.$this->getPreviewUrl($type).'" style="'.($width ? 'width: '.$width.'px;' : '').' '.($height ? 'width: '.$height.'px;' : '').'">';
    }

    public function getPreviewUrl($type = 0){
        preg_match('/(?:\?v=([\w\d]+)|.be\/([\w\d]+))/i', $this->getUrl(), $match);
        return 'http://img.youtube.com/vi/'.($match[1] ? $match[1] : $match[2]).'/'.$type.'.jpg';
    }

    public function getImage($type = 0){
        if(preg_match('/(?:\?v=([\w\d]+)|.be\/([\w\d]+))/i', $this->getUrl(), $match)){
            $filename = $match[1] . '-' . $type . '.jpg';
            $webpath = '/generated/video/';
            $dirpath = sfConfig::get('sf_web_dir') . $webpath;
            $filepath = $dirpath . $filename;

            if(!file_exists($filepath)){
                if(!is_file($dirpath)){
                    @mkdir($dirpath, 0775, true);
                }

                if($content = file_get_contents('http://img.youtube.com/vi/'.($match[1] ? $match[1] : $match[2]).'/'.$type.'.jpg')){
                    file_put_contents($filepath, $content);
                }
            }

            $image = new Image();
            $image->setVirtualColumn('isTemp', true);
            $image->setPath(rtrim($webpath, '/'));
            $image->setFilename($filename);

            return $image;
        }
    }

    public function getImageUri($width = null, $height = null, $scale = null, $inflate = null, $filters = null){
        return ($image = $this->getImage()) ? $image->getUri($width, $height, $scale, $inflate, $filters) : '';
    }

    public function getImageTag($width = null, $height = null, $scale = null, $inflate = null, $filters = null){
        return ($image = $this->getImage()) ? $image->getTag($width, $height, $scale, $inflate, $filters) : '';
    }


}
