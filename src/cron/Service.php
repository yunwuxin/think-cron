<?php

namespace yunwuxin\cron;

use yunwuxin\cron\command\Run;
use yunwuxin\cron\command\Schedule;

class Service extends \think\Service
{

    public function boot()
    {
        $this->commands([
            Run::class,
            Schedule::class,
        ]);
    }
}
