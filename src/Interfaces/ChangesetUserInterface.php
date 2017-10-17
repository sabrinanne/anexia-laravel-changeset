<?php

namespace Anexia\Changeset\Interfaces;

interface ChangesetUserInterface
{
    /**
     * @return string
     */
    public function getUserName();

    /**
     * Relation to Anexia\Changeset\Changeset model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function triggeredChangesets();
}