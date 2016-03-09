# Installation
## Instal Laravel
Laravel utilizes Composer to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.
First, download the Laravel installer using Composer:

    composer create-project --prefer-dist laravel/laravel blog

You may need to give write access to storage directory(For Detail please go through laravel.com).


## Instal FileMakerLaravel
Add below line in require section of your composer.json.

    lakinmohapatra/filemaker_laravel": "v0.0.*

Run the following command in terminal to install FileMakerLaravel

    composer update
