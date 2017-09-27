# Anexia Changeset

A Laravel package used to monitor permanent changes to your Eloquent models and log them into the database.

## Installation and configuration

Install the module via composer, therefore adapt the ``require`` part of your ``composer.json``:
```
"require": {
    "anexia/laravel-changeset": "1.0.0"
}
```


In the projects ``config/app.php`` add the new service providers:
```
return [
    'providers' => [        
        /*
         * Anexia Changeset Service Providers...
         */
        Anexia\Changeset\Providers\ChangesetServiceProvider::class,
    ]
];
```

Now run
```
composer update [-o]
```
to add the packages source code to your ``/vendor`` directory and its config files to your ``/config`` directory.

## Usage

...


## List of developers

* Alexandra Bruckner <ABruckner@anexia-it.com>, Lead developer

## Project related external resources

* [Laravel 5 documentation](https://laravel.com/docs/5.4/installation)
