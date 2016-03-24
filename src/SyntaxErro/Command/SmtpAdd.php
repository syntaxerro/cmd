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

class SmtpAdd extends Command
{
    use AnswerStorage;

    protected function configure()
    {
        $this
            ->setName('smtp:add')
            ->setDescription('Add domain or user to postfix and dovecot database.')
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

        $output->writeln("MODE: Adding new $type.");
        switch($type) {
            /* Adding new domain. */
            case 'domain':
                $newDomainQuestion = new Question("New domain name: ");
                $newDomain = $questioner->ask($input, $output, $newDomainQuestion);
                try {
                    $pdo->query(sprintf($queries['new_domain'], $newDomain));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Added $newDomain to domains table.</info>");
                break;

            /* Adding new user. */
            case 'user':
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
                $selectedDomainName = false;
                foreach($domains as $domain) {
                    if($domain['id'] == $selectedDomain) {
                        $selectedDomainName = $domain['name'];
                        break;
                    }
                }

                /* Get new username. */
                $emailQuestion = new Question("Add new user (only username, without at and domain): ");
                $email = $questioner->ask($input, $output, $emailQuestion);
                if(preg_match('/@/', $email)) throw new \UnexpectedValueException(sprintf("Username '%s' is not valid. It cannot contains domain name.", $email));
                $email .= "@$selectedDomainName";

                $existEmail = $pdo->query(sprintf($queries['user_exist'], $email))->rowCount();
                if($existEmail) throw new \UnexpectedValueException(sprintf("Username '%s' already exist.", $email));

                /* Get new password. */
                $passwordQuestion = new Question("Password for new user $email: ");
                $passwordQuestion->setHidden(true);
                $password = $questioner->ask($input, $output, $passwordQuestion);
                $passwordQuestion = new Question("Return password for new user $email: ");
                $passwordQuestion->setHidden(true);
                $returnPassword = $questioner->ask($input, $output, $passwordQuestion);
                if($password != $returnPassword) throw new \UnexpectedValueException("Passwords are different!");

                try {
                    $pdo->query(sprintf($queries['new_user'], $selectedDomain, $email, $password));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Added new user $email.</info>");
                break;

            /* Adding new alias. */
            case 'alias':
                /* List and select destination user. */
                $users = $pdo->query($queries['all_users'])->fetchAll();
                $exists = [];
                foreach($users as $user) {
                    $output->writeln("[".$user['id']."] ".$user['email']);
                    $exists[] = $user['id'];
                }
                $selectedUserQuestion = new Question("Select user ID: ");
                $selectedUser = $questioner->ask($input, $output, $selectedUserQuestion);
                if(!in_array($selectedUser, $exists)) throw new \UnexpectedValueException(sprintf("User with ID '%s' not exist in your database.", $selectedUser));
                $selectedUserArray = [];
                foreach($users as $user) {
                    if($user['id'] == $selectedUser) {
                        $selectedUserArray = $user;
                        break;
                    }
                }

                /* Ask and save new alias for selected user. */
                $aliasQuestion = new Question("New alias for ".$selectedUserArray['email']." (only username without domain): ");
                $alias = $questioner->ask($input, $output, $aliasQuestion);

                $domain = explode("@", $selectedUserArray['email'])[1];
                $alias .= "@$domain";

                try {
                    $pdo->query(sprintf($queries['new_alias'], $selectedUserArray['domain_id'], $alias, $selectedUserArray['email']));
                } catch(\PDOException $e) {
                    $message = $e->getMessage().'   *** You can modify queries from SyntaxErro\Resources\queries.yml ***';
                    throw new \PDOException($message);
                }
                $output->writeln("<info>SUCCESS: Added new alias $alias for user {$selectedUserArray['email']}.</info>");
                break;
        }
    }
}
