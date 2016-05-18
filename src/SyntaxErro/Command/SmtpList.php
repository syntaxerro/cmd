<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyntaxErro\Exception\FileNotReadableException;
use SyntaxErro\Model\CustomQueries;
use SyntaxErro\Tools\Mysql;
use SyntaxErro\Tools\Size;

class SmtpList extends Command
{
    use Mysql;

    protected function configure()
    {
        $this
            ->setName('smtp:list')
            ->setDescription('List domains or users from postfix and dovecot database.')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Domain or user.'
            )
        ;
    }

    /**
     * Execute smtp:list command.
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
        /* Get and validate type of data. */
        $type = strtolower($input->getArgument('type'));
        $allowedTypes = ["domain", "user"];
        if(!in_array($type, $allowedTypes)) throw new \UnexpectedValueException(sprintf("Type '%s' is not allowed. Choice domain or user.", $type));

        $pdo = $this->mysqlLogin($questioner, $input, $output);

        $output->writeln("MODE: List $type.");
        switch($type) {
            /* List domains. */
            case 'domain':
                $domains = $pdo->query($queries['all_domains'])->fetchAll();
                foreach($domains as $domain) {
                    $size = new Size("/var/mail/vhosts/{$domain['name']}");
                    $count = $pdo->query(sprintf($queries['count_users'], $domain['id']))->fetchAll()[0]['cnt'];
                    $line = "[<info>".$domain['name']."</info>] - $count accounts - ".$size->getHumanReadableSize();
                    $output->writeln($line);
                }
                break;

            /* List users. */
            case 'user':
                $users = $pdo->query($queries['all_users'])->fetchAll();
                foreach($users as $user) {
                    $email = explode('@', $user['email']);
                    $size = new Size("/var/mail/vhosts/{$email[1]}/{$email[0]}");
                    $line = "[".$email[1]."] - <info>{$user['email']}</info> - ".$size->getHumanReadableSize();
                    $output->writeln($line);
                }
                break;
        }
    }
}
