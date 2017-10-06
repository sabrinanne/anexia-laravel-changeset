<?php

namespace Anexia\Changeset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Changeset extends Model
{
    /** @var string */
    protected $userModelClass = 'App\User';

    /** string */
    const CHANGESET_TYPE_INSERT = 'I';
    /** string */
    const CHANGESET_TYPE_UPDATE = 'U';
    /** string */
    const CHANGESET_TYPE_DELETE = 'D';

    /** string */
    const CHANGESET_TYPE_LONG_INSERT = 'INSERT';
    /** string */
    const CHANGESET_TYPE_LONG_UPDATE = 'UPDATE';
    /** string */
    const CHANGESET_TYPE_LONG_DELETE = 'DELETE';

    protected $table = 'changesets';
    public $timestamps = true;
    protected $fillable = [
        'action_id',
        'changeset_type',
        'display',
        'object_type_id',
        'object_uuid',
        'user_id'
    ];

    protected $casts = [
        'action_id' => 'integer',
        'changeset_type' => 'string',
        'display' => 'string',
        'object_type_id' => 'integer',
        'object_uuid' => 'string',
        'user_id' => 'integer'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        /** @var Model $currentUser */
        $currentUser = Auth::user();
        if ($currentUser instanceof $this->userModelClass) {
            $this->user()->associate($currentUser);
        }
    }

    /**
     * @param string $userModelClass
     */
    public function setUserModelClass($userModelClass = 'App\User')
    {
        $this->userModelClass = $userModelClass;
    }

    /**
     * @return string
     */
    public function getUserModelClass()
    {
        return $this->userModelClass;
    }

    public function changerecords()
    {
        return $this->hasMany(Changerecord::class);
    }

    public function object()
    {
        die('get the object!!');
    }

    public function objectType()
    {
        return $this->belongsTo(ObjectType::class);
    }

    /**
     * 'child' Changeset (triggered the 'parent' Changeset via ChangesetTrackable trait's $trackRelated)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function relatedChangeset()
    {
        return $this->belongsTo(Changeset::class, 'related_changeset_id');
    }

    /**
     * 'parent' Changesets (created after a child got changed)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function relatedChangesets()
    {
        return $this->hasMany(Changeset::class, 'related_changeset_id');
    }

    public function user()
    {
        return $this->belongsTo($this->userModelClass);
    }
}