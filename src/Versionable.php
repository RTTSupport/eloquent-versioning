<?php

namespace ProAI\Versioning;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait Versionable
{
    protected $relation = false;

    /** Relationships */
    public function versions() {
        return $this->hasMany($this->getVersionTable(), 'ref_id');
    }

    /** Relationship Helpers */
    public function asRelation($joiningTable, $pivotField) {
        $this->relation = ['table' => $joiningTable, 'field' => $pivotField];

        return $this;
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
                                  $parentKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey(); // Solution ID

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey(); // Product ID

        // $table - "solution_product"

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }


        return $this->newBelongsToMany(
            $instance->asRelation($table, Str::snake(class_basename($related)).'_version')->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
    }

    /**
     * Boot the versionable trait for a model.
     *
     * @return void
     */
    public static function bootVersionable()
    {
        static::addGlobalScope(new VersioningScope);
    }


    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = array(), $connection = null)
    {
        // hide ref_id from model, because ref_id == id
        $attributes = Arr::except((array) $attributes, $this->getVersionKeyName());

        return parent::newFromBuilder($attributes, $connection);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \ProAI\Versioning\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get the names of the attributes that are versioned.
     *
     * @return array
     */
    public function getVersionedAttributeNames()
    {
        return (! empty($this->versioned)) ? $this->versioned : [];
    }

    /**
     * Get the version key name.
     *
     * @return string
     */
    public function getVersionKeyName()
    {
        return 'ref_' . $this->getKeyName();
    }

    /**
     * Get the version table associated with the model.
     *
     * @return string
     */
    public function getVersionTable()
    {
        return $this->getTable() . '_version';
    }

    /**
     * Get the table qualified version key name.
     *
     * @return string
     */
    public function getQualifiedVersionKeyName()
    {
        return $this->getVersionTable().'.'.$this->getVersionKeyName();
    }

    /**
     * Get the name of the "latest version" column.
     *
     * @return string
     */
    public function getLatestVersionColumn()
    {
        return defined('static::LATEST_VERSION') ? static::LATEST_VERSION : 'latest_version';
    }

    /**
     * Get the fully qualified "latest version" column.
     *
     * @return string
     */
    public function getQualifiedLatestVersionColumn()
    {
        if ($this->relation) {
            return $this->relation['table'] . '.' . $this->relation['field'];
        } else {
            return $this->getTable() . '.' . $this->getLatestVersionColumn();
        }
    }

    /**
     * Get the name of the "version" column.
     *
     * @return string
     */
    public function getVersionColumn()
    {
        return defined('static::VERSION') ? static::VERSION : 'version';
    }

    /**
     * Get the fully qualified "version" column.
     *
     * @return string
     */
    public function getQualifiedVersionColumn()
    {
        return $this->getVersionTable().'.'.$this->getVersionColumn();
    }

}
