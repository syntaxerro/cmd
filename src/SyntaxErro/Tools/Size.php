<?php

namespace SyntaxErro\Tools;

class Size
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * Size constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        is_readable($path) ? $this->readFiles($path) : null;
    }

    /**
     * @param $path
     * @return Size
     */
    private function readFiles($path)
    {
        $this->size = 0;
        foreach(new \DirectoryIterator($path) as $item) {
            if(!$item->isDot() && $item->isFile()) $this->size += $item->getSize();
            if(!$item->isDot() && $item->isDir()) $this->readFiles($item->getPathname());
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getHumanReadableSize()
    {
        switch(true) {
            case $this->size < 1000: return $this->size." B";
            case $this->size >= 1000 && $this->size < 1000000: return round($this->size/1000, 2)." kB";
            case $this->size >= 1000000 && $this->size < 1000000000: return round($this->size/1000000, 2)." MB";
            case $this->size >= 1000000000: return round($this->size/1000000000, 2)." GB";
        }
        return $this->size." B";
    }
}