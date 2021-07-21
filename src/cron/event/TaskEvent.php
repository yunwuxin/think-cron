<?php

namespace yunwuxin\cron\event;

use yunwuxin\cron\Task;

abstract class TaskEvent
{
    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getName()
    {
        return get_class($this->task);
    }
}
