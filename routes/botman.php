<?php
use App\Http\Controllers\BotManController;
use Illuminate\Support\Facades\File;

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $hello = app(\App\Services\BunqService::class)->helloworld();
    $bot->reply('Hello! ' . $hello);
});
$botman->hears('Start conversation', BotManController::class.'@startConversation');

$botman->hears('saldo', function ($bot) {
    $saldo = app(\App\Services\BunqService::class)->saldo();
    foreach ($saldo as $account) {
        $bot->reply($account);
    }
});

$botman->hears('list', function ($bot) {
   $listing =  app(\App\Services\BunqService::class)->monetaryAccountListings();
   $bot->reply(sprintf('%s accounts gevonden', count($listing)));
   foreach ($listing as $accountDescription) {
       $bot->reply($accountDescription);
   }
});

$botman->hears('init', function ($bot) {
    if (File::exists(config('services.bunq.context_location'))) {
        $bot->reply('App is al geinitialiseerd..');
    } else {
        app(\App\Services\BunqService::class)->init($bot);
    }
});

