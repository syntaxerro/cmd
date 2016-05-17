<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Model\CustomQueries;
use SyntaxErro\Tools\Mysql;

class SmtpPassword extends Command
{
    use Mysql;

    protected function configure()
    {
        $this
            ->setName('smtp:pass')
            ->setDescription('Change user password.')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Address email of user.'
            )
        ;
    }

    /**
     * Execute smtp:add command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     *
     * @throws FileNotReadableException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queries = CustomQueries::load(
            __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."queries.yml"
        );
        $questioner = $this->getHelper('question');
        /* Get and validate type of adding data. */
        $email = $input->getArgument('email');

        $pdo = $this->mysqlLogin($questioner, $input, $output);

        $output->writeln("MODE: Change password.");

        $exist = $pdo->query(sprintf($queries['user_exist'], $email))->rowCount();
        if(!$exist) throw new \UnexpectedValueException(sprintf("User with email '%s' not exist.", $email));

        $userPasswordQuestion = new Question("New password for $email: ");
        $userPasswordQuestion->setHidden(true);
        $returnUserPasswordQuestion = new Question("Return new password for $email: ");
        $returnUserPasswordQuestion->setHidden(true);
        $userPassword = $questioner->ask($input, $output, $userPasswordQuestion);
        $returnPassword = $questioner->ask($input, $output, $returnUserPasswordQuestion);

        if($returnPassword != $userPassword) throw new \UnexpectedValueException("Passwords are different!");

        $pdo->query(sprintf($queries['update_password'], $userPassword, $email));
    }
}
