<?php

namespace TheFramework\App\Database;

/**
 * Base class for custom pivot models.
 */
class Pivot extends Model
{
    /**
     * Indikasi jika model ini merepresentasikan tabel pivot.
     */
    public bool $incrementing = false;

    /**
     * Atribut foreign key milik parent.
     */
    protected string $pivotParentKey;

    /**
     * Atribut foreign key milik yang dituju.
     */
    protected string $pivotRelatedKey;

    /**
     * Set kunci pivot.
     */
    public function setPivotKeys(string $parentKey, string $relatedKey): self
    {
        $this->pivotParentKey = $parentKey;
        $this->pivotRelatedKey = $relatedKey;

        return $this;
    }
}
