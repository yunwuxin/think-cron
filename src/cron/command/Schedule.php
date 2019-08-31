<?php

namespace yunwuxin\cron\command;

use Symfony\Component\Process\Process;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Schedule extends Command
{

    protected function configure()
    {
        $this->setName('cron:schedule');
    }

    protected function execute(Input $input, Output $output)
    {

        if ('\\' == DIRECTORY_SEPARATOR) {
            $command = 'start /B "' . PHP_BINARY . '" think cron:run';
        } else {
            $command = 'nohup "' . PHP_BINARY . '" think cron:run >> /dev/null 2>&1 &';
        }

        $process = Process::fromShellCommandline($command);

        while (true) {
            $process->run();
            sleep(60);
        }
    }
}
