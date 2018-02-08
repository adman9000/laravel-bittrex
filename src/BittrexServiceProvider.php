<?php namespace adman9000\bittrex;

/**
 * @author  adman9000
 */
use Illuminate\Support\ServiceProvider;

class BittrexServiceProvider extends ServiceProvider {

	public function boot() 
	{
		$this->publishes([
			__DIR__.'/../config/bittrex.php' => config_path('bittrex.php')
		]);
	} // boot

	public function register() 
	{
		$this->mergeConfigFrom(__DIR__.'/../config/bittrex.php', 'bittrex');
		$this->app->bind('bittrex', function() {
			return new BittrexAPI(config('bittrex'));
		});

		

	} // register
}