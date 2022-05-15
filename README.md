# think-cron 计划任务

## 安装方法
```
composer require yunwuxin/think-cron
```

## 使用方法

### 创建任务类

```
<?php

namespace app\task;

use yunwuxin\cron\Task;

class DemoTask extends Task
{

    public function configure()
    {
        $this->daily(); //设置任务的周期，每天执行一次，更多的方法可以查看源代码，都有注释
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        //...具体的任务执行
    }
}

```

### 配置
> 配置文件位于 config/cron.php

```
return [
    'tasks' => [
        \app\task\DemoTask::class, //任务的完整类名
    ]
];
```

### 任务监听

#### 两种方法：

> 方法一 (推荐)

起一个常驻进程，可以配合supervisor使用
~~~
php think cron:schedule
~~~

> 方法二

在系统的计划任务里添加
~~~
* * * * * php /path/to/think cron:run >> /dev/null 2>&1
~~~
