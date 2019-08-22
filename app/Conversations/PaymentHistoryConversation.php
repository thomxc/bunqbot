<?php


namespace App\Conversations;


use App\Services\BunqService;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\Payment;
use Illuminate\Foundation\Inspiring;

class PaymentHistoryConversation extends Conversation
{
    /**
     * @var MonetaryAccount[]
     */
    private $monetaryAccounts;
    /**
     * @var \Illuminate\Foundation\Application
     */
    private $bunq;
    /**
     * @var Payment[]
     */
    private $payments;

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->bunq = app(BunqService::class);
        $this->printPayments(config('services.bunq.bank_account_id'));
    }

    private function whichBankAccount()
    {
        $buttons = [];

        if (count($this->monetaryAccounts) > 1) {
            foreach ($this->monetaryAccounts as $account) {
                $id = $account->getMonetaryAccountBank()->getId();
                $description = $account->getMonetaryAccountBank()->getDescription();
                $buttons[] = Button::create($description)->value($id);
            }
            $question = Question::create("Welke bankrekening wil je checken?")->fallback('Unable to ask question')->callbackId('which_bankaccount')->addButtons($buttons);

            return $this->ask($question, function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    $id = $answer->getValue();
                    $this->printPayments($id);
                }
            });
        } else {
            $this->printPayments($this->monetaryAccounts[0]->getMonetaryAccountBank()->getId());
        }
    }

    private function printPayments($id)
    {
        $this->payments = $this->bunq->getAllPaymentByAccountId($id);
        if (count($this->payments) > 0) {
            foreach ($this->payments as $payment) {
                $this->say(sprintf('%s - %s: %s', $payment->getDescription(), $payment->getType(), $payment->getAmount()));
            }
        } else {
            $this->say('Geen betalingen gevonden..');
        }
    }
}