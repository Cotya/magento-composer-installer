<?php

require_once(__DIR__.'/bootstrap.php');

use Symfony\Component\Process\Process;

echo "Artifact Generation started".PHP_EOL;

$function = function() {
    $projectPath = str_replace('\\', '/', realpath(__DIR__ . '/../'));

    $packagesPath = $projectPath . '/tests/res/packages';

    $runInProjectRoot = function ($command) use ($projectPath) {
        $process = new Process($command, $projectPath);
        $process->setTimeout(120);
        $process->run();
        return $process;
    };

    $composerJsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE  ;
    $addTestVersionToComposerJson = function ($version = "999.0.0") use ($projectPath, $composerJsonOptions) {
        $filePath = $projectPath.'/composer.json';
        $jsonObject = json_decode(file_get_contents($filePath), true);
        $jsonObject['version'] = $version;
        file_put_contents($filePath, json_encode($jsonObject, $composerJsonOptions));
    };
    $removeTestVersionFromComposerJson = function () use ($projectPath, $composerJsonOptions) {
        $filePath = $projectPath.'/composer.json';
        $jsonObject = json_decode(file_get_contents($filePath), true);
        unset($jsonObject['version']);
        file_put_contents($filePath, json_encode($jsonObject, $composerJsonOptions));
    };

    $composerCommand = 'composer';
    if (getenv('TRAVIS') == "true") {
        $composerCommand = $projectPath . '/composer.phar';
    } elseif (getenv('APPVEYOR') == 'True') {
        $composerCommand = 'php ' . $projectPath . '/composer.phar';
    } elseif ($runInProjectRoot('./composer.phar')->getExitCode() === 0) {
        $composerCommand = 'composer.phar';
    }

    $createComposerInstallerArtifact = function ()
    use (
        $projectPath,
        $addTestVersionToComposerJson,
        $removeTestVersionFromComposerJson,
        $runInProjectRoot,
        $composerCommand
    ) {


        $basePath = $projectPath . '/tests/FullStackTest';
        @unlink($projectPath.'/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink($basePath.'/magento/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink($basePath.'/magento-modules/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink($projectPath.'/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink($basePath.'/magento/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink($basePath.'/magento-modules/vendor/theseer/directoryscanner/tests/_data/nested/empty');

        $addTestVersionToComposerJson("999.0.0");
        $command = $composerCommand.' archive --format=zip --dir="tests/FullStackTest/artifact" -vvv';
        $process = $runInProjectRoot($command);

        if ($process->getExitCode() !== 0) {
            $message = sprintf(
                "process for <code>%s</code> exited with %s: %s%sError Message:%s%s%sOutput:%s%s",
                $process->getCommandLine(),
                $process->getExitCode(),
                $process->getExitCodeText(),
                PHP_EOL,
                PHP_EOL,
                $process->getErrorOutput(),
                PHP_EOL,
                PHP_EOL,
                $process->getOutput()
            );
            echo $message;
        } else {
            // everything fine, I assume
        }
        $addTestVersionToComposerJson("997.0.0");
        $command = $composerCommand.' archive --format=zip --dir="tests/FullStackTest/artifact" -vvv';
        $process = $runInProjectRoot($command);

        if ($process->getExitCode() !== 0) {
            $message = sprintf(
                "process for <code>%s</code> exited with %s: %s%sError Message:%s%s%sOutput:%s%s",
                $process->getCommandLine(),
                $process->getExitCode(),
                $process->getExitCodeText(),
                PHP_EOL,
                PHP_EOL,
                $process->getErrorOutput(),
                PHP_EOL,
                PHP_EOL,
                $process->getOutput()
            );
            echo $message;
        } else {
            // everything fine, I assume
        }
        $removeTestVersionFromComposerJson();
    };

    echo "start create Composer Artifact".PHP_EOL;
    $command = 'perl -pi.bak -e \'s/999/997/g\' ./composer.json';
    $process = $runInProjectRoot($command);
    $createComposerInstallerArtifact();
    $command = 'perl -pi.bak -e \'s/997/999/g\' ./composer.json';
    $process = $runInProjectRoot($command);
    $createComposerInstallerArtifact();
    echo "finish create Composer Artifact".PHP_EOL;

    echo "start create Composer Mock Artifact".PHP_EOL;
    $directory = new \DirectoryIterator($packagesPath);
    /** @var \DirectoryIterator $fileinfo */
    foreach ($directory as $file) {
        if (!$file->isDot() && $file->isDir()) {
            $args = ' archive --format=zip --dir="'.$projectPath.'/tests/FullStackTest/artifact" -vvv';
            $process = new Process(
                $composerCommand . $args,
                $file->getPathname()
            );
            $process->setTimeout(120);
            $process->run();
            if ($process->getExitCode() !== 0) {
                $message = sprintf(
                    "process in <code>%s</code> for <code>%s</code> exited with %s: %s%sError Message:%s%s%sOutput:%s%s",
                    $file->getPathname(),
                    $process->getCommandLine(),
                    $process->getExitCode(),
                    $process->getExitCodeText(),
                    PHP_EOL,
                    PHP_EOL,
                    $process->getErrorOutput(),
                    PHP_EOL,
                    PHP_EOL,
                    $process->getOutput()
                );
                echo $message;
            }
        }
    };
    echo "finish create Composer Mock Artifact".PHP_EOL;
};

$function();
unset($function);

echo "Artifact Generation finished".PHP_EOL;
