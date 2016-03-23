<?php

namespace SyntaxErro\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SyntaxErro\Exception\FileNotReadableException;

class RotateCat extends Command
{

    /**
     * @var array
     */
    private $files = [];

    /**
     * Configure h:h command.
     * @void
     */
    protected function configure()
    {
        $this
            ->setName('rotate:cat')
            ->setDescription('Cat all rotated logs.')
            ->addArgument(
                'dir',
                InputArgument::REQUIRED,
                'Directory with logs.'
            )
            ->addArgument(
                'pattern',
                InputArgument::REQUIRED,
                'Filename pattern for search rotated log files.'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit files for open.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @throws FileNotReadableException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dir = $input->getArgument('dir');
        $pattern = '/'.$input->getArgument('pattern').'/';
        $limit = $input->getOption('limit');

        if(!is_readable($dir)) throw new FileNotReadableException(sprintf("Cannot open %s directory.", $dir));
        foreach(new \DirectoryIterator($dir) as $logFile) {
            if(!$logFile->isDot() && $logFile->isFile() && $logFile->isReadable() && preg_match($pattern, $logFile->getFilename())) {
                $this->files[] = $logFile->getPathname();
            }
        }

        uasort($this->files, function($a, $b) {
            $aCount = preg_replace('/[^0-9]+/', '', $a);
            $bCount = preg_replace('/[^0-9]+/', '', $b);

            $aCount = is_numeric($aCount) ? $aCount : 0;
            $bCount = is_numeric($bCount) ? $bCount : 0;
            return $bCount - $aCount;
        });

        if($limit) {
            $this->files = array_values($this->files);
            $cnt = count($this->files);
            foreach($this->files as $i => $file) {
                if($i < $cnt-$limit) unset($this->files[$i]);
            }
        }


        foreach($this->files as $file) {
            $output->write($this->openFile(new \SplFileInfo($file)));
        }
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    private function openFile(\SplFileInfo $file)
    {
        if(preg_match('/gz/', $file->getExtension())) {
            return $this->gz_get_contents($file->getPathname());
        } else {
            return file_get_contents($file->getPathname());
        }
    }

    /**
     * @param string $path to gzipped file
     * @return string
     */
    private function gz_get_contents($path)
    {
        $handle = fopen($path, "rb");
        fseek($handle, -4, SEEK_END);
        $buf = fread($handle, 4);
        $unpacked = unpack("V", $buf);
        $uncompressedSize = end($unpacked);
        fclose($handle);

        // read the gzipped content, specifying the exact length
        $handle = gzopen($path, "rb");
        $contents = gzread($handle, $uncompressedSize);
        gzclose($handle);

        return $contents;
    }
}
