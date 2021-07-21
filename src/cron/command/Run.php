<?php

namespace yunwuxin\cron\command;

use Carbon\Carbon;
use think\console\Command;
use think\exception\Handle;
use yunwuxin\cron\event\TaskFailed;
use yunwuxin\cron\event\TaskProcessed;
use yunwuxin\cron\event\TaskSkipped;
use yunwuxin\cron\Scheduler;

class Run extends Command
{
    /** @var Carbon */
    protected $startedAt;

    protected function configure()
    {
        $this->startedAt = Carbon::now();
        $this->setName('cron:run');
    }

    public function handle(Scheduler $scheduler)
    {
        $this->listenForEvents();

        $scheduler->run();
    }

    /**
     * 注册事件
     */
    protected function listenForEvents()
    {
        $this->app->event->listen(TaskProcessed::class, function (TaskProcessed $event) {
            $this->output->writeln("Task {$event->getName()} run at " . Carbon::now());
        });

        $this->app->event->listen(TaskSkipped::class, function (TaskSkipped $event) {
            $this->output->writeln('<info>Skipping task (has already run on another server):</info> ' . $event->getName());
        });

        $this->app->event->listen(TaskFailed::class, function (TaskFailed $event) {
            $this->output->writeln("Task {$event->getName()} failed at " . Carbon::now());

            /** @var Handle $handle */
            $handle = $this->app->make(Handle::class);

            $handle->renderForConsole($this->output, $event->exception);

            $handle->report($event->exception);
        });
    }

}
