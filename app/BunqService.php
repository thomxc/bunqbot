<?php

namespace App\Services;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\BunqException;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\UserCompany;
use bunq\Model\Generated\Endpoint\UserPerson;
use bunq\Util\BunqEnumApiEnvironmentType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BunqService {

    /**
     * @var UserPerson
     */
    private $user;

    public function __construct()
    {
        if (File::exists(config('services.bunq.context_location'))) {
            $this->setupContext();
            $this->setupCurrentUser();
        }
    }

    /**
     * @throws BunqException
     */
    public function init()
    {
        switch (config('services.bunq.env')) {
            case 'production':
                Log::info('We gebruiken de productie omgeving');
                $environmentType = BunqEnumApiEnvironmentType::PRODUCTION();
                break;
            case 'sandbox':
            default:
                Log::info('We gebruiken de sandbox omgeving');
                $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
                break;

        }
        $apiKey = config('services.bunq.key'); // Replace with your API key
        Log::info('We gebruiken de volgende API KEY: ' . str_limit($apiKey, 8));
        $deviceDescription = $_SERVER['SERVER_NAME']; // Replace with your device description
        Log::info('We gebruiken de volgende beschrijving om de App te beschrijven: ' . $deviceDescription);
        $permittedIps = []; // List the real expected IPs of this device or leave empty to use the current IP
        Log::info('De volgende IPs hebben toegang tot de API:' . implode(',', $permittedIps));
        $this->apiContext = ApiContext::create(
            $environmentType,
            $apiKey,
            $deviceDescription,
            $permittedIps
        );
        $result = $this->apiContext->save(config('services.bunq.context_location'));
        Log::info(var_export($result, true));
        Log::info('De API is geinitialiseerd met bovenstaande gegevens.');
    }


    /**
     * @return string
     */
    public function helloworld()
    {
        return $this->user->getLegalName();
    }

    public function saldo()
    {
        $monetaryAccountList = MonetaryAccount::listing();
        $listing = [];
        foreach ($monetaryAccountList->getValue() as $monetaryAccount) {
            $listing[] = sprintf('%s: %s %d', $monetaryAccount->getMonetaryAccountBank()->getDescription(), $monetaryAccount->getMonetaryAccountBank()->getBalance()->getCurrency(), $monetaryAccount->getMonetaryAccountBank()->getBalance()->getValue());
        }

        return $listing;
    }

    /**
     * @return array
     */
    public function monetaryAccountListings()
    {
        $monetaryAccountList = MonetaryAccount::listing();
        $listing = [];
        foreach ($monetaryAccountList->getValue() as $monetaryAccount) {
            $listing[] = $monetaryAccount->getMonetaryAccountBank()->getDescription();
        }

        return $listing;
    }

    /**
     * @throws BunqException
     */
    private function setupContext()
    {
        $apiContext = ApiContext::restore(config('services.bunq.context_location'));
        $apiContext->ensureSessionActive();
        $apiContext->save(config('services.bunq.context_location'));
        BunqContext::loadApiContext($apiContext);
        Log::info('API Context restored..');
    }

    /**
     * Retrieves the user that belongs to the API key.
     *
     * @throws BunqException
     */
    private function setupCurrentUser()
    {
        if (BunqContext::getUserContext()->isOnlyUserCompanySet()) {
            $this->user = BunqContext::getUserContext()->getUserCompany();
        } elseif (BunqContext::getUserContext()->isOnlyUserPersonSet()) {
            $this->user = BunqContext::getUserContext()->getUserPerson();
        } else {
            throw new BunqException(vsprintf(self::ERROR_USER_TYPE_UNEXPECTED, [get_class($this->user)]));
        }
    }
}
