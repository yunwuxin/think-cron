<?php

namespace yunwuxin\cron;

use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use Swoole\Timer;
use think\swoole\Manager;
use Throwable;
use yunwuxin\cron\command\Run;
use yunwuxin\cron\command\Schedule;
use yunwuxin\cron\event\TaskFailed;

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
                    $manager->runInSandbox(function (Scheduler $scheduler) {
                        $tasks = $scheduler->dueTasks();

                        if (empty($tasks)) {
                            return;
                        }

                        $wait = new WaitGroup();

                        // 每个任务一个协程并行执行
                        foreach ($tasks as $task) {
                            $wait->add();

                            $cid = Coroutine::create(function () use ($scheduler, $task, $wait) {
                                try {
                                    $scheduler->processTask($task);
                                } catch (Throwable $e) {
                                    // 协程内未捕获的异常会导致worker退出，统一转为任务失败事件
                                    $scheduler->trigger(new TaskFailed($task, $e));
                                } finally {
                                    $wait->done();
                                }
                            });

                            if ($cid === false) {
                                $wait->done();
                            }
                        }

                        $wait->wait();
                    });
                });
            }, "cron");
        });
    }
}
