<?php

namespace SyntaxErro\Tools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Model\Parameters;

trait AnswerStorage
{
    /**
     * @var Parameters|null
     */
    private $parameters;

    /**
     * Auto-save parameters.yml on set every value.
     *
     * @var bool
     */
    private $autoSave = true;

    /**
     * @param bool $autoSave
     * @return AnswerStorage
     */
    public function init($autoSave = true)
    {
        $this->parameters = new Parameters();
        $this->autoSave = $autoSave;
        return $this;
    }

    /**
     * Ask question, return response and save for future.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $question
     * @param $name
     * @return null|string
     *
     * @throws \SyntaxErro\Exception\CannotWriteException
     * @throws \SyntaxErro\Exception\InvalidConfigException
     */
    public function ask(InputInterface $input, OutputInterface $output, $question, $name)
    {
        if(!($this->parameters instanceof Parameters)) $this->init();

        if($this instanceof Command) {
            $questioner = $this->getHelper('question');
            if($this->parameters->has($name)) {
                $question = new Question("$question <info>[{$this->parameters->get($name)}]</info>", $this->parameters->get($name));
            } else {
                $question = new Question($question." ");
                $saveQuestion = new ConfirmationQuestion("Save for future? Y/n", true, '/^y|Y|t|T/i');
            }
            $qResponse = trim($questioner->ask($input, $output, $question));
            if(!is_string($qResponse) || !strlen($qResponse)) $this->ask($input, $output, $question->getQuestion(), $name);
            if(isset($saveQuestion) && $questioner->ask($input, $output, $saveQuestion)) {
                $this->parameters->set($name, $qResponse, $this->autoSave);
            }
            return $qResponse;
        }
        return null;
    }

    /**
     * @return null|Parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
