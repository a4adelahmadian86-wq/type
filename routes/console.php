<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('farast:purge-files')->daily();
