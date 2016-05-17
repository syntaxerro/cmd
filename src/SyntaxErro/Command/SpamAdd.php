<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Model\CustomQueries;
use SyntaxErro\Tools\Mysql;

class SpamAdd extends Command
{
    use Mysql;

    protected function configure()
    {
        $this
            ->setName('spam:add')
            ->setDescription('Add email to blacklist or whitelist per user.')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Black or white.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $queries = CustomQueries::load(
            __DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Resources".DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."queries.yml"
        );
        $type = $input->getArgument('type');
        $allowTypes = ['black', 'white'];
        if(!in_array(strtolower($type), $allowTypes)) throw new \UnexpectedValueException(sprintf("Type '%s' is invalid for spam:add command.", $type));
        $questioner = $this->getHelper('question');

        $pdo = $this->mysqlLogin($questioner, $input, $output);

        $output->writeln("MODE: Adding to {$type}list.");

        /* List and select user. */
        $users = $pdo->query($queries['all_users'])->fetchAll();
        $exists = [];
        foreach($users as $user) {
            $output->writeln("[".$user['id']."] ".$user['email']);
            $exists[] = $user['id'];
        }
        $selectedUserQuestion = new Question("Select user ID: ");
        $selectedUser = $questioner->ask($input, $output, $selectedUserQuestion);
        if(!in_array($selectedUser, $exists) && $selectedUser != '@GLOBAL') throw new \UnexpectedValueException(sprintf("User with ID '%s' not exist in your database.", $selectedUser));
        $selectedUserEmail = '@GLOBAL';
        foreach($users as $user) {
            if($user['id'] == $selectedUser) {
                $selectedUserEmail = $user['email'];
                break;
            }
        }

        /* Ask blacklisted or whitelisted email.  */
        $senderQuestion = new Question("Email adding to {$type}list: ");
        $sender = $questioner->ask($input, $output, $senderQuestion);

        /* Create or update per user blacklist or whitelist. */
        $listExist = $pdo->query(sprintf($queries[$type."_exist"], $selectedUserEmail))->rowCount();
        if($listExist) {
            $pdo->query(sprintf($queries[$type."_update_add"], $sender, $selectedUserEmail));
        } else {
            $pdo->query(sprintf($queries[$type."_add"], $selectedUserEmail, $sender));
        }

        $output->writeln("<info>SUCCESS: Added $sender to {$type}list of $selectedUserEmail.</info>");
    }
}
