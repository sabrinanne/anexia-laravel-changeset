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

    protected $changesetTypesMap = [
        Changeset::CHANGESET_TYPE_INSERT => Changeset::CHANGESET_TYPE_LONG_INSERT,
        Changeset::CHANGESET_TYPE_UPDATE => Changeset::CHANGESET_TYPE_LONG_UPDATE,
        Changeset::CHANGESET_TYPE_DELETE => Changeset::CHANGESET_TYPE_LONG_DELETE
    ];

    protected static function bootChangesetTrackable()
    {
        static::created(function(Model $model) {
            $model->newCreationChangeset($model);
        });

        static::updated(function(Model $model) {
            $model->newUpdateChangeset($model);
        });

        static::deleted(function(Model $model) {
            $model->newDeletionChangeset($model);
        });
    }

    /**
     * @return ChangesetUserInterface|null
     */
    private function getChangesetUser()
    {
        $currentUser = null;

        if (Auth::check()) {
            $authUser = Auth::user();
            if ($authUser instanceof ChangesetUserInterface) {
                $currentUser = $authUser;
            }
        }

        return $currentUser;
    }

    /**
     * Called after the model was successfully created (INSERTED into database)
     *
     * @param Model $model
     */
    public function newCreationChangeset(Model $model)
    {
        $objectType = ObjectType::firstOrCreate(['name' => get_class($model)]);
        $currentUser = $this->getChangesetUser();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : '';
        $actionId = uniqid();
        $changesetType = Changeset::CHANGESET_TYPE_INSERT;
        $attributes = $model->attributes;

        $changeset = new Changeset();
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->date = date('Y-m-d H:i:s');
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;
        $changeset->user()->associate($currentUser);

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . $changeset->date . ' by ' . $userName;
        $changeset->save();

        foreach ($attributes as $fieldName => $newValue) {
            if (in_array($fieldName, $this->trackFields)) {
                $changerecord = new Changerecord();
                $changerecord->display = 'Set ' . $fieldName . ' to ' . $newValue;
                $changerecord->field_name = $fieldName;
                $changerecord->new_value = $newValue;
                $changerecord->changeset()->associate($changeset);
                $changerecord->save();
            }
        }

        if (!empty($model->trackRelated)) {
            // only create one changeset per each object (collect them to avoid duplicates)
            $handledChanges[$objectType->name][$model->id] = $changesetType;
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $currentUser, $handledChanges);
        }
    }

    /**
     * Called after the model was successfully updated (UPDATED in database)
     *
     * @param Model $model
     */
    public function newUpdateChangeset(Model $model)
    {
        $objectType = ObjectType::firstOrNew(['name' => get_class($model)]);
        $currentUser = $this->getChangesetUser();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : '';
        $actionId = uniqid();
        $changesetType = Changeset::CHANGESET_TYPE_UPDATE;
        $attributes = $model->attributes;

        $changeset = new Changeset();
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->date = date('Y-m-d H:i:s');
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;
        $changeset->user()->associate($currentUser);

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . $changeset->date . ' by ' . $userName;
        $changeset->save();

        foreach ($attributes as $fieldName => $newValue) {
            if (in_array($fieldName, $this->trackFields)) {
                $oldValue = $model->original[$fieldName];
                if ($newValue !== $oldValue) {
                    $changerecord = new Changerecord();
                    $changerecord->display = 'Changed ' . $fieldName . ' from ' . $oldValue . ' to ' . $newValue;
                    $changerecord->field_name = $fieldName;
                    $changerecord->new_value = $newValue;
                    $changerecord->old_value = $oldValue;
                    $changerecord->changeset()->associate($changeset);
                    $changerecord->save();
                }
            }
        }

        if (!empty($model->trackRelated)) {
            // only create one changeset per each object (collect them to avoid duplicates)
            $handledChanges[$objectType->name][$model->id] = $changesetType;
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $currentUser, $handledChanges);
        }
    }

    /**
     * Called after the model was successfully deleted (DELETED from database)
     *
     * @param Model $model
     */
    public function newDeletionChangeset(Model $model)
    {
        $objectType = ObjectType::firstOrNew(['name' => get_class($model)]);
        $currentUser = $this->getChangesetUser();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : '';
        $actionId = uniqid();
        $changesetType = Changeset::CHANGESET_TYPE_DELETE;

        $changeset = new Changeset();
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->date = date('Y-m-d H:i:s');
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;
        $changeset->user()->associate($currentUser);

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . $changeset->date . ' by ' . $userName;
        $changeset->save();

        $changerecord = new Changerecord();
        $changerecord->display = 'Deleted ' . $objectType->name  . ' ' . $model->id;
        $changerecord->changeset()->associate($changeset);
        $changerecord->save();

        if (!empty($model->trackRelated)) {
            // only create one changeset per each object (collect them to avoid duplicates)
            $handledChanges[$objectType->name][$model->id] = $changesetType;
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $currentUser, $handledChanges);
        }
    }

    /**
     * @param Model $model
     * @param Changeset $modelChangeset
     * @param string $actionId
     * @param string $changesetType
     * @param Model|null $user
     * @param array $handledChanges
     */
    private function manageRelatedChangesets(Model $model, Changeset $modelChangeset, $actionId,
                                             $changesetType, Model $user = null, &$handledChanges = [])
    {
        foreach ($model->trackRelated as $parentRelation => $inverseRelation) {
            $parentClass = get_class($model->$parentRelation()->getModel());
            $objectType = ObjectType::firstOrNew(['name' => $parentClass]);

            switch (get_class($model->$parentRelation)) {
                case Collection::class:
                    if ($model->$parentRelation->count() > 0) {
                        foreach ($model->$parentRelation as $parent) {
                            if (!isset($handledChanges[$parentClass][$parent->id])) {
                                $this->createRelatedChangeset(
                                    $model,
                                    $modelChangeset,
                                    $actionId,
                                    $changesetType,
                                    $parent,
                                    $objectType,
                                    $inverseRelation,
                                    $user,
                                    $handledChanges
                                );
                            }
                        }
                    }
                    break;
                default:
                    if ($model->$parentRelation instanceof $parentClass
                        && !isset($handledChanges[$parentClass][$model->$parentRelation->id])
                    ) {
                        $this->createRelatedChangeset(
                            $model,
                            $modelChangeset,
                            $actionId,
                            $changesetType,
                            $model->$parentRelation,
                            $objectType,
                            $inverseRelation,
                            $user,
                            $handledChanges
                        );
                    }
                    break;
            }
        }
    }

    /**
     * Recursively add parent Changesets and Changerecords
     *
     * @param Model $childModel
     * @param Changeset $childChangeset
     * @param string|integer $actionId
     * @param string $originalChangesetType
     * @param Model $parentModel
     * @param ObjectType $objectType
     * @param string $relation
     * @param ChangesetUserInterface|null $user
     * @param array|null $handledChanges
     */
    private function createRelatedChangeset(Model $childModel, Changeset $childChangeset, $actionId,
                                            $originalChangesetType, Model $parentModel, ObjectType $objectType,
                                            $relation, ChangesetUserInterface $user = null, &$handledChanges = [])
    {
        $changesetType = Changeset::CHANGESET_TYPE_UPDATE;
        $relatedObjectType = ObjectType::firstOrNew(['name' => get_class($childModel)]);
        $userName = $user instanceof ChangesetUserInterface ? $user->getUserName() : '';

        $changeset = new Changeset();
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->date = date('Y-m-d H:i:s');
        $changeset->is_related = true;
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $parentModel->id;
        $changeset->relatedChangeset()->associate($childChangeset);
        $changeset->user()->associate($user);
        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $parentModel->id
            . ' at date ' . $changeset->date . ' after ' . $this->changesetTypesMap[$originalChangesetType] . ' '
            . $relatedObjectType->name . ' ' . $childModel->id .  ' by ' . $userName;
        $changeset->save();

        $changerecord = new Changerecord();
        $changerecord->field_name = $relation;
        $changerecord->changeset()->associate($changeset);
        $changerecord->related_display = $childChangeset->display;
        $changerecord->relatedObjectType()->associate($childChangeset->objectType);
        $changerecord->related_object_uuid = $childChangeset->object_uuid;

        switch (get_class($parentModel->$relation)) {
            case Collection::class:
                $relValues = [];
                foreach ($parentModel->$relation as $rel) {
                    $relValues[] = ['id' => $rel->id];
                }

                $changerecord->display = 'Changed ' . $relation . ' associations to ' . json_encode($relValues);
                $changerecord->new_value = json_encode($relValues);
                break;

            default:
                if ($originalChangesetType == Changeset::CHANGESET_TYPE_DELETE) {
                    $changerecord->display = 'Deleted ' . $relation . ' association ' . $childModel->id;
                    $changerecord->new_value = null;
                } else {
                    $changerecord->display = 'Changed ' . $relation . ' associations to ' . $childModel->id;
                    $changerecord->new_value = $childModel->id;
                }

                break;
        }
        $changerecord->save();

        // only create one changeset per each object (collect them to avoid duplicates)
        $handledChanges[get_class($parentModel)][$parentModel->id] = $changesetType;

        // recursion starts here
        if (!empty($parentModel->trackRelated)) {
            $this->manageRelatedChangesets($parentModel, $changeset, $actionId, $changesetType, $user, $handledChanges);
        }
    }
}