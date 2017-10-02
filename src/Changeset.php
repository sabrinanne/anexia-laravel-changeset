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

    protected $table = 'changesets';
    public $timestamps = true;
    protected $fillable = [
        'action_id',
        'changeset_type',
        'date',
        'display',
        'object_type_id',
        'object_uuid',
        'user_id'
    ];

    protected $casts = [
        'action_id' => 'integer',
        'changeset_type' => 'string',
        'date' => 'datetime',
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
        return $this->hasMany('Anexia\Changeset\Changerecord');
    }

    public function user()
    {
        return $this->belongsTo($this->userModelClass);
    }

    public function objectType()
    {
        return $this->belongsTo('Anexia\Changeset\ObjectType');
    }
}