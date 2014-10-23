<?php

class sfPhastSocket{

    protected static $instance;
    protected $host, $port;

    public function __construct($host = null, $port = null){
        if(null === $host){
            $host = sfConfig::get('app_socket_host', 'localhost');
        }
        if(null === $port){
            $port = sfConfig::get('app_socket_port', 8001);
        }
        $this->host = $host;
        $this->port = $port;
    }

    public static function getInstance(){
        return null !== static::$instance ? static::$instance : static::$instance = new static();
    }

    public static function send($event, $data, $user = null){
        return static::getInstance()->emit($event, $data, $user);
    }

    public function emit($event, $data, $user = null){
        $ch = curl_init();

        $fields = [
            'd' => json_encode($data),
            'e' => $event
        ];

        if($user and $user instanceof User){
            $fields['a'] = $user->getAccessKey();
        }

        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->host);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        curl_exec($ch);
        curl_close($ch);
    }

}