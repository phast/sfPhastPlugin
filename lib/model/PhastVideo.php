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


}
