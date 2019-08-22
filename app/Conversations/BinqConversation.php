<?php


namespace App\Conversations;


use App\Services\BunqService;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\File;

class BinqConversation extends Conversation
{
    protected $command;
    /**
     * @var string
     */
    protected $firstname;

    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $this->firstname = $this->getBot()->getUser()->getFirstName();

        if (!empty($this->command)) {
            return $this->startCommand($this->command);
        }

        $question = Question::create("Hi $this->firstname, Wat kan ik voor je doen?")->fallback('Unable to ask question')->callbackId('what_can_i_do')->addButtons([
            Button::create('Saldo opvragen')->value('saldo'),
            Button::create('Rekeningoverzicht')->value('rekeningoverzicht'),
            Button::create('Wie hebben er al betaald deze maand?')->value('al_betaald'),
            Button::create('Nieuw betaalverzoek aanmaken')->value('betaalverzoek'),
        ]);

        $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->startCommand($answer->getValue());
            }
        });
    }

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startCommand($command)
    {
        $bot = $this->getBot();

        switch ($command) {
            case 'saldo':
                $this->saldoCommand($bot);
                break;
            case 'list':
                $this->listCommand($bot);
                break;
            case 'init':
                $this->initCommand($bot);
                break;
            case 'rekeningoverzicht':
                $this->paymentsCommand($bot);
                break;
            case 'betaalverzoek':
                $this->paymentRequest($bot);
                break;
            case 'al_betaald':
            default:
                $bot->reply('Hello! ' . $this->firstname);

        }
    }

    private function saldoCommand(Botman $bot)
    {
        $bot->types();
        $bot->reply('even kijken..');
        $bunq = app(BunqService::class);
        $bot->types();
        $saldo = $bunq->saldo();
        foreach ($saldo as $account) {
            $bot->reply($account);
        }
    }

    private function listCommand(Botman $bot)
    {
        $bunq = app(BunqService::class);

        $bot->types();
        $listing = $bunq->monetaryAccountListings();
        $bot->reply(sprintf('%s accounts gevonden', count($listing)));
        foreach ($listing as $accountDescription) {
            $bot->reply($accountDescription);
        }
    }

    private function initCommand(BotMan $bot)
    {
        if (File::exists(config('services.bunq.context_location'))) {
            $bot->reply('App is al geinitialiseerd..');
        } else {
            $bunq = app(BunqService::class);
            $bunq->init($bot);
        }
    }

    private function paymentsCommand(BotMan $bot)
    {
        $bot->types();
        $bot->startConversation(new PaymentHistoryConversation());
    }

    private function paymentRequest(BotMan $bot)
    {
        $bot->types();
        $bot->ask(Question::create('Weet je zeker dat je een nieuw betaalverzoek wilt maken?')->callbackId('are_you_sure')->addButtons([
            Button::create('Ja ik weet het zeker')->value('yes'),
            Button::create('Nee toch niet')->value('no'),
        ]), function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'yes') {
                    $this->getBot()->reply('Ok, komt ie:');
                    $bunq = app(BunqService::class);
                    $request = $bunq->makePaymentRequest();
                } else {
                    $this->getBot()->reply('Ok, danniet');
                }
            }
        });
    }
}