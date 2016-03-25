<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Parser;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Tools\AnswerStorage;

class SmtpRemove extends Command
{
    use AnswerStorage;

    protected function configure()
    {
        $this
            ->setName('smtp:rm')
            ->setDescription('Remove domain, user or alias to postfix and dovecot database.')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Domain, user or alias.'
            )
        ;
    }

    /**
     * Load queries from SyntaxErro\Resources\config\queries.yml
     *
     * @return mixed
     * @throws FileNotReadableException
     */
    private function loadCustomQueries()
    {
        $queriesFile = __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."queries.yml";
        if(!is_readable($queriesFile)) throw new FileNotReadableException(sprintf("Cannot read '%s' file for read queries.", $queriesFile));
        $parser = new Parser();
        return $parser->parse(file_get_contents($queriesFile));
    }

    /**
     * Execute smtp:rm command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     *
     * @throws FileNotReadableException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queries = $this->loadCustomQueries();
        $questioner = $this->getHelper('question');
        /* Get and validate type of adding data. */
        $type = strtolower($input->getArgument('type'));
        $allowedTypes = ["domain", "user", "alias"];
        if(!in_array($type, $allowedTypes)) throw new \UnexpectedValueException(sprintf("Type '%s' is not allowed. Choice domain, user or alias.", $type));

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

        $output->writeln("MODE: Removing $type.");
        switch($type) {
            /* Removing domain. */
            case 'domain':
                /* List domains and select. */
                $domains = $pdo->query($queries['all_domains'])->fetchAll();
                $exists = [];
                foreach($domains as $domain) {
                    $output->writeln("[".$domain['id']."] ".$domain['name']);
                    $exists[] = $domain['id'];
                }
                $selectedDomainQuestion = new Question("Select domain by ID: ");
                $selectedDomain = $questioner->ask($input, $output, $selectedDomainQuestion);
                if(!in_array($selectedDomain, $exists)) throw new \UnexpectedValueException(sprintf("Domain with ID '%s' not exist in your database.", $selectedDomain));
                $selectedDomainName = '';
                foreach($domains as $domain) {
                    if($domain['id'] == $selectedDomain) {
                        $selectedDomainName = $domain['name'];
                        break;
                    }
                }

                try {
                    $pdo->query(sprintf($queries['remove_domain'], $selectedDomain));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Removed domain $selectedDomainName.</info>");
                break;

            /* Removing user. */
            case 'user':
                /* List and select user. */
                $users = $pdo->query($queries['all_users'])->fetchAll();
                $exists = [];
                foreach($users as $user) {
                    $output->writeln("[".$user['id']."] ".$user['email']);
                    $exists[] = $user['id'];
                }
                $selectedUserQuestion = new Question("Select user ID: ");
                $selectedUser = $questioner->ask($input, $output, $selectedUserQuestion);
                if(!in_array($selectedUser, $exists)) throw new \UnexpectedValueException(sprintf("User with ID '%s' not exist in your database.", $selectedUser));
                $selectedUserEmail = '';
                foreach($users as $user) {
                    if($user['id'] == $selectedUser) {
                        $selectedUserEmail = $user['email'];
                        break;
                    }
                }

                try {
                    $pdo->query(sprintf($queries['remove_user'], $selectedUser));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Removed user $selectedUserEmail.</info>");
                break;

            /* Removing alias. */
            case 'alias':
                /* List and select aliases. */
                $aliases = $pdo->query($queries['all_aliases'])->fetchAll();
                $exists = [];
                foreach($aliases as $alias) {
                    $output->writeln("[".$alias['id']."] ".$alias['source']." → ".$alias['destination']);
                    $exists[] = $alias['id'];
                }
                $selectedAliasQuestion = new Question("Select alias ID: ");
                $selectedAlias = $questioner->ask($input, $output, $selectedAliasQuestion);
                if(!in_array($selectedAlias, $exists)) throw new \UnexpectedValueException(sprintf("Alias with ID '%s' not exist in your database.", $selectedAlias));
                $selectedAliasName = '';
                foreach($aliases as $alias) {
                    if($alias['id'] == $selectedAlias) {
                        $selectedAliasName = $alias['source']." → ".$alias['destination'];
                        break;
                    }
                }

                try {
                    $pdo->query(sprintf($queries['remove_alias'], $selectedAlias));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Removed alias $selectedAliasName.</info>");
                break;
        }
    }
}