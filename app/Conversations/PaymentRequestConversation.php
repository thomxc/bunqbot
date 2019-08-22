<?php


namespace App\Conversations;


use App\Services\BunqService;
use BotMan\BotMan\Messages\Conversations\Conversation;
use bunq\Model\Generated\Endpoint\RequestInquiry;

class PaymentRequestConversation extends Conversation
{
    /**
     * @var int
     */
    private $requestinquiry_id;
    /**
     * @var RequestInquiry
     */
    private $request;

    public function __construct($requestinquiry_id)
    {
        $this->requestinquiry_id = $requestinquiry_id;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $this->getBot()->reply(sprintf('Je hebt op Request met id: %s geklikt', $this->requestinquiry_id));
        $bunq = app(BunqService::class);
        $this->request = $bunq->getPaymentRequestById($this->requestinquiry_id);
        $this->getBot()->reply(sprintf('(%s) Dit betaalverzoek is aangemaakt op %s en verloopt op %s de link is: %s', $this->request->getStatus(), \Carbon\Carbon::parse($this->request->getCreated()), \Carbon\Carbon::parse($this->request->getTimeExpiry()), $this->request->getBunqmeShareUrl()));
//        $bunq->getRequestResponses($this->requestinquiry_id);
//        $this->getBot()->reply(sprintf('%s personen hebben inmiddels betaald namelijk: %s'));
    }
}