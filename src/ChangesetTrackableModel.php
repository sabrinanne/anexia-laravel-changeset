<?php

namespace Anexia\Changeset;

use Illuminate\Database\Eloquent\Model;

class ChangesetTrackableModel extends Model
{
    /** @var array */
    protected $unloggable = [];

    protected static function boot()
    {
        parent::boot();

        static::created(function(ChangesetTrackableModel $model) {
            $model->postCreate();
        });

        static::updated(function(ChangesetTrackableModel $model) {
            $model->postUpdate();
        });

        static::deleted(function(ChangesetTrackableModel $model) {
            $model->postDelete();
        });
    }

    /**
     * Called after the model was successfully created (INSERTED into database)
     */
    public function postCreate()
    {
        var_dump($this);
        die('created');
    }

    /**
     * Called after the model was successfully updated (UPDATED in database)
     */
    public function postUpdate()
    {
        var_dump($this);
        die('updated');
    }

    /**
     * Called after the model was successfully deleted (DELETED from database)
     */
    public function postDelete()
    {
        var_dump($this);
        die('deleted');
    }
}