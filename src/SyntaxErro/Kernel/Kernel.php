<?php

namespace SyntaxErro\Kernel;

use SyntaxErro\Exception\FileNotFoundException;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Exception\InvalidCommandException;
use SyntaxErro\Exception\InvalidConfigException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
/**
 * Class Kernel
 * Contain Application from Symfony components. Register commands from SyntaxErro\Command
 * @package SyntaxErro\Kernel
 */
class Kernel
{
    /**
     * @var Application
     */
    private $app;

    /**
     * Kernel constructor.
     */
    public function __construct()
    {
        $this->app = new Application("Syntaxerro CMD Tools", "0.0.4 alpha");
    }

    /**
     * Load commands from SyntaxErro\Command namespace.
     *
     * @return Kernel
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws InvalidCommandException
     * @throws InvalidConfigException
     */
    public function loadCommands()
    {
        foreach(new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Command") as $command) {
            if(!$command->isFile() || $command->isDot()) continue;
            $command = 'SyntaxErro\Command\\'.str_replace('.php', '', $command->getFilename());
            $this->app->add($this->validateCommandClass($command));
        }
        return $this;
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
                sprintf("Class '%s' is not loaded.", $commandClassName)
            );
        }

        if(!is_subclass_of($commandClassName, '\Symfony\Component\Console\Command\Command')) {
            throw new InvalidCommandException(
                sprintf('Class \'%s\' registered as command must be a children of \Symfony\Component\Console\Command\Command.', $commandClassName)
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
