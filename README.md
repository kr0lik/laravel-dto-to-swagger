# laravel-dto-to-swagger

Auto generation swagger from php types.

## Install
```
$ composer require kr0lik/laravel-dto-to-swagger
```
Setup is extremely simple, just add the service provider to your app.php config.

Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider::class,

You can also publish the config so you can add your own param converters.
```
$ php artisan vendor:publish --provider="Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider"
```
