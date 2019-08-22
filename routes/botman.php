<?php

use App\Http\Controllers\BotManController;
use Illuminate\Support\Facades\File;

$botman = resolve('botman');

$botman->hears('H.*\ Binq\ ?{command}', '\App\Http\Controllers\BinqController@startConversation');
