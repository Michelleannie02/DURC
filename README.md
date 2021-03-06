# DURC
DURC is reverse CRUD

DURC is an artisan console command that builds Laravel Eloquent models and views by reading from the database assuming the DB follows some rules.

DURC:mine will mine your databases using the structural rules listed below, and generate a configuration file.
DURC:write will use the configuration file to generate basic CRUD components for a Laravel system.

## Installation

Via Composer

```bash
$ composer require careset/durc:dev-master
```

Eventually we will release a stable, non-alpha version. But for now you will have to accept alpha code.

This project only works on Laravel 5.5

[package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) should work..

Publish the configuration of DURC and laravel-handlebars

```bash
php artisan vendor:publish --provider="CareSet\DURC\DURCServiceProvider"
php artisan vendor:publish --tag=laravel-handlebars
```
### Add stuff to your config files

Add the following lines to yourproject/config/app.php
under service providers:

```
        ProAI\Handlebars\HandlebarsServiceProvider::class,
        CareSet\DURC\DURCServiceProvider::class,
```

DURC mustache templates use the .mustache extension. 
Now add the '.mustache' to the 'filext' parameter array in yourproject/config/handlebars.php
This will ensure that you can see the views that are generated.

### Add database parameters
If you are using .env file, fill out the mysql database parameters with a user that has at least read
access to all of the mined tables. You need to copy your .env.example into a new .env file if there is no
.env file in the root of your project

#### Publish DURC Public assets
Since composer doesn't run package scripts, you need to publish DURC's public assets manually, or add this script to 
your root level composer.json file.

```
php artisan vendor:publish --tag=public
```

## Available commands

**Command:**
```bash
$ php artisan DURC:mine --DB=thefirst_db_name --DB=the_second_db --DB=the_third (etc...)
$ php artisan DURC:write
```


If you want to run a fresh install and demo DURC stuff, load the mysql tables from /test_mywind_database/ and then run
```bash
$ php artisan DURC:mine --DB=DURC_northwind_model --DB=DURC_northwind_data --DB=DURC_aaaDurctest --DB=DURC_irs
$ php artisan DURC:write
```

There is a demo web interface which you can see by copying the contents of yourproject/routes/durc.php 
to yourproject/routes/web.php

DURC:mine will generate two files in the /config directory

 yourproject/config/DURC_config.autogen.json
 yourproject/config/DURC_config.edit_me.json

If you change the DURC_config.edit_me.json, it will no longer be overwritten by subsequent DURC:mine runs.

DURC:write will take whatever content exists in DURC_config.edit_me.json and generate the following Laravel Assets from it:

* DURC Controllers under yourproject/app/DURC/Controllers/DURC_*Controller.php (these will always be overwritten by subsequent DURC:write runs)
* Laravel Controllers (that inherit from the corresponding DURC Controllers) under yourproject/app/Http/Controllers/*Controller.php (these are not overwritten by subsequent DURC runs, this is where your code goes)
* DURC Models under yourproject/app/DURC/Models/DURC_*.php
* Laravel Eloquent Models (that inherit from the corresponding DURC models) under yourproject/app/*.php
* Mustache Templates under yourproject/resources/views/DURC/ 
  * Index templates for each table
  * Edit templates for each table
  * A starting menu template that lists all generated tables and demonstrates how to include durc forms using mustache etc
* routes to a route file called yourproject/routes/web.durc.php and is automatically loaded by DURCServiceProvider
* testing routes to a file called yourproject/routes/durc_test.php and is automatically loaded by DURCServiceProvider

We use a webpack requirement for this, but you JS system might be different.

https://datatables.net/examples/styling/bootstrap4.html

# How It Works

"side-by-side" generation means that the initial durc generate command will build a something.autogen.ext and a something.editme.ext and 
if (and only if) the something.editme.ext is edited or changed somehow then it will not be overwritten. 
The autogen version will always be overwritten. 

"inherited" generation means that DURC generates a base class, which a user-editable class extends. DURC will always overwrite the parent class
but will never overwrite modified child classes

using the "squash" option on the command line will tell DURC to overwrite everything, even classes that would otherwise contain user changes.
Use this option with caution.  

# Table Syntax Rules

In order to generate the Laravel Eloquent classes automagically, you have to follow a few rules
in how you setup your database. While not all of these rules have an impact in the current
version of DURC, they are all db syntax that we intended to support. 

* suppose you want to have a table in the database that is ignored by DURC.... simply prefix the table with an underscore. So DURC will not pay attention to a table called.
* Do not use plural names in your tables. DURC is singular only and does not do any transforms between singluar and plural. They are too complicated and do not provide enough benifit.
* Do not use camel case in your tables. Tables must be this_good_data and not thisGoodData. This will be fixed in the future, but it is a current limitation.
* You must have AutoIncrement and PRIMARY key set on the 'id' field of each table. This is what DURC will link things to.
* If you want to link a field to the id of another table, then ensure that you end the field name with YourThing_id
* you can have more than one linking key, so Another_YourThing_id and AFine_YourThing_id will both work as expected
* Many to many relationships are essentially ignored, instead DURC will resolve a two many-to-one relationship to the cnetral table. You can override this in your client code if desired. 
* One of the main features of DURC is the ability to use autocomplete to link fields to one another using the interface. This automatic behavior needs to understand which field in a table will be used inside the autocomplete for other data types to link to. the label for the select element is autogenerated based on preferring the first fields with 'name' or 'label' etc. After that DURC will choose the first text field it can find.  
* However, if there is a field called select_name then the system understands that to be the real right answer and will use that as the select name, irrespective other _name or _label fields
* if the fields beigns with is_ then it is regarded as being a boolean and will be replaced with a checkbox or a radio button...
* If there is a table with a field called [entity]_id that is a primary key, DURC will resolve it to a has-one relationship with the [entity] table
* DURC natively understands markdown fields. if you name a field with an ending of _markdown it will automatically display a markdown editory in the DURC screen
* Naming a field something_code will turn the DURC html screen into a code editor and will use the [CodeMirror](http://codemirror.net/) to create a code editor. So ending a text or varchar column type with with _sql_code will serve to change that field into a code editor using the sql mode. 
* The _code postfix strings are usually by the CodeMirror mode name, but with some changes documented in [CodeSyntaxPostFix.md](./CodeSyntaxPostFix.md)


Laravel Eloquent expects the 

    ALTER TABLE `YourThing`
    ADD `created_at` DATETIME NOT NULL,  
    ADD `updated_at` DATETIME NOT NULL,
    ADD `deleted_at` DATETIME DEFAULT NULL

The 'deleted_at' field has to have the capacity to exist as null, and if you have it there, DURC should enable Eloquents Soft Delete methods, for the table. https://laravel.com/docs/5.5/eloquent#soft-deleting 

Because we build function names from the field names in the table, we have to have some limitations.
You can start a field with a digit '1', or '0' but you cannot start a function that way.. so beware...


# Automatically generated API

Once DURC has generated its classes, it will also automatically generate an API endpoint for each table. 

Visiting 
 /DURC/json/yourtablenamehere/1

Will show the entry of yourtablename with an id of 1. DURC knows how to get the related data to yourtablename and will show all of the data in other tables that
it understands is linked to yourtablename id = 1.

# Using Eloquent Child Classes to steer
The basic idea of DURC is that you generate some child classes that inherent from auto-generated parents.
When you substantially change your database structure, you re-run the generator, but the generator will 
not touch any code that you have put in your child classes under the /app/ directory. 

So if you have made changes to /app/yourthing.php then it will not be overwritten...
but if the underlying yourthing table now has a new field that links to another table.. well that should all 
work as expected because the parent class (which is overwritten each time code is generated) is now smarter.

Its a little bit of a snake eating its tail, but things you put into your child-DURC class files can change how the 
next round of code-generation happens. Specifically if you populate the following UX fields: 

* public static $UX_hidden_col
* public static $UX_view_only_col

Which does what it sounds like. The next time a view is generated it will not show the fields that are hidden and fields with view only will either be disabled or readonly. 

# TROUBLESHOOTING

If you get the error "No application encryption key has been specified"
You have to copy the default .env.example into .env and run 
```bash
$ php artisan key:generate
```


