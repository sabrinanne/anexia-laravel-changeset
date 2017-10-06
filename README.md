# Anexia Changeset

A Laravel package used to detect permanent changes to your Eloquent models (written into the database) and log them
into separate database tables.

## Installation and configuration

Install the module via composer, therefore adapt the ``require`` part of your ``composer.json``:
```
"require": {
    "anexia/laravel-changeset": "1.0.0"
}
```

In the projects ``config/app.php`` add the new service provider. This will include the package's migration files to the
project's migration path:
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
to add the packages source code to your ``/vendor`` directory and update the autoloading.

## How it works

The Changeset package comes with three new models:
```
Changeset
    * id
    * action_id (uniquid() for each request/user action; related changesets share the same action_id; not nullable)
    * changeset_type (I = INSERT, U = UPDATE, D = DELETE; not nullable)
    * display (info text that describes the changes within the changeset; not nullable)
    * object_type_id (references to the name of the class that got changed within the changeset; foreign_key to 
        object_type; not nullable)
    * object_uuid (tracked identifier of the object; 'id' property by default; not nullable)
    * related_changeset_id (child changeset that triggered an additional changeset for a parent model; the parent model
        is defined by $trackRelated in the child class; foreign_key to changeset; nullable)
    * user_id (identifier of the user that triggered the change; must implement the ChangesetUserInterface; App\User by
        default; nullable)
    * created_at (timestamp of the changeset entry's creation in the database; auto-managed by Eloquent)
    * updated_at (timestamp of the changeset entry's last update in the database; auto-managed by Eloquent)
```
```
Changerecord
    * id
    * field_name (property that got changed; nullable)
    * changeset_id (One-To-Many relation to a changeset; one changeset can contain multiple changerecords; foreign_key
        to changeset; not nullable)
    * display (info text that describes the change on the field_name; not nullable)
    * is_deletion (boolean that marks a 'DELETE' event; 0 by default)
    * is_related (boolean that marks if the associated changeset has a related_changeset_id defined; 0 by default) 
    * new_value (value of the field_name after the change; nullable)
    * old_value (value of the field_name before the change; nullable)
    * created_at (timestamp of the changerecord entry's creation in the database; auto-managed by Eloquent)
    * updated_at (timestamp of the changerecord entry's last update in the database; auto-managed by Eloquent)
```
```
ObjectType
    * id
    * name (class that is trackable = uses ChangesetTrackable trait; unique; not nullable)
    * created_at (timestamp of the object type entry's creation in the database; auto-managed by Eloquent)
    * updated_at (timestamp of the object type entry's last update in the database; auto-managed by Eloquent)
```

The Changeset package adds hooks to the models 'created', 'updated' and 'deleted' events. So any change (save(),
update(), delete(), forceDelete()) of a trackable model (that uses the ChangesetTrackable trait, like Post.php)
triggers a changeset creation.

Depending on the performed change, multiple changerecords get created and associated to the new changeset:
- If a new model gets saved, all its properties receive a changerecord that gets associated to its 'CREATE' changeset
(since they were all 'changed').
- If an existing model gets updated, all the changed properties (different values than before the change) receive a
changerecord that gets associated to its 'UPDATE' changeset.
- If an existing model gets deleted, no changerecord gets associated to its 'DELETE' changeset. 

Regardless of the performed change's type, further changesets for configured parent relations (via $trackRelated) will
be triggered. Parent relations themselves can define their own $trackRelated associations, so a multi-layer tree of
related changesets might be triggered (see Usage 'Related Changesets').


## Usage

Use The ChangesetTrackable trait in a base model class. All models that are supposed to be changeset-trackable must then
be extended from this base model class.
```
// base model class app/BaseModel.php
<?php

namespace App;

use Anexia\Changeset\Traits\ChangesetTrackable;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use ChangesetTrackable;
    
    // possible other "global" behaviour for all models can be put here
}


----------------------------------
// actual model class app/Post.php

<?php

namespace App;

class Post extends BaseModel
{
    protected $table = 'posts';
    protected $fillable = ['author_id', 'name', 'content'];
    protected $trackBy = 'id';
    protected $trackFields = ['author_id', 'name', 'content'];
    protected $tackRelated = ['author' => 'posts'];
    
    public function author()
    {
        return $this->belongsTo('App\User', 'author_id');
    }
}

----------------------------------
// actual model class app/User.php

<?php

namespace App;

class User extends BaseModel implements ChangesetUserInterface
{
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
    protected $trackBy = 'id';
    protected $trackFields = ['name', 'email'];
    
    /**
     * Implementation of required interface method
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    /**
     * Implementation of required interface method
     *
     * Relation to Anexia\Changeset\Changeset model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function triggeredChangesets()
    {
        return $this->hasMany('Anexia\Changeset\Changeset', 'user_id');
    }
    
    public function posts()
    {
        return $this->hasMany('App\Post', 'author_id');
    }
}
```
The detour over an "intermediate" BaseModel class is necessary to allow the protected fields of the ChangesetTrackable 
trait to be overwritten for each trackable model. That means that $trackBy, $trackFields and $trackRelated can NOT
be altered/redefined in the BaseModel class itself, but in each of its child classes like Post.

### Object Types

The changeset package uses the ObjectType model to manage all classes that use the package's ChangesetTrackable trait.
The application "learns" and remembers trackable class names during its lifecycle. After the first occurrence of a 
tracked change for each class that uses the ChanesetTrackable trait the class name will be stored in a new "ObjectType"
entry.

#### Seeding

To learn all possible Object Types (= names of classes that use the ChangesetTrackabke trait) at once, the package comes
with a ChanesetObjectTypeSeeder. This seeder iterates through all classes within the directories "app" and "vendor" and
and checks whether they (or one of their parent classes) use the ChangesetTrackable trait. If so, the classes name gets
stored as a new Object Type.

To run the Seeder you have two options:
1) You can run it explicitely
```
php artisan db:seed --class="Anexia\\Changeset\\Database\\Seeds\\ChangesetObjectTypeSeeder"
```

2) You can include it into your general database seeder, which is usually /database/seeds/DatabaseSeeder.php
```
<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder {

	public function run()
	{
		Model::unguard();

        // Changeset Seeds (ObjectType)
        $this->call(\Anexia\Changeset\Database\Seeds\ChangesetObjectTypeSeeder::class);
        $this->command->info('Changeset object types seeded!');

        // other seeds
		$this->call('AnotherSeeder');
		$this->command->info('Other data seeded!');
		
		...
    }
}
```
If you then run your common seeding command
```
php artisan db:seed
```
the object type seeder gets processed together with all other defined seeders (in given order).

### Related Changesets

Since Post.php has a relation to another model User.php (via 'author') and configured this relation as 'trackable', 
any change on a post object will not just trigger a changeset for that post, but also for its associated author. So

- If a new model gets saved, a new 'CREATE' changeset with object type App\Post gets created, all its properties
receive a changerecord that gets associated to that changeset. Also a new 'UPDATE' changeset with object type App\User
gets created, with a changerecord for field_name 'posts', is_related = true and the display info that a post object got
newly associated. 

Assuming that a new post with id 1 gets created by admin (= user 1) for user with id 2, the following object types,
changesets and changerecords would be generated:
```
// object types
[
    {
        "id":1,
        "name":"App\Post"
    },
    {
        "id":2,
        "name":"App\User"
    }
]
```
```
// changeset and changerecords for post
{
    "id":1,
    "action_id":"59d5ced79454e",
    "changeset_type":"I",
    "display":"INSERT App\Post 1 at date 2017-10-05 10:02:46 by admin",
    "object_type_id":1,
    "object_uuid":1,
    "related_changeset_id":null,
    "user_id":1,
    "created_at":"2017-10-05 10:02:46",
    "updated_at":"2017-10-05 10:02:46",
    "changerecords":[
        {
            "id":1,
            "field_name":"author_id",
            "changeset_id":1,
            "display":"Set author_id to 1",
            "is_deletion":false,
            "is_related":false,
            "new_value":"1",
            "old_value":null,
            "created_at":"2017-10-05 10:02:46",
            "updated_at":"2017-10-05 10:02:46"
        },
        {
            "id":2,
            "field_name":"name",
            "changeset_id":1,
            "display":"Set name to First Post",
            "is_deletion":false,
            "is_related":false,
            "new_value":"First Post",
            "old_value":null,
            "created_at":"2017-10-05 10:02:46",
            "updated_at":"2017-10-05 10:02:46"
        },
        {
            "id":3,
            "field_name":"content",
            "changeset_id":1,
            "display":"Set content to This is my first post",
            "is_deletion":false,
            "is_related":false,
            "new_value":"This is my first post",
            "old_value":null,
            "created_at":"2017-10-05 10:02:46",
            "updated_at":"2017-10-05 10:02:46"
        }
    ]
}
```
```
// related changeset and changerecord for user (author)
{
    "id":2,
    "action_id":"59d5ced79454e",
    "changeset_type":"U",
    "display":"UPDATE App\User 1 at date 2017-10-05 10:02:46 after INSERT App\Post 1 by admin",
    "object_type_id":2,
    "object_uuid":1,
    "related_changeset_id":1,
    "user_id":1,
    "created_at":"2017-10-05 10:02:46",
    "updated_at":"2017-10-05 10:02:46",
    "changerecords":[
        {
            "id":4,
            "field_name":"posts",
            "changeset_id":2,
            "display":"Changed posts associations to [{"id":1}]",
            "is_deletion":false,
            "is_related":true,
            "new_value":"[{"id":1}]",
            "old_value":null,
            "created_at":"2017-10-05 10:02:46",
            "updated_at":"2017-10-05 10:02:46"
        }
    ]
}
```
- If an existing post gets updated, a new 'UPDATE' changeset with object type App\Post gets created, all the changed
properties receive a changerecord that gets associated to that changeset. Also a new 'UPDATE' changeset with object type 
App\User gets created, with a changerecord for field_name 'posts', is_related = true and the display info that a
certain associated post got changed.
Assuming the existing post with id 1 gets changed by admin (= user 1), the following changesets and changerecords 
would be generated:
```
// changeset and changerecords for post
{
    "id":3,
    "action_id":"59d5f7c928e33",
    "changeset_type":"U",
    "display":"UPDATE App\Post 1 at date 2017-10-05 12:03:10 by admin",
    "object_type_id":1,
    "object_uuid":1,
    "related_changeset_id":null,
    "user_id":1,
    "created_at":"2017-10-05 12:03:10",
    "updated_at":"2017-10-05 12:03:10",
    "changerecords":[
        {
            "id":1,
            "field_name":"author_id",
            "changeset_id":3,
            "display":"Set author_id to 1",
            "is_deletion":false,
            "is_related":false,
            "new_value":"1",
            "old_value":null,
            "created_at":"2017-10-05 12:03:10",
            "updated_at":"2017-10-05 12:03:10"
        }
    ]
}
```
```
// related changeset and changerecord for user (author)
{
    "id":4,
    "action_id":"59d5f7c928e33",
    "changeset_type":"U",
    "display":"UPDATE App\User 1 at date 2017-10-05 12:03:10 after UPDATE App\Post 1 by admin",
    "object_type_id":2,
    "object_uuid":1,
    "related_changeset_id":3,
    "user_id":1,
    "created_at":"2017-10-05 12:03:10",
    "updated_at":"2017-10-05 12:03:10",
    "changerecords":[
        {
            "id":4,
            "field_name":"posts",
            "changeset_id":4,
            "display":"Associated posts still are [{"id":1}]",
            "is_deletion":false,
            "is_related":true,
            "new_value":"[{"id":1}]",
            "old_value":null,
            "created_at":"2017-10-05 12:03:10",
            "updated_at":"2017-10-05 12:03:10"
        }
    ]
}
```
- If an existing post gets deleted, a new 'DELETE' changeset with object type App\Post gets created. Also a new
'UPDATE' changeset with object type App\User gets created, with a changerecord for field_name 'posts',
is_related = true, is_deletion = true and the display info that a certain associated post got deleted.
```
// changeset (with no changerecords) for post
{
    "id":5,
    "action_id":"59d5fd3f34bf9",
    "changeset_type":"D",
    "display":"DELETE App\Post 1 at date 2017-10-05 12:05:51 by admin",
    "object_type_id":1,
    "object_uuid":1,
    "related_changeset_id":null,
    "user_id":1,
    "created_at":"2017-10-05 12:05:51",
    "updated_at":"2017-10-05 12:05:51"
}
```
```
// related changeset and changerecord for user (author)
{
    "id":6,
    "action_id":"59d5fd3f34bf9",
    "changeset_type":"U",
    "display":"UPDATE App\User 1 at date 2017-10-05 12:05:51 after DELETE App\Post 1 by admin",
    "object_type_id":2,
    "object_uuid":1,
    "related_changeset_id":5,
    "user_id":1,
    "created_at":"2017-10-05 12:05:51",
    "updated_at":"2017-10-05 12:05:51",
    "changerecords":[
        {
            "id":5,
            "field_name":"posts",
            "changeset_id":6,
            "display":"Deleted posts associations",
            "is_deletion":true,
            "is_related":true,
            "new_value":"[]",
            "old_value":null,
            "created_at":"2017-10-05 12:03:10",
            "updated_at":"2017-10-05 12:03:10"
        }
    ]
}
``` 

## List of developers

* Alexandra Bruckner <ABruckner@anexia-it.com>, Lead developer

## Project related external resources

* [Laravel 5 documentation](https://laravel.com/docs/5.4/installation)
