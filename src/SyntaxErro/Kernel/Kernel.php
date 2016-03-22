<?php

namespace SyntaxErro\Kernel;

use SyntaxErro\Exception\FileNotFoundException;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Exception\InvalidCommandException;
use SyntaxErro\Exception\InvalidConfigException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Parser;
/**
 * Class Kernel
 * Contain Application from Symfony components and configPath constant with path to config file.
 * Config file should be in $ProjectRoot/src/SyntaxErro/Resources/config/commands.yml
 * @package SyntaxErro\Kernel
 */
class Kernel
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @const string
     */
    const configPath = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."commands.yml";

    /**
     * Kernel constructor.
     */
    public function __construct()
    {
        $this->app = new Application("Syntaxerro CMD Tools", "0.0.3 alpha");
    }

    /**
     * Load commands from $ProjectRoot/src/SyntaxErro/Resources/config/commands.yml
     *
     * @return Kernel
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws InvalidCommandException
     * @throws InvalidConfigException
     */
    public function loadCommands()
    {
        foreach($this->validateCommandsFile() as $command) {
            $this->app->add($this->validateCommandClass($command));
        }
        return $this;
    }

    /**
     * Throws some exceptions if not valid $ProjectRoot/src/SyntaxErro/Resources/config/commands.yml
     * or not exist, or is not readable.
     * Parse yaml file and return parsed array.
     *
     * @return array
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws InvalidConfigException
     */
    private function validateCommandsFile()
    {
        if(!file_exists(static::configPath)) {
            throw new FileNotFoundException(sprintf("Not found file '%s'", static::configPath));
        }

        if(!is_readable(static::configPath)) {
            throw new FileNotReadableException(sprintf("Cannot read '%s' file. Check permissions.", static::configPath));
        }

        $parser = new Parser();
        $commands = $parser->parse(file_get_contents(static::configPath));
        if( $commands === null || !is_array($commands) || !(count($commands)) ) {
            throw new InvalidConfigException(sprintf("Not found commands in '%s' config file.", static::configPath));
        }
        return $commands;
    }

    /**
     * Validate registered command class name and create new instance.
     *
     * @param $commandClassName
     * @return Command
     * @throws InvalidCommandException
     */
    private function validateCommandClass($commandClassName)
    {
        if(!class_exists($commandClassName)) {
            throw new InvalidCommandException(
                sprintf("Not found '%s' class registered as command in '%s' file.", $commandClassName, static::configPath)
            );
        }

        if(!is_subclass_of($commandClassName, '\Symfony\Component\Console\Command\Command')) {
            throw new InvalidCommandException(
                sprintf('Class \'%s\' registered as command in \'%s\' file is not children of \Symfony\Component\Console\Command\Command.', $commandClassName, static::configPath)
            );
        }

        return new $commandClassName;
    }

    /**
     * Start application.
     *
     * @return int
     * @throws \Exception
     */
    public function run()
    {
        return $this->app->run();
    }
}
