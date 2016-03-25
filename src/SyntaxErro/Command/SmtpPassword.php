<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Model\CustomQueries;
use SyntaxErro\Tools\AnswerStorage;

class SmtpPassword extends Command
{
    use AnswerStorage;

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

        $output->writeln("<info>Login into mySQL: </info>");
        /* Login into mySQL. */
        $sqlHost = $this->ask($input, $output, "Host:", 'mysql_host');
        $sqlUsername = $this->ask($input, $output, "Username:", 'mysql_username');
        $sqlDatabase = $this->ask($input, $output, "Database name:", 'mysql_database');
        $passwordQuestion = new Question("Password: ");
        $passwordQuestion->setHidden(true);
        $sqlPassword = $questioner->ask($input, $output, $passwordQuestion);

        $pdo = new \PDO("mysql:host=$sqlHost;dbname=$sqlDatabase;charset=utf8", $sqlUsername, $sqlPassword);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $output->write("Connected.".PHP_EOL.PHP_EOL);

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
