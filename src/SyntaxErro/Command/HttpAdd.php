<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use SyntaxErro\Exception\CannotWriteException;
use SyntaxErro\Exception\FileNotFoundException;
use SyntaxErro\Tools\AnswerStorage;
use SyntaxErro\Tools\Twig;

class HttpAdd extends Command
{
    use Twig;
    use AnswerStorage;

    /**
     * Configure h:h command.
     * @void
     */
    protected function configure()
    {
        $this
            ->setName('http:add')
            ->setDescription('Create new vhost from template.')
            ->addArgument(
                'domain',
                InputArgument::REQUIRED,
                'Domain or address of server.'
            )
            ->addOption(
                'ssl',
                's',
                InputOption::VALUE_NONE,
                'Create vhost with SSL protocol.'
            )
            ->addOption(
                'nginx',
                'x',
                InputOption::VALUE_NONE,
                'Use nginx config output format.'
            )
            ->addOption(
                'template',
                'tpl',
                InputOption::VALUE_REQUIRED,
                'Filename of custom template. Templates root is in SyntaxErro/Resources/tpl.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @throws CannotWriteException
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $questioner = $this->getHelper('question');
        /* Get parameters from user. */
        $domain = $input->getArgument('domain');
        $ssl = $input->getOption('ssl');
        $nginx = $input->getOption('nginx');
        $template = $input->getOption('template');
        $projects = $this->ask($input, $output, "What's path to directory with your all projects?", 'projects');
        $vhosts = $this->ask($input, $output, "What's path to directory with your all virtual hosts?", 'vhosts');
        $web = $this->ask($input, $output, "What's your web directory? Type '0' for DocumentRoot.", 'web_directory');
        $email = $this->ask($input, $output, "What's your email?", 'admin_email');

        /* Validate paths to projects and vhosts. */
        $projects = $this->validateDirectoryPath($projects);
        $vhosts = $this->validateDirectoryPath($vhosts);

        /* Validate parameters from user for new vhost creating. */
        if(!is_writable($vhosts)) throw new CannotWriteException(sprintf("Cannot write to '%s' directory.", $vhosts));
        if(file_exists($vhosts.$domain.".conf")) {
            $continueQuestion = new ConfirmationQuestion("VirtualHost exist. Do you want override? y/N", false, '/^y|Y|t|T/i');
            if(!$questioner->ask($input, $output, $continueQuestion)) {
                $output->writeln("<error>Aborted by user.</error>");
                return 385;
            }
        }

        /* Create DocumentRoot path. */
        $documentRoot = $web ?
            str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $projects.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.$web) :
            str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $projects.DIRECTORY_SEPARATOR.$domain);

        if(!file_exists($documentRoot)) {
            throw new FileNotFoundException(sprintf("Not found '%s' directory. Cannot create vhost for this DocumentRoot.", $projects.$domain));
        }

        /* Get certificates parameters if ssl option is set. */
        if($ssl) {
            $certRoot = $this->ask($input, $output, "What's your certificates root with all pem files.", 'cert_root');
            $certRoot = str_replace('*', $domain, $certRoot);
            $certRoot = $this->validateDirectoryPath($certRoot);

            $qLetsEncrypt = new ConfirmationQuestion("Use letsencrypt filenames? y/N", false, '/^y|Y|t|T/i');
            $letsencrypt = $questioner->ask($input, $output, $qLetsEncrypt);

            if($letsencrypt) {
                $cert = $nginx ? "fullchain.pem" : "cert.pem";
                $key = "privkey.pem";
                if(!$nginx) $chain = "chain.pem";
            } else {
                $qCert = new Question("What's filename of ".($nginx ? "fullchain" : "cert")." file? ");
                $qKey = new Question("What's filename of private key file? ");
                $cert = $questioner->ask($input, $output, $qCert);
                $key = $questioner->ask($input, $output, $qKey);
                if(!$nginx) {
                    $qChain = new Question("What's filename of chain file?");
                    $chain = $questioner->ask($input, $output, $qChain);
                }
            }
        }

        /* Add server aliases. */
        $aliases = [];
        $aliasQuestion = new Question("Add server aliases. WWW prefix will be auto added. <info>[Press enter/return for finish]</info> ");
        while($alias = $questioner->ask($input, $output, $aliasQuestion)) {
            if(!strlen($alias)) break;
            $aliases[] = $alias;
        }

        /* Initialize twig and render configuration from template. */
        $this->initTwig();
        $tplPath = $nginx ? 'nginx-vhost.twig' : 'apache-vhost.twig';
        if($template) $tplPath = $template;
        $configurationContent = $this->render($tplPath, [
            'ServerName' => $domain,
            'ServerAdmin' => $email,
            'DocumentRoot' => $documentRoot,
            'ServerAlias' => $aliases,
            'SSLCertificateFile' => isset($certRoot) && isset($cert) ? $certRoot.$cert : false,
            'SSLCertificateKeyFile' => isset($certRoot) && isset($key) ? $certRoot.$key : false,
            'SSLCertificateChainFile' => isset($certRoot) && isset($chain) ? $certRoot.$chain : false
        ]);

        /* Confirm and save or abort. */
        $output->writeln($configurationContent);
        $continueQuestion = new ConfirmationQuestion("<info>VirtualHost created. Accept and save?</info> Y/n", true, '/^y|Y|t|T/i');
        if($questioner->ask($input, $output, $continueQuestion)) {
            file_put_contents($vhosts.$domain.".conf", $configurationContent);
            $output->writeln("VirtualHost saved. Remember enable it running: <info>sudo a2ensite $domain && sudo service apache2 reload</info>");
        } else {
            $output->writeln("<error>Aborted by user.</error>");
        }

        return 0;
    }

    /**
     * Return path with DIRECTORY_SEPARATOR at end.
     *
     * @param string $path
     * @return string
     */
    private function validateDirectoryPath($path)
    {
        if(substr($path, strlen($path)-1, strlen($path)-1) != DIRECTORY_SEPARATOR) $path = $path.DIRECTORY_SEPARATOR;
        return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    }
}
