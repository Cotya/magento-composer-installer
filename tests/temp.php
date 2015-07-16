<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\ComposerTestFramework\Composer;

use Symfony\Component\Process\Process;

class Wrapper
{

    /**
     * @var \SPLFileInfo
     */
    protected $executable;

    protected $composerCommandTimeout = 300;

    protected $processObjectList = array();

    public function __construct($version = null)
    {
        $downloadUrl = 'https://getcomposer.org/composer.phar';
        if (null!==$version) {
            $downloadUrl = 'https://getcomposer.org/download/'.$version.'/composer.phar';
        }

        $targetPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ComposerTestRes/';
        $targetPath .= 'composer_'.$version.'.phar';
        $this->executable = new \SplFileInfo($targetPath);
        if (!$this->executable->isFile()) {
            if (!is_dir($this->executable->getPath())) {
                mkdir($this->executable->getPath(), 0777, true);
            }
            file_put_contents($targetPath, fopen($downloadUrl, 'r'));
        }
    }

    protected function getComposerCommand()
    {

        if (getenv('APPVEYOR') == "True") {
            $command = 'php '.$this->executable->getRealPath();
        } else {
            $command = '/usr/bin/env php '.$this->executable->getRealPath();
        }
        return $command;
    }

    protected function getComposerArgs()
    {
        $args = '--prefer-dist --no-dev --no-progress --no-interaction --profile -vvv';
        return $args;
    }

    protected function handleProcessResult(Process $process)
    {
        $this->processObjectList[] = $process;
        if ($process->getExitCode() !== 0) {
            $message = $messageEnd =
                'process for <code>'.$process->getCommandLine().'</code> exited with '.
                $process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            $message .= PHP_EOL.$messageEnd;
            throw new \Exception($message, $process->getExitCode());
        }
    }

    public function archive(\SPLFileInfo $packageDirectory, \SplFileInfo $targetDirectory)
    {
        $process = new Process(
            $this->getComposerCommand().' archive --format=zip --dir="'.$targetDirectory->getPathname().'" -vvv',
            $packageDirectory->getPathname()
        );
        $process->run();
        $this->handleProcessResult($process);
    }

    public function install(\SplFileInfo $projectDirectory, \SplFileObject $composerJson)
    {
        //file_put_contents is not able to handle SplString
        file_put_contents(
            $projectDirectory->getPathname().'/composer.json',
            (string)\Cotya\ComposerTestFramework\Helper\FileObject::getContent($composerJson)
        );
        $process = new Process(
            $this->getComposerCommand().' install '.$this->getComposerArgs().' ',
            $projectDirectory->getPathname()
        );
        $process->setTimeout($this->composerCommandTimeout);
        $process->run();
        $this->handleProcessResult($process);
    }

    public function update(\SplFileInfo $projectDirectory, \SplFileObject $composerJson)
    {
        //file_put_contents is not able to handle SplString
        file_put_contents(
            $projectDirectory->getPathname().'/composer.json',
            (string)\Cotya\ComposerTestFramework\Helper\FileObject::getContent($composerJson)
        );
        $process = new Process(
            $this->getComposerCommand().' update '.$this->getComposerArgs().' ',
            $projectDirectory->getPathname()
        );
        $process->setTimeout($this->composerCommandTimeout);
        $process->run();
        $this->handleProcessResult($process);
    }

    public function createProject(\SplFileInfo $projectDirectory, $projectName, $version = null, $repository = null)
    {

        $command = $this->getComposerCommand().' create-project '.$this->getComposerArgs();

        if (null !== $repository) {
            $command .=  ' --repository-url="'.$repository.'" ';
        }
        $command .=  ' "'.$projectName.'" ';
        if (null !== $version) {
            $command .=  ' "'.$version.'" ';
        }
        $process = new Process($command, $projectDirectory->getPathname());
        $process->setTimeout($this->composerCommandTimeout);
        $process->run();
        $this->handleProcessResult($process);
    }
}
