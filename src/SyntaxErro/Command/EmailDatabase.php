<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Tools\AnswerStorage;


class EmailDatabase extends Command
{
    use AnswerStorage;

    protected function configure()
    {
        $this
            ->setName('smtp:database')
            ->setDescription('Create default database schema for postfix, dovecot and spamassassin.');
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
        $questioner = $this->getHelper('question');

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

        $output->writeln("MODE: Creating database schema.");


        $pdo->query("
          CREATE TABLE IF NOT EXISTS `virtual_domains` (
            `id`  INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $output->writeln("<info>Created virtual_domains table.</info>");

        $pdo->query("
          CREATE TABLE IF NOT EXISTS `virtual_users` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `domain_id` INT NOT NULL,
            `password` VARCHAR(106) NOT NULL,
            `email` VARCHAR(120) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            FOREIGN KEY (domain_id) REFERENCES virtual_domains(id) ON DELETE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $output->writeln("<info>Created virtual_users table.</info>");

        $pdo->query("
            CREATE TABLE IF NOT EXISTS `virtual_aliases` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `domain_id` INT NOT NULL,
                `source` varchar(100) NOT NULL,
                `destination` varchar(100) NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (domain_id) REFERENCES virtual_domains(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $output->writeln("<info>Created virtual_aliases table.</info>");

        $spamQuestion = new ConfirmationQuestion("Create spamassassin user preferences table [Y/n]?", true, '/^y|Y|t|T/i');
        $spam = $questioner->ask($input, $output, $spamQuestion);
        if($spam) {
            $pdo->query("
                CREATE TABLE IF NOT EXISTS `userpref` (
                    `username` varchar(100) NOT NULL,
                    `preference` varchar(30) NOT NULL,
                    `value` text NOT NULL,
                    `prefid` int(11) NOT NULL AUTO_INCREMENT,
                     PRIMARY KEY (`prefid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $output->writeln("<info>Created userpref table.</info>");
        }
    }
}
