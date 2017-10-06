<?php

namespace Anexia\Changeset;

use Anexia\Changeset\Traits\ChangesetTrackable;
use Illuminate\Database\Eloquent\Model;

class ObjectType extends ChangesetTrackableModel
{
    protected $table = 'object_types';
    public $timestamps = true;
    protected $fillable = [
        'name'
    ];
    protected $casts = [
        'name' => 'string'
    ];
    protected $trackFields = ['name'];
}