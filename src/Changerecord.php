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
        'old_value'
    ];

    protected $casts = [
        'changeset_id' => 'integer',
        'display' => 'string',
        'field_name' => 'string',
        'is_deletion' => 'boolean',
        'is_related' => 'boolean',
        'new_value' => 'string',
        'old_value' => 'string'
    ];

    public function changeset()
    {
        return $this->belongsTo(Changeset::class);
    }
}