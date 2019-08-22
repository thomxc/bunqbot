<?php

namespace App\Http\Controllers;

use App\Conversations\BinqConversation;
use App\Services\BunqService;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BinqController extends Controller
{
    /**
     * @var BunqService
     */
    private $bunq;

    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->listen();


    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    public function startConversation(Botman $bot, $command)
    {
        $bot->startConversation(new BinqConversation($command));
    }


}
