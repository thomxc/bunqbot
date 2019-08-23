<?php

namespace App\Services;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\BunqException;
use bunq\Http\Pagination;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\RequestInquiry;
use bunq\Model\Generated\Endpoint\RequestResponse;
use bunq\Model\Generated\Endpoint\UserCompany;
use bunq\Model\Generated\Endpoint\UserPerson;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;
use Hamcrest\Core\IsNotTest;
use http\Env\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BunqService {
    const CURRENCY_TYPE_EUR = 'EUR';
    const POINTER_TYPE_EMAIL = 'EMAIL';

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
        $listing = [];
        $monetaryAccount = $this->getAccountById(config('services.bunq.bank_account_id'));
        $listing[] = sprintf('%s: %s %d', $monetaryAccount->getDescription(), $monetaryAccount->getBalance()->getCurrency(), $monetaryAccount->getBalance()->getValue());

        return $listing;
    }

    /**
     * @return array|MonetaryAccount[]
     */
    public function getAccounts()
    {
        return MonetaryAccount::listing()->getValue();
    }
    /**
     * @return array
     */
    public function monetaryAccountListings()
    {
        $monetaryAccountList = MonetaryAccount::listing();
        $listing = [];
        foreach ($monetaryAccountList->getValue() as $monetaryAccount) {
            $listing[] = sprintf('%s - %s', $monetaryAccount->getMonetaryAccountBank()->getId(), $monetaryAccount->getMonetaryAccountBank()->getDescription());
        }

        return $listing;
    }

    /**
     * @param $id
     * @return bool|MonetaryAccountBank
     */
    public function getAccountById($id)
    {
        $accounts = $this->getAccounts();
        foreach ($accounts as $account) {
            if ($account->getMonetaryAccountBank()->getId() == $id) {
                return $account->getMonetaryAccountBank();
            }
        }
        return false;
    }

    /**
     * @param $id
     * @return array|Payment[]
     */
    public function getAllPaymentByAccountId($id)
    {
        return $this->getAllPayment($id);
    }

    /**
     * @param int $monetaryAccount
     * @param int $count
     *
     * @return Payment[]
     */
    public function getAllPayment($monetaryAccount, int $count = 100): array
    {
        $pagination = new Pagination();
        $pagination->setCount($count);
        return Payment::listing(
            $monetaryAccount,
            $pagination->getUrlParamsCountOnly()
        )->getValue();
    }

    /**
     * @return int
     */
    public function makePaymentRequest()
    {
        return $this->makeRequest(35, 'sugardaddy@bunq.com', 'Boodschappen Werk', config('services.bunq.bank_account_id'));
    }

    /**
     * @param string $amount
     * @param string $recipient
     * @param string $description
     * @param int $monetaryAccountId
     *
     * @return int
     */
    public function makeRequest(
        string $amount,
        string $recipient,
        string $description,
        int $monetaryAccountId
    ): int {
        // Create a new request and retrieve it's id.
        return RequestInquiry::create(
            new Amount($amount, self::CURRENCY_TYPE_EUR),
            new Pointer(self::POINTER_TYPE_EMAIL, $recipient),
            $description,
            true,
            $monetaryAccountId
        )->getValue();
    }

    /**
     * @return array|RequestInquiry[]
     */
    public function getAllPaymentRequests()
    {
        return RequestInquiry::listing(
            config('services.bunq.bank_account_id')
        )->getValue();
    }

    /**
     * @param $id
     * @return RequestInquiry
     */
    public function getPaymentRequestById($id)
    {
        return RequestInquiry::get($id)->getValue();
    }

    /**
     * @param $id
     * @return array|RequestResponse[]
     */
    public function getRequestResponses($id)
    {
        return RequestResponse::listing(
            config('services.bunq.bank_account_id')
        )->getValue();
    }

    /**
     * @param string $amount
     * @param string $recipient
     * @param string $description
     * @param int $monetaryAccount
     *
     * @return int
     */
    public function makePayment(
        string $amount,
        string $recipient,
        string $description,
        $monetaryAccount
    ): int {
        // Create a new payment and retrieve it's id.
        return Payment::create(
            new Amount($amount, self::CURRENCY_TYPE_EUR),
            new Pointer(self::POINTER_TYPE_EMAIL, $recipient),
            $description,
            $monetaryAccount
        )->getValue();
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
