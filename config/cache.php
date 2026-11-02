<?php
return ['default'=>env('CACHE_STORE','database'),'stores'=>['array'=>['driver'=>'array'],'file'=>['driver'=>'file','path'=>storage_path('framework/cache/data')],'database'=>['driver'=>'database','connection'=>env('DB_CACHE_CONNECTION'),'table'=>'cache','lock_connection'=>env('DB_CACHE_LOCK_CONNECTION'),'lock_table'=>'cache_locks']],'prefix'=>env('CACHE_PREFIX','fida_crm')];
