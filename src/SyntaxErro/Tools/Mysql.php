<?php

namespace SyntaxErro\Tools;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait Mysql
{
    use AnswerStorage;

    /**
     * Login into mySQL server. Return PDO instance for executing queries.
     *
     * @param QuestionHelper $questioner
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return \PDO
     */
    public function mysqlLogin(QuestionHelper $questioner, InputInterface $in, OutputInterface $out)
    {
        $out->writeln("<info>Login into mySQL: </info>");
        /* Login into mySQL. */
        $sqlHost = $this->ask($in, $out, "Host:", 'mysql_host');
        $sqlUsername = $this->ask($in, $out, "Username:", 'mysql_username');
        $sqlDatabase = $this->ask($in, $out, "Database name:", 'mysql_database');
        $passwordQuestion = new Question("Password: ");
        $passwordQuestion->setHidden(true);
        $sqlPassword = $questioner->ask($in, $out, $passwordQuestion);

        $pdo = new \PDO("mysql:host=$sqlHost;dbname=$sqlDatabase;charset=utf8", $sqlUsername, $sqlPassword);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $out->write("Connected.".PHP_EOL.PHP_EOL);
        return $pdo;
    }
}
