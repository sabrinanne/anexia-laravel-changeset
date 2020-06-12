<?php

namespace Anexia\Changeset\Traits;

use Anexia\Changeset\Changerecord;
use Anexia\Changeset\Changeset;
use Anexia\Changeset\Constants\ChangesetStatus;
use Anexia\Changeset\Constants\ChangesetType;
use Anexia\Changeset\Constants\ChangesetTypeLong;
use Anexia\Changeset\Constants\ResourceStatus;
use Anexia\Changeset\Interfaces\ChangesetUserInterface;
use Anexia\Changeset\ObjectType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait ChangesetTrackable
{
    /** @var string */
    protected $trackBy = 'id';
    /** @var array */
    protected $trackFields = [];
    /** @var array */
    protected $trackRelated = [];

    protected $changesetTypesMap = [
        ChangesetType::INSERT => ChangesetTypeLong::INSERT,
        ChangesetType::UPDATE => ChangesetTypeLong::UPDATE,
        ChangesetType::DELETE => ChangesetTypeLong::DELETE,
        ChangesetType::ATTACH => ChangesetTypeLong::ATTACH,
        ChangesetType::DETACH => ChangesetTypeLong::DETACH,
    ];


    /**
     * Get the changeset connection name.
     *
     * @return string
     */
    public function getChangesetConnection()
    {
        return config('database.changeset_default');
    }

    protected static function bootChangesetTrackable()
    {
        static::created(function(Model $model) {
            // If it is pending, we should be handling the changeset creation in controller to be able to set correct model status in changeset
            if ($model->status !== ResourceStatus::PENDING) {
                $model->newCreationChangeset($model, ChangesetStatus::APPROVED);
            }
        });

        static::updating(function(Model $model) {
            if (!$model::$skipChangesetCreation)
            {
                $status = $model::$requireApproval ? ChangesetStatus::PENDING : ChangesetStatus::APPROVED;
                $model->newUpdateChangeset($model, $status, $model::$user);
            }
        });

        static::deleting(function(Model $model) {
            if (!$model::$skipChangesetCreation) {
                $status = $model::$requireApproval ? ChangesetStatus::PENDING : ChangesetStatus::APPROVED;
                $model->newDeletionChangeset($model, $status, $model::$user);
            }
        });
    }

    /**
     * Called after the model was successfully created (INSERTED into database)
     *
     * @param Model $model
     * @param string $status
     */
    public function newCreationChangeset(Model $model, string $status, Model $currentUser = null)
    {
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $objectType = $oTModel->firstOrCreate(['name' => get_class($model)]);

        $changeset = new Changeset();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : 'unknown username';
        $actionId = uniqid();
        $changesetType = ChangesetType::INSERT;
        $attributes = $model->attributes;

        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . date('Y-m-d H:i:s') . ' by ' . $userName;
        $changeset->status = $status;
        $changeset->user_id = $currentUser ? $currentUser->id : null;
        $changeset->save();

        foreach ($attributes as $fieldName => $newValue) {
            if (in_array($fieldName, $this->trackFields)) {
                $newValue = !empty($newValue) ? $newValue : 'NULL';

                $changerecord = new Changerecord();
                $changerecord->setConnection($this->getChangesetConnection());
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
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $status, $currentUser, $handledChanges);
        }
    }

    /**
     * Called after the model was successfully updated (UPDATED in database)
     *
     * @param Model $model
     * @param string $model
     */
    public function newUpdateChangeset(Model $model, string $status, Model $currentUser = null)
    {
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $objectType = $oTModel->firstOrCreate(['name' => get_class($model)]);

        $changeset = new Changeset();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : 'unknown username';
        $actionId = uniqid();
        $changesetType = ChangesetType::UPDATE;
        $attributes = $model->attributes;

        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . date('Y-m-d H:i:s') . ' by ' . $userName;
        $changeset->status = $status;
        $changeset->user_id = $currentUser ? $currentUser->id : null;
        $changeset->save();

        foreach ($attributes as $fieldName => $newValue) {
            if (in_array($fieldName, $this->trackFields)) {
                $oldValue = isset($model->original[$fieldName]) && !empty($model->original[$fieldName]) ? $model->original[$fieldName] : 'NULL';
                $newValue = !empty($newValue) ? $newValue : 'NULL';
                if ($newValue !== $oldValue) {
                    $changerecord = new Changerecord();
                    $changerecord->setConnection($this->getChangesetConnection());
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
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $status, $currentUser, $handledChanges);
        }
    }

    /**
     * Called after the model was successfully deleted (DELETED from database)
     *
     * @param Model $model
     * @param string $status
     */
    public function newDeletionChangeset(Model $model, string $status, Model $currentUser = null)
    {
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $objectType = $oTModel->firstOrCreate(['name' => get_class($model)]);

        $changeset = new Changeset();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : 'unknown username';
        $actionId = uniqid();
        $changesetType = ChangesetType::DELETE;

        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($objectType);
        $changeset->object_uuid = $model->id;

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $objectType->name . ' ' . $model->id
            . ' at date ' . date('Y-m-d H:i:s') . ' by ' . $userName;
        $changeset->status = $status;
        $changeset->user_id = $currentUser ? $currentUser->id : null;
        $changeset->save();

        if (!empty($model->trackRelated)) {
            // only create one changeset per each object (collect them to avoid duplicates)
            $handledChanges[$objectType->name][$model->id] = $changesetType;
            $this->manageRelatedChangesets($model, $changeset, $actionId, $changesetType, $status, $currentUser, $handledChanges);
        }
    }

    /**
     * @param Model $model
     * @param Changeset $modelChangeset
     * @param string $actionId
     * @param string $changesetType
     * @param Model|null $user
     * @param array $handledChanges
     * @param string $status
     */
    private function manageRelatedChangesets(Model $model, Changeset $modelChangeset, $actionId, $changesetType, $status,
                                             Model $user = null, &$handledChanges = [])
    {
        foreach ($model->trackRelated as $parentRelation => $inverseRelation) {
            $parentClass = get_class($model->$parentRelation()->getModel());

            if (isset($model->$parentRelation)) {
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
                                        $inverseRelation,
                                        $status,
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
                                $inverseRelation,
                                $status,
                                $user,
                                $handledChanges
                            );
                        }
                        break;
                }
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
     * @param string $status
     */
    private function createRelatedChangeset(Model $childModel, Changeset $childChangeset, $actionId,
                                            $originalChangesetType, Model $parentModel, $relation, $status,
                                            ChangesetUserInterface $user = null, &$handledChanges = [])
    {
        $changesetType = ChangesetType::UPDATE;
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $relatedObjectType = $oTModel->firstOrCreate(['name' => get_class($childModel)]);
        $parentModelType = $oTModel->firstOrCreate(['name' => get_class($parentModel)]);

        $userName = $user instanceof ChangesetUserInterface ? $user->getUserName() : 'unknown username';

        $changeset = new Changeset();
        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($parentModelType);
        $changeset->object_uuid = $parentModel->id;
        $changeset->relatedChangeset()->associate($childChangeset);
        $changeset->user()->associate($user);
        $changeset->display = $this->changesetTypesMap[$changesetType] . ' ' . $parentModelType->name . ' '
            . $parentModel->id . ' at date ' . date('Y-m-d H:i:s') . ' after '
            . $this->changesetTypesMap[$originalChangesetType] . ' ' . $relatedObjectType->name . ' '
            . $childModel->id .  ' by ' . $userName;
        $changeset->status = $status;
        $changeset->save();

        if (isset($parentModel->$relation)) {
            $changerecord = new Changerecord();
            $changerecord->setConnection($this->getChangesetConnection());
            $changerecord->changeset()->associate($changeset);
            $changerecord->field_name = $relation;
            $changerecord->is_related = true;

            switch (get_class($parentModel->$relation)) {
                case Collection::class:
                    $relValues = [];
                    foreach ($parentModel->$relation as $rel) {
                        $relValues[] = ['id' => $rel->id];
                    }

                    if ($originalChangesetType == ChangesetType::INSERT) {
                        $changerecord->display = 'Changed ' . $relation . ' associations to ' . json_encode($relValues);
                    } else if ($originalChangesetType == ChangesetType::DELETE) {
                        $changerecord->display = 'Deleted ' . $relation . ' associations';
                    } else {
                        $changerecord->display = 'Associated ' . $relation . ' still are ' . json_encode($relValues);
                    }
                    $changerecord->new_value = json_encode($relValues);
                    break;

                default:
                    if ($originalChangesetType == ChangesetType::DELETE) {
                        $changerecord->display = 'Deleted ' . $relation . ' association ' . $childModel->id;
                        $changerecord->is_deletion = true;
                        $changerecord->new_value = null;
                    } else if ($originalChangesetType == ChangesetType::INSERT) {
                        $changerecord->display = 'Set ' . $relation . ' association to ' . $childModel->id;
                        $changerecord->new_value = $childModel->id;
                    } else {
                        $changerecord->display = 'Associated ' . $relation . ' still is ' . $childModel->id;
                        $changerecord->new_value = $childModel->id;
                    }

                    break;
            }

            $changerecord->save();
        }

        // only create one changeset per each object (collect them to avoid duplicates)
        $handledChanges[get_class($parentModel)][$parentModel->id] = $changesetType;

        // recursion starts here
        if (!empty($parentModel->trackRelated)) {
            $this->manageRelatedChangesets($parentModel, $changeset, $actionId, $changesetType, $status, $user, $handledChanges);
        }
    }

    public function newAttachChangeset(string $parentClass, int $parentID, string $relation, int $childID, string $status, Model $currentUser = null)
    {
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $parentObjectType = $oTModel->firstOrCreate(['name' => $parentClass]);

        $changeset = new Changeset();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : 'unknown username';
        $actionId = uniqid();
        $changesetType = ChangesetType::ATTACH;
        //$attributes = $model->attributes;

        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($parentObjectType);
        $changeset->object_uuid = $parentID;

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' to ' . $parentObjectType->name . ' ' . $parentID
            . ' at date ' . date('Y-m-d H:i:s') . ' by ' . $userName;
        $changeset->status = $status;
        $changeset->user_id = $currentUser ? $currentUser->id : null;
        $changeset->save();

        $changerecord = new Changerecord();
        $changerecord->setConnection($this->getChangesetConnection());
        $changerecord->display = 'Attach ' . $relation . ' ' . $childID;
        $changerecord->field_name = $relation;
        $changerecord->new_value = $childID;
        $changerecord->changeset()->associate($changeset);
        $changerecord->save();
    }

    public function newDetachChangeset(string $parentClass, int $parentID, string $relation, int $childID, string $status, Model $currentUser = null)
    {
        $oTModel = new ObjectType();
        $oTModel->setConnection($this->getChangesetConnection());
        $parentObjectType = $oTModel->firstOrCreate(['name' => $parentClass]);

        $changeset = new Changeset();
        $userName = $currentUser instanceof ChangesetUserInterface ? $currentUser->getUserName() : 'unknown username';
        $actionId = uniqid();
        $changesetType = ChangesetType::DETACH;
        //$attributes = $model->attributes;

        $changeset->setConnection($this->getChangesetConnection());
        $changeset->action_id = $actionId;
        $changeset->changeset_type = $changesetType;
        $changeset->objectType()->associate($parentObjectType);
        $changeset->object_uuid = $parentID;

        $changeset->display = $this->changesetTypesMap[$changesetType] . ' from ' . $parentObjectType->name . ' ' . $parentID
            . ' at date ' . date('Y-m-d H:i:s') . ' by ' . $userName;
        $changeset->status = $status;
        $changeset->user_id = $currentUser ? $currentUser->id : null;
        $changeset->save();

        $changerecord = new Changerecord();
        $changerecord->setConnection($this->getChangesetConnection());
        $changerecord->display = 'Detach ' . $relation . ' ' . $childID;
        $changerecord->field_name = $relation;
        $changerecord->new_value = $childID;
        $changerecord->changeset()->associate($changeset);
        $changerecord->save();
    }
}