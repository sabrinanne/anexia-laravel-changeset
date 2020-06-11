<?php

namespace Anexia\Changeset\Constants;

class ChangesetType
{
    public const INSERT = 'I';
    public const UPDATE = 'U';
    public const DELETE = 'D';
}

class ChangesetTypeLong
{
    public const INSERT = 'Insert';
    public const UPDATE = 'Update';
    public const DELETE = 'Delete';
}

class ChangesetStatus
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
}

class ResourceStatus
{
    public const ENABLED = 'enabled';
    public const DISABLED = 'disabled';
    public const PENDING = 'pending';
}