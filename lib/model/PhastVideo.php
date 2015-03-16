<?php


class PhastVideo extends BaseObject
{
	public function getCode(){
		if($this->getUrl()){
			preg_match('/(?:\?v=([\w\d]+)|.be\/([\w\d]+))/i', $this->getUrl(), $match);
			$src = 'http://www.youtube.com/embed/' . ($match[1] ? $match[1] : $match[2]);
			return "<iframe width=\"{$this->getWidth()}\" height=\"{$this->getHeight()}\" src=\"{$src}\" frameborder=\"0\" allowfullscreen></iframe>";
		}
	}
}
