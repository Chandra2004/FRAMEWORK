<?php

namespace TheFramework\App;

class Relation
{
    public $type;
    public $parent;
    public $related;
    public $foreignKey;
    public $localKey;
    public $select = []; // tambahkan properti untuk kolom yang ingin dipilih

    public function __construct($type, $parent, $related, $foreignKey, $localKey)
    {
        $this->type = $type;
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Tambahkan kolom tertentu yang ingin diambil dari relasi
     */
    public function select(array $columns)
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Ambil hasil relasi berdasarkan jenisnya (hasMany, hasOne, belongsTo)
     */
    public function getResults($parentRow)
    {
        $relatedModel = new $this->related();
        $query = $relatedModel->query();

        // jika user menentukan kolom yang akan diambil
        if (!empty($this->select)) {
            $query->select($this->select);
        }

        switch ($this->type) {
            case 'hasMany':
                return $query
                    ->where($this->foreignKey, '=', $parentRow[$this->localKey])
                    ->get();

            case 'hasOne':
                return $query
                    ->where($this->foreignKey, '=', $parentRow[$this->localKey])
                    ->first();

            case 'belongsTo':
                return $query
                    ->where($this->localKey, '=', $parentRow[$this->foreignKey])
                    ->first();
        }

        return null;
    }
}
