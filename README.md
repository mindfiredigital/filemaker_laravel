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

# Usage

## Creating a Model

Artisan is the name of the command-line interface included with Laravel. It provides a number of helpful commands for your use while developing your application. It is driven by the powerful Symfony Console component. To view a list of all available Artisan commands, you may use the list command:

	php artisan list
	

Generally we create models in laravel by using the following artisan command.
	php artisan make:model Test
	
Here Test.php model file will be generated inside app/Test.php.  This class extends Laravel's Eloquent Model class but we need it to extend the filemaker_laravel Model class instead.  Delete the following line from the newly created Test.php file:

	use Illuminate\Database\Eloquent\Model;

Then add the following line in its place:

	use filemaker_laravel\Database\Eloquent\Model;
	
In your Model classes you need to specify the layout that should be used while querying  in your FileMaker database.  In order to do this, add the following line inside the Test class:

	protected $layoutName = 'YourTestLayoutName';

By default Laravel will assume the primary key of your table is "id".  If you have a different primary key you will need to add the following inside your class:

	protected $primaryKey = 'YourTestPrimaryKey';





