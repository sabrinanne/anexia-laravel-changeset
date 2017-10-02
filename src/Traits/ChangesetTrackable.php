<?php

namespace Anexia\Changeset\Traits;

use Anexia\Changeset\Changerecord;
use Anexia\Changeset\Changeset;
use Anexia\Changeset\ChangesetUserInterface;
use Anexia\Changeset\ObjectType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ChangesetTrackable
{
    /** @var string */
    protected $trackBy = 'id';
    /** @var array */
    protected $trackFields = [];
    /** @var array */
    protected $trackRelated = [];

    protected static function bootChangesetTrackable()
    {
        static::creating(function(Model $model) {
            $model->newCreateChangeset($model);
        });

        static::updating(function(Model $model) {
            $model->newUpdateChangeset($model);
        });

        static::deleting(function(Model $model) {
            $model->newDeletionChangeset($model);
        });
    }

    /**
     * Called after the model was successfully created (INSERTED into database)
     *
     * @param Model $model
     */
    public function newCreateChangeset(Model $model)
    {
//        var_dump($this);
//        die('created');
    }

    /**
     * Called after the model was successfully updated (UPDATED in database)
     *
     * @param Model $model
     */
    public function newUpdateChangeset(Model $model)
    {
//        $classes = get_declared_classes();
//        var_dump($classes);
//        die();

//        $objectType = ObjectType::firstOrCreate(['name' => get_class($model)]);
//
//        $currentUser = null;
//
//        if (Auth::check()) {
//            $authUser = Auth::user();
//            if ($authUser instanceof ChangesetUserInterface) {
//                $currentUser = $authUser;
//            }
//        }
//        $actionId = uniqid();
//        $changesetType = Changeset::CHANGESET_TYPE_UPDATE;
//        $changerecords = [];
//        $attributes = $this->attributes;
//
//        $changeset = new Changeset();
//        $changeset->action_id = $actionId;
//        $changeset->changeset_type = $changesetType;
//        $changeset->date = date('Y-m-d H:i:s');
//        $changeset->obectType()->associate($objectType);
//        $changeset->object_uuid = $model->id;
//        $changeset->user()->associate($currentUser);
//
//        $changeset->display = $changesetType . ' ' . $objectType . ' ' . $model->id . ' at date ' . $changeset->date . ' by ' . $currentUser->getUserName();
//
//        foreach ($attributes as $key => $newValue) {
//            if (in_array($key, $this->trackFields)) {
//                $oldValue = $this->original[$key];
//                if ($newValue !== $oldValue) {
//                    $changerecord = new Changerecord();
//                    $changerecord->field_name = $key;
//                    $changerecord->new_value = $newValue;
//                    $changerecord->old_value = $oldValue;
//                    $changerecord->changeset()->associate($changeset);
//
//                    $changerecords[] = $changerecord;
//                }
//            }
//        }
//
//        $this->trackRelated = [
//            'unit' => 'exercises',
//            'cubeConfigs' => 'exercise',
//            'statistics' => 'exercise'
//        ];
//        if (!empty($this->trackRelated)) {
//            $this->manageRelatedChangesets($changerecords, $model, $actionId, $changesetType, $currentUser);
//        }
//
//        die();
//
//        foreach ($changerecords as $changerecord) {
//            var_dump($changerecord->toArray());
//        }
//
//        die('updated');
    }

    /**
     * Called after the model was successfully deleted (DELETED from database)
     *
     * @param Model $model
     */
    public function newDeletionChangeset(Model $model)
    {
//        var_dump($this->forceDeleting);
//        var_dump($this->originalData);
//        die('deleted');
    }

    /**
     * @param Changerecord[] $changerecords
     * @param Model $model
     * @param string $actionId
     * @param string $changesetType
     * @param Model|null $user
     */
    private function manageRelatedChangesets(&$changerecords, Model $model, $actionId, $changesetType, Model $user = null)
    {
        foreach ($this->trackRelated as $parentRelation => $inverseRelation) {
            $parentClass = get_class($model->$parentRelation()->getModel());
            $objectType = ObjectType::firstOrCreate(['name' => $parentClass]);

            switch (get_class($model->$parentRelation)) {
                case Collection::class:
                    if ($model->$parentRelation->count() > 0) {
                        foreach ($model->$parentRelation as $parent) {
                            $this->createRelatedChangeset($changerecords, $actionId, $changesetType);
                            $changeset = new Changeset();
                            $changeset->action_id = $actionId;
                            $changeset->changeset_type = $type;
                            $changeset->date = date('Y-m-d H:i:s');
                            $changeset->is_related = true;
                            $changeset->obectType()->associate($objectType);
                            $changeset->object_uuid = $parent->id;
                            $changeset->user()->associate($user);

                            $changerecord = new Changerecord();
                            $changerecord->field_name = $inverseRelation;
                            $changerecord->new_value = json_encode($model->toArray());
                            $changerecord->changeset()->associate($changeset);
                        }
                    }
                    break;
                default:
                    if ($model->$parentRelation instanceof $parentClass) {
                        die('ok');
                    }
                    break;
            }
        }
    }

    /**
     * @param Changerecord[] $changerecords
     * @param string|integer $actionId
     * @param string $changesetType
     * @param string|integer $objectId
     * @param ObjectType $objectType
     * @param ChangesetUserInterface $user
     * @param string $relation
     * @param Model $relatedObject
     */
    private function createRelatedChangeset(&$changerecords, $actionId, $changesetType, $objectId, ObjectType $objectType,
                                            ChangesetUserInterface $user, $relation, Model $relatedObject)
    {
        $changeset = new Changeset();
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->date = date('Y-m-d H:i:s');
        $changeset->is_related = true;
        $changeset->obectType()->associate($objectType);
        $changeset->object_uuid = $objectId;
        $changeset->user()->associate($user);

        $changerecord = new Changerecord();
        $changerecord->field_name = $relation;
        $changerecord->new_value = json_encode($relatedObject->toArray());
        $changerecord->changeset()->associate($changeset);

        $changerecords[] = $changerecord;
    }
}