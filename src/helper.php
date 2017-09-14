<?php

use yunwuxin\cron\command\Run;
use yunwuxin\cron\command\Schedule;

\think\Console::addDefaultCommands([
    Run::class,
    Schedule::class
]);