<?php 
namespace adman9000\bittrex;

class BittrexAPI
{
    protected $key;     // API key
    protected $secret;  // API secret
    protected $url;     // API base URL
    protected $version; // API version
    protected $curl;    // curl handle

    protected $market_url;
    protected $public_url;
    protected $public_url_v2;
    protected $account_url;

    /**
     * Client constructor.
     *
     * @param array $auth
     * @param array $urls
     */
    public function __construct(array $auth=null, array $urls=null) {
    
        if(!$auth) $auth = config("bittrex.auth");
        if(!$urls) $urls = config("bittrex.urls");

        $this->key    = array_get($auth, 'key');
        $this->secret = array_get($auth, 'secret');
        
        $this->market_url  = array_get($urls, 'market');
        $this->public_url  = array_get($urls, 'public');
        $this->public_url_v2  = array_get($urls, 'publicv2');
        $this->account_url = array_get($urls, 'account');

    }

    /**
     * Destructor function
     **/
    function __destruct()
    {
    }
    
    
    /**
     * setAPI()
     * @param $key - API key
     * @param $secret - API secret
     * We can change the API key to access different accounts
     **/
    function setAPI($key, $secret) {

       $this->key = $key;
       $this->secret = $secret;
    }


    /**
     ---------- PUBLIC FUNCTIONS ----------
    * getTicker
    * getTickers
    * getCurrencies
    * getAssetPairs (for backwards compatibility)
    * getMarkets (calls getAssetPairs)
    *
    *
    *
    * 
     **/

    /**
     * Used to get the current tick values for a market.
     *
     * @param string $market a string literal for the market (ex: BTC-LTC)
     * @return array
     */
    public function getTicker($market) {
        return $this->publicRequest('getticker', [
            'market' => $market
        ]);
    }
    
    /**
    * Use market summaries to get a ticker for all markets
    **/
     public function getTickers() {
        return $this->publicRequest('getmarketsummaries');
    }
    

    /**
     * Used to get all supported currencies at Bittrex along with other meta data.
     *
     * @return array
     */
    public function getCurrencies() {
        return $this->publicRequest('getcurrencies');
    }

     /**
     * Used to get the open and available trading markets at Bittrex along with other meta data.
     *
     * @return array
     */
    public function getMarkets() {
        return $this->publicRequest('getmarkets');
    }


    /**
     * Used to retrieve the latest trades that have occurred for a specific market.
     *
     * @return array
     */
    public function getMarketHistory($market) {
        return $this->publicRequest('getmarkethistory', [
            'market' => $market
        ]);
    }
    



    /**
     ---------- PRIVATE ACCOUNT FUNCTIONS ----------
    * getBalances
    * getRecentTrades
    * getOpenOrders
    * getAllOrders (false)
    * trade (false)
    * marketSell (false)
    * marketBuy (false)
    * limitSell
    * limitBuy
    * depositAddress
     **/

     /**
     * getBalances()
     * @return array of currency balances for this account
     **/
     public function getBalances() {
        return $this->accountRequest('getbalances');
    }
    
    /**
     * Get recent trades
     * Not available with this API
    **/
    public function getRecentTrades() {
        return false;
    }

     /**
     * Get all orders that you currently have opened. A specific market can be requested
     *
     * @param string|null $market a string literal for the market (ie. BTC-LTC)
     * @return array
     */
    public function getOpenOrders($market=null) {
        return $this->marketRequest('getopenorders', [
            'market' => $market,
        ]);
    }

    /**
     * getAllOrders()
     * Not available in API
     *
     * @param string $market Currency pair
     * @param int $limit     Limit of orders. Default. 100
     * @return false
     **/
    public function getAllOrders($market = false, $limit = false) {
         return $this->accountRequest('getorderhistory', [
            'market' => $market,
        ]);
    }


    /** trade()
     * Not used by this API
    **/
    public function trade($market, $amount, $type, $rate=false) {

        return false;
        
    }

    /** marketSell()
     * @param $symbol - asset pair to trade
     * @param $amount - amount of trade asset
    */
    public function marketSell($symbol, $amount) {

        return false;

    }
    /** marketBuy()
     * @param $symbol - asset pair to trade
     * @param $amount - amount of trade asset
    */
    public function marketBuy($symbol, $amount) {

        return false;
        
    }
    /**
     *
     * @param string $market a string literal for the market (ex: BTC-LTC)
     * @param string|float $quantity the amount to purchase
     * @param string|float rate the rate at which to place the order.
     *
     * @return array Returns you the order uuid
     */
    public function limitBuy($market, $quantity, $rate) {
        return $this->marketRequest('buylimit', [
            'market' => $market,
            'quantity' => $quantity,
            'rate' => $rate,
        ]);
    }

    /**
     *
     * @param string $market a string literal for the market (ex: BTC-LTC)
     * @param string|float $quantity the amount to sell
     * @param string|float rate the rate at which to place the order.
     *
     * @return array Returns you the order uuid
     *
     */
    public function limitSell($market, $quantity, $rate) {
        return $this->marketRequest('selllimit', [
            'market' => $market,
            'quantity' => $quantity,
            'rate' => $rate,
        ]);
    }


    /**
     * Deposit Address
     * @param string $symbol   Asset symbol
     * @return mixed
     **/
    public function depositAddress($symbol) {

        return $this->accountRequest("getdepositaddress", ['currency' => $symbol]);
        
    }


      /**
     ---------- REQUESTS ----------
     **/


    /**
     * Execute a public API request
     *
     * @param $segment
     * @param array $parameters
     * @return array
     */
    function publicRequest ($segment, array $parameters=[], $version='v1.1') {
        $options = [
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
            ],
        ];

        $publicUrl = $this->getPublicUrl($version);
        $url = $publicUrl . $segment . '?' . http_build_query(array_filter($parameters));
        $feed = file_get_contents($url, false, stream_context_create($options));
        return json_decode($feed, true);
    }


         /**
     * Executes a private API request (market|account),
     * using nonce, key & secret
     *
     * @param $baseUrl
     * @param $segment
     * @param array $parameters
     * @return array
     */
    protected function privateRequest($baseUrl, $segment, $parameters=[]) {
        $parameters = array_merge(array_filter($parameters), [
            'apiKey' => $this->key,
            'nonce' => time()
        ]);

        $uri = $baseUrl . $segment . '?' . http_build_query($parameters);
        $sign = hash_hmac('sha512', $uri, $this->secret);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apisign:$sign",
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/4.0 (compatible; Bittrex PHP-Laravel Client; ' . php_uname('a') . '; PHP/' . phpversion() . ')'
        );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  20);
        curl_setopt($ch,  CURLOPT_TIMEOUT, 300);

        $execResult = curl_exec($ch);
   
        $res = json_decode($execResult, true);
        return $res;
    }
    
        /**
     * Execute a market API request
     *
     * @param $segment
     * @param array $parameters
     * @return array
     */
    public function marketRequest($segment, array $parameters=[]) {
        $baseUrl = $this->market_url;
        return $this->privateRequest($baseUrl, $segment, $parameters);
    }

    /**
     * Execute an account API request
     *
     * @param $segment
     * @param array $parameters
     * @return array
     */
    public function accountRequest($segment, array $parameters=[]) {
        $baseUrl = $this->account_url;
        return $this->privateRequest($baseUrl, $segment, $parameters);
    }

    private function getPublicUrl($version)
    {
        switch($version) {
            case 'v1.1':
                return $this->public_url;
            case 'v2.0':
                return $this->public_url_v2;
            default:
                throw new \Exception("Invalid Bittrex API version: $version");
        }
    }

}
