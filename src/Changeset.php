<?php

namespace Anexia\Changeset;

use Illuminate\Database\Eloquent\Model;

class Changeset extends Model
{
    protected $table = 'changesets';
    public $timestamps = true;
    protected $guarded = [
        'object_uuid',
        'changeset_type',
        'date',
        'object_type_id',
        'user_id'
    ];

    protected $casts = [
        'object_uuid' => 'string',
        'changeset_type' => 'string',
        'date' => 'datetime',
        'object_type_id' => 'integer',
        'user_id' => 'integer'
    ];

    public function changerecords()
    {
        return $this->hasMany('Anexia\Changeset\Changerecord');
    }

    public function user()
    {
        return $this->belongsTo('Anexia\Changeset\ChangesetUser');
    }

    public function obectType()
    {
        return $this->belongsTo('Anexia\Changeset\ObjectType');
    }
}