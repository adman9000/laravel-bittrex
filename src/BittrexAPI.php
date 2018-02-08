<?php

/** Binance repo
 * Uses the Binance API, standardises data & function calls
**/


namespace App\Repositories;

use adman9000\bittrex\BittrexAPI;

class BittrexExchange {


    function __construct($key=false, $secret=false) {

        $this->api_key = $key;
        $this->api_secret = $secret;

    }
    
    function setAPI($key, $secret) {

         $this->api_key = $key;
        $this->api_secret = $secret;
    }


	/** getAccountStats()
	 * Returns an array of account stats for Binance
	 * btc balance, alts btc value, btc to usd exchange rate, array of altcoins held
	**/
	function getAccountStats() {

		$stats = array();

		//Actual amount of BTC held at this exchange
		$btc_balance = 0;

		//Adding up the BTC value of altcoins
		$alts_btc_value = 0;


        //Get what we need from the API
        $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $bapi->setAPI($this->api_key, $this->api_secret);

		 //Get balances of my coins
        $balances = $bapi->getBalances();

        //Get the BTC-USD rate
        $btc_market = $bapi->getTicker("USDT-BTC");
        $btc_usd = $btc_market['result'][0]['Last'];

        //Get latest markets for everythign on bittrex
        $markets = $bapi->getTickers();


        foreach($balances['result'] as $balance) {

            //include BTC
            if($balance['Currency'] == "BTC") {

                $btc_balance = $balance['Balance'];

            }
            else {


                foreach($markets['result'] as $market) {

                    if($market['MarketName'] == 'BTC-'.$balance['Currency']) {


                        $value = $balance['Balance'] * $market['Last'];

                        $alts_btc_value += $value;

                          //Set the amount of this altcoin held, plus its BTC value if amount is >0
                        if($balance['Balance'] > 0) {
	                        $data[$balance['Currency']]['btc_value']['balance'] = $balance['Balance'] ;
	                        $data[$balance['Currency']]['btc_value'] = $value;
	                        $data[$balance['Currency']]['usd_value'] = $value * $btc_usd;
	                    }

                        break;

                    }
                }
            }
        }

        $stats['btc_balance'] = $btc_balance;
        $stats['alts_btc_value'] = $alts_btc_value;
        $stats['altcoins'] = $data;
        $stats['btc_usd_rate'] = $btc_usd;

        $stats['total_btc_value'] = $stats['btc_balance'] + $stats['alts_btc_value'] ;

        //Get values of everything in USD
        $stats['btc_usd_value'] = $stats['btc_balance'] * $stats['btc_usd_rate'];
        $stats['alts_usd_value'] = $stats['alts_btc_value'] * $stats['btc_usd_rate'];
        $stats['total_usd_value'] = $stats['total_btc_value'] * $stats['btc_usd_rate'];

        return $stats;

	}


	/** getBalances()
	 * @param $inc_zero - include zero balances
	 * @return standardised array of balances
	 **/
	function getBalances($inc_zero=true) {

		//Actual amount of BTC held at this exchange
        $btc_balance = 0;

        //Adding up the BTC value of altcoins
        $alts_btc_value = 0;


        //Get what we need from the API
        $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $bapi->setAPI($this->api_key, $this->api_secret);

         //Get balances of my coins
        $balances = $bapi->getBalances();

        //Get the BTC-USD rate
        $btc_market = $bapi->getTicker("USDT-BTC");
        $btc_usd = $btc_market['result'][0]['Last'];

        //Get latest markets for everythign on bittrex
        $ticker = $bapi->getTickers();

        //The standardised array I'm going to return
        $return = array();

        if(!$balances['result']) return false;
        else {

            foreach($balances['result'] as $wallet) {

                 //include BTC
                if($wallet['Currency'] == "BTC") {

                    $btc_balance +=  $wallet['Balance'];
                    $return['btc']['balance'] = $wallet['Balance'];
                    $return['btc']['available'] = $wallet['Available'];
                    $return['btc']['locked'] = $wallet['Pending'];
                    $return['btc']['usd_value'] = $return['btc']['balance'] * $btc_usd;
                    $return['btc']['gbp_value'] = number_format($return['btc']['usd_value'] / env("USD_GBP_RATE"), 2);

                }
                else {


                    foreach($ticker['result'] as $market) {

                        if($market['MarketName'] == "BTC-".$wallet['Currency']) {

                            $total = $wallet['Balance'];

                            //Calculate the BTC value of this coin and add it to the balance
                            $value = $total * $market['Last'];

                            $alts_btc_value += $value;

                            //Set the amount of this altcoin held, plus its BTC value if amount is >0
                            if($inc_zero || $value > 0.0001) {

                                $asset = array();
                                $asset['code'] = $wallet['Currency'];
                                $asset['balance'] = $total;
                                $asset['available'] = $wallet['Available'];
                                $asset['locked'] = $wallet['Pending'];
                                $asset['btc_value'] = round($value, 8);
                                $asset['usd_value'] = $value * $btc_usd;
                                $asset['gbp_value'] = number_format($asset['usd_value'] / env("USD_GBP_RATE"), 2);
                                $return['assets'][] = $asset;

                            }

                            break;

                        }
                    }
                }
                
            }

        	
        }

        return $return;
	}



    //Return an array of all tradeable assets on the exchange
    function getAssets() {

         $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
         $assets = $bapi->getCurrencies();
         
        $return =array();
      
        foreach($assets['result'] as $result) {
            $row = array();
            $row['code'] = $result['Currency'];
            $row['name'] = $result['CurrencyLong'];
            $return[] = $row;
        }

        return $return;
    }

      //Return an array of all tradeable pairs on the exchange
    function getMarkets() {

        $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $bapi->setAPI($this->api_key, $this->api_secret);
        $markets = $bapi->getTickers();
        $return =array();

        foreach($markets['result'] as $market) {
            $arr = explode("-", $market['MarketName']);
            $trade = $arr[0];
            if($trade == "BTC") {
                $row = array();
                $row['market_code'] = $market['MarketName'];
                $row['base_code'] = $arr[1];
                $row['trade_code'] = $arr[0];
                $return[] = $row;
            }
        }

        return $return;

    }

    /** getTicker()
    Get all the BTC markets available on this exchange with prices
    **/

    function getTicker() {

        //The ticker info to return
        $ticker = array();


         //Get what we need from the API
        $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $bapi->setAPI($this->api_key, $this->api_secret);


        //Get the BTC-USD rate
        $btc_market = $this->getBTCMarket();
        $btc_usd = $btc_market['usd_price'];

        $markets =  $bapi->getTickers();

        //Loop through markets, find any of my coins and save the latest price to DB
        foreach($markets['result'] as $market) {
            $arr = explode("-", $market['MarketName']);
            $base = $arr[0];
            if($base == "BTC") {
                
                $price_info = array("code" => $arr[1], "btc_price"=>$market['Last'], "usd_price" => $market['Last'] * $btc_usd, "gbp_price" => $market['Last'] * $btc_usd / env("USD_GBP_RATE"));
                  
                $ticker[] = $price_info;
   
            }
        }

        return $ticker;
    }


        //get the btc usd market & gbp price as well
    function getBTCMarket() {

        $bapi = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $bapi->setAPI($this->api_key, $this->api_secret);

        $market = $bapi->getTicker("USDT-BTC");
        $market = $market['result'];
        $price_info = array("code" => "BTC",  "usd_price" => $market['Last'] , "gbp_price" => $market['Last'] / env("USD_GBP_RATE"));
                return $price_info;

    }


        /** Bittrex API doesn't allow market buy & sell so use limits & pass market price in **/

    function marketSell($symbol, $quantity, $rate) {

        $api = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $api->setAPI($this->api_key, $this->api_secret);

        return $api->sellLimit($symbol, $quantity, $rate);

    }

    function marketBuy($symbol, $quantity, $rate) {

        $api = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $api->setAPI($this->api_key, $this->api_secret);

        return $api->buyLimit($symbol, $quantity, $rate);

    }


    function getOrders() {

        $api = new BittrexAPI(config("bittrex.auth"), config("bittrex.urls"));
        $api->setAPI($this->api_key, $this->api_secret);

        $orders = $api->getOrderHistory();

         $return = array();

        foreach($orders['result'] as $order) {
            $r = array();

            $coins = explode("-", $order['Exchange']);
            $type = explode("_", $order['OrderType']);

            if($type[sizeof($type)-1] == "BUY") {
                $r['coin_bought'] = $coins[0];
                $r['coin_sold'] = $coins[1];
                $r['amount_bought'] = $order['Quantity'];
                $r['amount_sold'] = $order['Price'];
            }
            else {
                $r['coin_bought'] = $coins[1];
                $r['coin_sold'] = $coins[0];
                $r['amount_bought'] = $order['Price'];
                $r['amount_sold'] = $order['Quantity'];
            }

            $r['exchange_rate'] = $order['PricePerUnit'];
            $r['fees'] = $order['Commission'];
            if($order['Closed']) $r['status'] = "complete"; 
            else $r['status'] = "incomplete";

            $return[] = $r;

        }

       return $return;

    }
}
