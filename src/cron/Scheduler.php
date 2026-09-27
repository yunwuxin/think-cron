<?php

namespace yunwuxin\cron;

use Carbon\Carbon;
use Exception;
use think\App;
use think\cache\Driver;
use yunwuxin\cron\event\TaskEvent;
use yunwuxin\cron\event\TaskFailed;
use yunwuxin\cron\event\TaskProcessed;
use yunwuxin\cron\event\TaskSkipped;

class Scheduler
{
    /** @var App */
    protected $app;

    /** @var Carbon */
    protected $startedAt;

    protected $tasks = [];

    /** @var Driver */
    protected $cache;

    public function __construct(App $app)
    {
        $this->app   = $app;
        $this->tasks = $app->config->get('cron.tasks', []);
        $this->cache = $app->cache->store($app->config->get('cron.store', null));
    }

    public function run()
    {
        foreach ($this->dueTasks() as $task) {
            $this->processTask($task);
        }
    }

    /**
     * 获取本次调度到期的任务（以调用时间为调度基准）
     * @return Task[]
     */
    public function dueTasks()
    {
        $this->startedAt = Carbon::now();

        $tasks = [];

        foreach ($this->tasks as $taskClass) {

            if (is_subclass_of($taskClass, Task::class)) {

                /** @var Task $task */
                $task = $this->app->invokeClass($taskClass, [$this->cache]);
                // 统一以本次调度启动时间为基准判断是否到期，
                // 避免前序任务执行耗时跨分钟后，后续本该执行的任务被跳过
                if ($task->isDue($this->startedAt) && $task->filtersPass()) {
                    $tasks[] = $task;
                }
            }
        }

        return $tasks;
    }

    /**
     * 执行任务
     * @param $task Task
     */
    public function processTask($task)
    {
        if ($task->onOneServer) {
            $this->runSingleServerTask($task);
        } else {
            $this->runTask($task);
        }

        $this->trigger(new TaskProcessed($task));
    }

    /**
     * 触发事件
     * @param $event TaskEvent
     */
    public function trigger($event)
    {
        $this->app->event->trigger($event);
    }

    /**
     * @param $task Task
     * @return bool
     */
    protected function serverShouldRun($task)
    {
        $key = $task->mutexName() . $this->startedAt->format('Hi');
        if ($this->cache->has($key)) {
            return false;
        }
        $this->cache->set($key, true, 60);
        return true;
    }

    protected function runSingleServerTask($task)
    {
        if ($this->serverShouldRun($task)) {
            $this->runTask($task);
        } else {
            $this->trigger(new TaskSkipped($task));
        }
    }

    /**
     * @param $task Task
     */
    protected function runTask($task)
    {
        try {
            $task->run();
        } catch (Exception $e) {
            $this->trigger(new TaskFailed($task, $e));
        }
    }
}
