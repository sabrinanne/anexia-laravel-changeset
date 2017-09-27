<?php

namespace Anexia\Changeset;

use Illuminate\Database\Eloquent\Model;

class Changerecord extends Model
{
    protected $table = 'changerecords';
    public $timestamps = true;
    protected $guarded = [
        'changeset_id',
        'field_name',
        'is_related',
        'new_value',
        'old_value'
    ];

    protected $casts = [
        'object_type_id' => 'integer',
        'changeset_id' => 'integer',
        'field_name' => 'string',
        'object_uuid' => 'boolean',
        'new_value' => 'string',
        'old_value' => 'string',
    ];

    public function changeset()
    {
        return $this->belongsTo('Anexia\Changeset\Changeset');
    }
}