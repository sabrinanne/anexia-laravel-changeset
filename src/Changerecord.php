<?php

namespace Anexia\Changeset;

use Illuminate\Database\Eloquent\Model;

class Changerecord extends Model
{
    protected $table = 'changerecords';
    public $timestamps = true;
    protected $fillable = [
        'changeset_id',
        'display',
        'field_name',
        'is_deletion',
        'is_related',
        'new_value',
        'old_value',
        'related_display',
        'related_object_type_id',
        'related_object_uuid'
    ];

    protected $casts = [
        'changeset_id' => 'integer',
        'display' => 'string',
        'field_name' => 'string',
        'is_deletion' => 'boolean',
        'is_related' => 'boolean',
        'new_value' => 'string',
        'old_value' => 'string',
        'related_display' => 'string',
        'related_object_type_id' => 'integer',
        'related_object_uuid' => 'string'
    ];

    public function changeset()
    {
        return $this->belongsTo(Changeset::class);
    }

    public function relatedObject()
    {
        die('get related object');
    }

    public function relatedObjectType()
    {
        return $this->belongsTo(ObjectType::class);
    }
}