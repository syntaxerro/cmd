<?php

namespace SyntaxErro\Model;

use Symfony\Component\Yaml\Parser;
use SyntaxErro\Exception\FileNotReadableException;

class CustomQueries
{
    /**
     * Load queries from SyntaxErro\Resources\config\queries.yml
     *
     * @param string $path
     * @return array|null
     * @throws FileNotReadableException
     */
    public static function load($path)
    {
        if(!is_readable($path)) throw new FileNotReadableException(sprintf("Cannot read '%s' file for read queries.", $path));
        $parser = new Parser();
        return $parser->parse(file_get_contents($path));
    }
}
