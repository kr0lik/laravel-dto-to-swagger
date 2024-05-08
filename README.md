# laravel-dto-to-swagger

Auto generation swagger from Laravel routing and strongly typed data for routes (Use DTO for request and response data).

*You can use laravel-dto-to-swagger togather with spatie/laravel-data packege and swagger will be fully automated and very simple.

## Install
```
$ composer require kr0lik/laravel-dto-to-swagger
```
Setup is extremely simple, just add the service provider to your app.php config.

Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider::class,

You also need publish the config:
```
$ php artisan vendor:publish --provider="Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider"
```

## Use
1. Update the config/swagger.php file according to your needs.
2. Check ot fix your Controller/Action to string types and DTO
3. Run the following command to automatically generate the Swagger documentation:
```
$ php artisan swagger:generate
```
This command will generate a swagger.yaml file with the Swagger documentation.

## Example

See example folder
