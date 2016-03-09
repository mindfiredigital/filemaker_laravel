# Installation
## Instal Laravel
Laravel utilizes Composer to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.
First, download the Laravel installer using Composer:

    composer create-project --prefer-dist laravel/laravel blog

You may need to give write access to storage directory(For Detail please go through laravel.com).


## Install FileMakerLaravel Package
Add below line in require section of your composer.json.

    lakinmohapatra/filemaker_laravel": "v0.0.*

Run the following command in terminal to install FileMakerLaravel

    composer update
    
# Config

 Open config/app.php and add the following line to the providers array:

	'filemaker_laravel\Database\FileMakerServiceProvider::class',

In config/database.php change the default connection type to filemaker:

	'default' => 'filemaker',

Add the following to the connections array:

	'filemaker' => [
		'driver'   => 'filemaker',
		'host'     => env('DB_HOST'),
		'database' => env('DB_DATABASE'),
		'username' => env('DB_USERNAME'),
		'password' => env('DB_PASSWORD'),
	],

In your root directory create a new file named .env and add the following while including your database connection details:

	DB_HOST=YourHost
	DB_DATABASE=YourDatabase
	DB_USERNAME=YourUsername
	DB_PASSWORD=YourPassword

Note that if you are using version control you do not want the .env file to be a part of your repository so it is included in .gitignore by default.


