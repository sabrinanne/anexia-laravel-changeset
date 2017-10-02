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
        'class'
    ];

    protected $casts = [
        'class' => 'string'
    ];

    public function changersets()
    {
        return $this->hasMany('Anexia\Changeset\Changerset');
    }
}