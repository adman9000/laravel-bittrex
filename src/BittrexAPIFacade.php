<?php namespace adman9000\bittrex;

use Illuminate\Support\Facades\Facade;

class BittrexAPIFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'bittrex';
	}
}