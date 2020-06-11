<?php

namespace Anexia\Changeset;

use Anexia\Changeset\Constants\ChangesetStatus;
use Anexia\Changeset\Constants\ChangesetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Changeset extends Model
{
    /** @var string */
    protected $userModelClass = 'App\Models\User';

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
        'display',
        'object_type_id',
        'object_uuid',
        'user_id',
        'status',
        'version'
    ];

    protected $casts = [
        'action_id' => 'integer',
        'changeset_type' => 'string',
        'display' => 'string',
        'object_type_id' => 'integer',
        'object_uuid' => 'string',
        'user_id' => 'integer',
        'status' => 'string',
        'version' => 'integer'
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

    public static function boot()
    {
        parent::boot();

        static::creating(
            function ($model) {
                $lastVersion = Changeset::where('object_type_id', $model->object_type_id)
                    ->where('object_uuid', $model->object_uuid)
                    ->latest('version')->first();

                if ($lastVersion) {
                    $model->version = $lastVersion->version + 1;
                }
            }
        );

        static::updating(
            function ($model) {
                if ($model->isDirty('status')){
                    self::executeChangeset($model);
                }
            }
        );
    }

    /**
     * @param string $userModelClass
     */
    public function setUserModelClass($userModelClass = 'App\Models\User')
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

    private static function executeChangeset($model) {
        $objectType = $model->objectType->name;
        $object = $objectType::find($model->object_uuid);
        $object->setSkipChangesetCreation(true);

        if ($model->changeset_type === ChangesetType::INSERT && $model->status === ChangesetStatus::REJECTED) {
            $object->delete();
        } else if ($model->status === ChangesetStatus::APPROVED)
        {
            if($model->changeset_type === ChangesetType::INSERT)
                // Retrieve original status that user set
                $object->status = $model->changerecords()->where('field_name', 'status')->first()->new_value;
            $object->save();
        }
        else if($model->changeset_type === ChangesetType::UPDATE)
        {
            $attributes = $model->changerecords()->get()->reduce(
                function ($accumulator, $changerecord) {
                    $accumulator[$changerecord->field_name] = $changerecord->new_value;
                    return $accumulator;
                }
            );

            $object->update($attributes);
        }
        else if($model->changeset_type === ChangesetType::DELETE)
        {
            $object->delete();
        }
    }
}
}