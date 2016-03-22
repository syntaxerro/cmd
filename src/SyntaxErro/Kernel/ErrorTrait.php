<?php

namespace SyntaxErro\Kernel;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

trait ErrorTrait
{
    /**
     * Display error on screen using OutputInterface.
     * Optional stop progress bar if submitted third argument.
     *
     * @param \Exception $e
     * @param OutputInterface $output
     * @param FormatterHelper $formatter
     * @param ProgressBar|null $progress
     */
    private function displayError(\Exception $e, OutputInterface $output, FormatterHelper $formatter, ProgressBar $progress = null)
    {
        if($progress !== null) {
            $progress->clear();
            $progress->setMessage('Error!');
            $progress->finish();
            $output->write(PHP_EOL);
        }

        $errors[] = get_class($e);
        $errors[] = null;
        $errors[] = $e->getMessage();
        while($prevException = $e->getPrevious()) {
            $errors[] = $prevException->getMessage();
            $e = $prevException;
        }

        $output->writeln($formatter->formatBlock($errors, 'error') );
    }
}
