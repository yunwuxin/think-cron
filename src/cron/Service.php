<?php

namespace yunwuxin\cron;

use Swoole\Timer;
use think\swoole\Manager;
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

        $this->app->event->listen('swoole.init', function (Manager $manager) {
            $manager->addWorker(function () use ($manager) {
                Timer::tick(60 * 1000, function () use ($manager) {
                    $manager->runWithBarrier([$manager, 'runInSandbox'], function (Scheduler $scheduler) {
                        $scheduler->run();
                    });
                });
            }, "cron");
        });
    }
}
