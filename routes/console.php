<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('web-intelligence:prune')->dailyAt('03:20')->withoutOverlapping();
