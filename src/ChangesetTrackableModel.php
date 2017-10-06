<?php

namespace Anexia\Changeset;

use Anexia\Changeset\Traits\ChangesetTrackable;
use Illuminate\Database\Eloquent\Model;

class ChangesetTrackableModel extends Model
{
    use ChangesetTrackable;

    public function changesets()
    {
        return $this->hasMany(Changeset::class);
    }
}