<?php

namespace SyntaxErro\Tools;


class ProxyMap
{
    const FILE_PATH = __DIR__.'/../../../cache/proxy.map';

    private $map = [];

    public function __construct()
    {
        if(!file_exists(static::FILE_PATH)) file_put_contents(static::FILE_PATH, '');
        foreach(explode(PHP_EOL, file_get_contents(static::FILE_PATH)) as $line) {
            $divided = explode(':', $line);
            if(count($divided) < 2) continue;
            $this->map[trim($divided[0])] = trim($divided[1]);
        }
    }

    public function save()
    {
        $content = '';
        foreach($this->map as $domain => $ip) {
            $content .= $domain.':'.$ip.PHP_EOL;
        }
        file_put_contents(static::FILE_PATH, $content);
        return $this;
    }

    public function isMapped($domain)
    {
        return isset($this->map[$domain]);
    }

    public function setMap($domain, $ip)
    {
        $this->map[$domain] = $ip;
        return $this->save();
    }

    public function getIpFor($domain)
    {
        if(!$this->isMapped($domain)) return null;
        return $this->map[$domain];
    }
}