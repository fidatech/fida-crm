<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('crm:send-campaigns')->everyMinute()->withoutOverlapping();

Schedule::command('crm:discover-prospects')->hourly()->withoutOverlapping();
