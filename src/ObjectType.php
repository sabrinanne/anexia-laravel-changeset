<?php

namespace Anexia\Changeset;

use Anexia\Changeset\Traits\ChangesetTrackable;
use Illuminate\Database\Eloquent\Model;

class ObjectType extends Model
{
    use ChangesetTrackable;

    protected $table = 'object_types';
    public $timestamps = true;
    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'name' => 'string'
    ];

    public function changersets()
    {
        return $this->hasMany(Changeset::class);
    }
}