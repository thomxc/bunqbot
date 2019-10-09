## Getting started
1. Kopieer .env.example naar .env en plaats je Bunq (sandbox) api key in de `BUNQ_API_KEY` variable.  
2. start een webserver met bijvoorbeeld `php artisan serve`
3. Navigeer naar `localhost:8000/botman/tinker` om een chat te beginnen.
4. Gebruik het chatcommand `hi binq init` om de chatbot te initialiseren
5. Gebruik het chatcommand `hi binq list` om alle rekeningen te laten tonen. Gebruik vervolgens het id in het resultaat als `BUNQ_ACCOUNT_ID`
6. Start een gesprek met: `hi binq` of `hallo binq`

## Bunq Sandbox API Key genereren
`curl https://public-api.sandbox.bunq.com/v1/sandbox-user -X POST --header "Content-Type: application/json" --header "Cache-Control: none" --header "User-Agent: curl-request" --header "X-Bunq-Client-Request-Id: $(date)randomId" --header "X-Bunq-Language: nl_NL" --header "X-Bunq-Region: nl_NL" --header "X-Bunq-Geolocation: 0 0 0 0 000"`

## Dependencies
* PHP 7.1 
* [Botman 2](https://botman.io/)
* [Laravel 5.7](https://laravel.com)
* [BunqSDK 1.10](https://github.com/bunq/sdk_php)
