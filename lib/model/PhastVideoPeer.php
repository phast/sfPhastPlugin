<?php


class PhastVideoPeer
{

	public static function validateURL($url){
		return preg_match('#^(http://)?(www\.)?(youtu\.be|youtube\.com)/(watch\?v=[\w\d_-]+|[\w\d_-]+)$#i', $url);
	}

	public static function retrieveTitleFromURL($url){
		if(preg_match('#^(?:http://)?(?:www\.)?(?:youtu\.be|youtube\.com)/(watch\?v=[\w\d_-]+|[\w\d_-]+)$#i', $url, $match)){
			if($data = @simplexml_load_string(file_get_contents('http://gdata.youtube.com/feeds/api/videos/' . preg_replace('/watch\?v=/', '', $match[1])))){
				return isset($data->title) ? $data->title : '';
			}
		}

		return '';

	}

}
