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
                    $this->printPayments($this->bunq->getAllPaymentByAccountId($id));
                }
            });
        } else {
            $this->printPayments($this->bunq->getAllPaymentByAccountId(config('services.bunq.bank_account_id')));
        }
    }

    /**
     * @param Payment[] $payments
     */
    private function printPayments($payments)
    {
        if (count($payments) > 0) {
            $payments = collect($payments);
            $payments = $payments->sortBy(function (Payment $payment) { return $payment->getCreated(); });
            foreach ($payments as $payment) {
                if ($payment->getAmount()->getValue() > 0) {
                    $emoji = '⬆️';
                } else {
                    $emoji = '⬇️';
                }
                $this->say(sprintf('%s %s: *%s* - %s - %s%s', $emoji, \Carbon\Carbon::parse($payment->getCreated())->format('d M-Y'), $payment->getAlias()->getDisplayName(), $payment->getDescription(), '€', $payment->getAmount()->getValue()));
            }
        } else {
            $this->say('Geen betalingen gevonden..');
        }
    }
}