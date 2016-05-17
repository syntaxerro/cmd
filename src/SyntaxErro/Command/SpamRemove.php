<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Model\CustomQueries;
use SyntaxErro\Tools\Mysql;

class SpamRemove extends Command
{
    use Mysql;

    protected function configure()
    {
        $this
            ->setName('spam:rm')
            ->setDescription('Remove email from blacklist or whitelist per user.')
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
        if(!in_array(strtolower($type), $allowTypes)) throw new \UnexpectedValueException(sprintf("Type '%s' is invalid for spam:rm command.", $type));
        $questioner = $this->getHelper('question');

        $pdo = $this->mysqlLogin($questioner, $input, $output);

        $output->writeln("MODE: Removing from {$type}list.");

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

        /* Ask sender email.  */
        $senderQuestion = new Question("Email removing from {$type}list: ");
        $sender = $questioner->ask($input, $output, $senderQuestion);

        /* Remove sender from blacklist or whitelist per user. */
        $listExist = $pdo->query(sprintf($queries[$type."_exist"], $selectedUserEmail))->rowCount();
        if($listExist) {
            $pdo->query(sprintf($queries[$type."_update_remove"], $sender, $selectedUserEmail));
        } else {
            $type = ucfirst($type);
            $output->writeln("<error>{$type}list of $selectedUserEmail not contain $sender address.</error>");
            exit;
        }

        $output->writeln("<info>SUCCESS: Removed $sender from {$type}list of $selectedUserEmail.</info>");
    }
}
