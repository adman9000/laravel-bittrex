# laravel-bittrex
Laravel implementation of the Bittrex crypto exchange trading API

## Install

#### Install via Composer

```
composer require adman9000/laravel-bittrex
```

Utilises autoloading in Laravel 5.5+. For older versions add the following lines to your `config/app.php`

```php
'providers' => [
        ...
        adman9000\bittrex\BittrexServiceProvider::class,
        ...
    ],


 'aliases' => [
        ...
        'Kraken' => adman9000\bittrex\BittrexAPIFacade::class,
    ],
```

## Features

Price tickers, balances, trades
