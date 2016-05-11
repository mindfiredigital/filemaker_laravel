# Installation
## Instal Laravel
Laravel utilizes Composer to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.
First, download the Laravel installer using Composer:

    composer create-project --prefer-dist laravel/laravel blog

You may need to give write access to storage directory(For Detail please go through laravel.com).


## Install FileMakerLaravel Package
Add below line in require section of your composer.json.

    opensource-mindfire/filemaker_laravel": "*"

# Ways to load official FileMaker php api.

1. Make filemaker official library as a laravel package and load it via composer.


2. As our package is dependent upon official filemaker api, you need to load it through composer autoload. 
   Steps to do it.

	1. Go to vendor/opensource-mindfire/
	2. Create a directory named "filemakerapi" 
	3. Paste the FileMaker.php file with dependent class files.

Go to composer.json present in your root directory . And add the following lines.

	"autoload": {
	        "classmap": [
	            "vendor/opensource-mindfire/filemakerapi/FileMaker.php"
	        ],
	 }
	 
This will autoload FileMaker.php and make it available throughout our package.

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

# Eloquent Usage
## Retrieving all records
If you are in controller then use Model name(Test). If you are in model then use "self" instead of "Test".

	Test::all();

Return type is same as laravel

## Adding Constraints
Since each Eloquent model serves as a query builder, you may also add constraints to queries, and then use the get method to retrieve the results:

	Test::where('active', 1)
   		->orderBy('name', 'desc')
     	->skip(10)
     	->take(10)
     	->get();

## Retrieving single record
Retrieve a model by its primary key

	Test::find(1);
Retrieve the first model matching the query constraints

	Test::where('active', 1)->first();

## Basic Inserts

To create a new record in the database, simply create a new model instance, set attributes on the model, then call the save method:

	$test = new Test;
	$test->name = 'name';
	$test->save();

## Basic Updates
The save method may also be used to update models that already exist in the database. To update a model, you should retrieve it, set any attributes you wish to update, and then call the save method.

	$test = Test::find(1);
	$test->name = 'New Name';
	$test->save();
	
Updates can also be performed against any number of models that match a given query. 

	Test::where('active', 1)
      ->where('address', 'San Diego')
      ->update(['name' => 'Deb']);
      
## Deleting Models
To delete a model, call the delete method on a model instance:

	$test = Test::find(1);
	$test->delete();
	
## Other Creation Methods
There are two other methods you may use to create models by mass assigning attributes: firstOrCreate and firstOrNew. The firstOrCreate method will attempt to locate a database record using the given column / value pairs. If the model can not be found in the database, a record will be inserted with the given attributes.

The firstOrNew method, like firstOrCreate will attempt to locate a record in the database matching the given attributes. However, if a model is not found, a new model instance will be returned. Note that the model returned by firstOrNew has not yet been persisted to the database. You will need to call save manually to persist it:

	// Retrieve the user by the attributes, or create it if it doesn't exist...
	$user = App\User::firstOrCreate(['name' => 'user name ']);

	// Retrieve the flight by the attributes, or instantiate a new instance...
	$user = App\User::firstOrNew(['name' => 'user name']);

## Execute a filemaker script
You can execute a filemaker script by following command. Please pass script name and parameter to performScript function. 
Then you need to use get() inorder to get the expected result.

	$this->performScript('Web_Contact_Creation_Script', 'test_value')->get('test_field1');


## Access Portals
No extra work needed. This package is smart enough to access all portals attached to the layout automatically. 
Ex: Suppose you have a portal test_portal inside test layout. Suppose test_portal has one field portal_field1 and three rows. Then you will result like below.

	"test_portal::portal_field1" => array:3 [▼
		 	0 => "Test1"
		      	1 => "Test2"
		      	2 => "Test3"
		    ]

## Note

If you are getting exceptions like 
	"Deprecated: Assigning the return value of new by reference is deprecated in   /FileMaker/Implementation/Parser/FMResultSet.php on line 65"
	
Then please replace latest version of PEAR.php with older version of PEAR.php present inside FileMaker/ folder.
Actually this is a bug from official filemaker php api which shows this depricated & strict warnings.

By default, any errors with filemaker package will be logged into your laravel log files.

## Issue Reporting 

If you find any issues with this package, please click on issues section on github navbar and add your issue details.
	https://github.com/opensource-mindfire/filemaker_laravel/issues


