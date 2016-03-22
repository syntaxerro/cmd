<?php

namespace SyntaxErro\Model;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use SyntaxErro\Exception\CannotWriteException;
use SyntaxErro\Exception\InvalidConfigException;

/**
 * Class Parameters
 * PHP representation of $ProjectRoot/src/Resources/config/parameters.yml
 *
 * @package SyntaxErro\Model
 */
class Parameters
{
    /**
     * Parsed parameters.yml file
     * @var mixed|array
     */
    private $content;

    /**
     * Path to parameters.yml file
     * @const yamlPath
     */
    const yamlPath = __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."parameters.yml";

    /**
     * Parameters constructor.
     * Open and parse parameters.yml file
     */
    public function __construct()
    {
        $parser = new Parser();
        $this->content = is_readable(static::yamlPath) ? $parser->parse(file_get_contents(static::yamlPath)) : null;
        if(!$this->content) $this->content = [];
    }

    /**
     * Return data from $ProjectRoot/src/Resources/config/parameters.yml by key.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidConfigException
     */
    public function get($key)
    {
        if(!array_key_exists($key, $this->content)) {
            throw new InvalidConfigException(sprintf("File '%s' not contain %s parameter.", static::yamlPath, $key));
        }

        return $this->content[$key];
    }

    /**
     * Check key exist.
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->content);
    }

    /**
     * Set key with value and save if $autoSave is true.
     *
     * @param $key
     * @param $value
     * @param bool $autoSave
     * @throws CannotWriteException
     * @return Parameters
     */
    public function set($key, $value, $autoSave = false)
    {
        $this->content[$key] = $value;
        if($autoSave) {
            $dumper = new Dumper();
            if(file_exists(static::yamlPath) && !is_writable(static::yamlPath)) throw new CannotWriteException(sprintf("Cannot write to '%s' file.", static::yamlPath));
            file_put_contents(static::yamlPath, $dumper->dump($this->content, 1));
        }
        return $this;
    }
}
