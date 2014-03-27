<?php

require_once __DIR__.'/../vendor/autoload.php';

$execute = function(){
    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
    $output_error = function($file, $message='')use($output){
        $output->writeln("<fg=red>[fail]</fg=red> $file $message");
    };
    $output_success = function($file, $message='')use($output){
        $output->writeln("<info>[success]</info> $file $message");
    };
    
    $baseDirectory = __DIR__.'/FullStackTest/htdocs/';
    $filesShouldExist = array(
        'app/etc/modules/Aoe_Profiler.xml',
    );
    
    $directoriesShouldExist = array(
        
    );
    
    $shouldNotExist = array(
        
    );

    $output->writeln("<question>start filesShouldExist</question>");
    foreach( $filesShouldExist as $file){
        if( !file_exists($baseDirectory.$file) ){
            $output_error($file);
        }else{
            $output_success($file);
        }
    }
    
    
    $output->writeln("<question>start directoriesShouldExist (not implemented)</question>");
    foreach( $directoriesShouldExist as $file){
    }
    
    
    $output->writeln("<question>start shouldNotExist</question>");
    foreach( $shouldNotExist as $file){
        if( file_exists($baseDirectory.$file) ){
            $output_error($file);
        }else{
            $output_success($file);
        }
    }
};

$execute();

