<?php

namespace ProAI\Versioning;

use Illuminate\Support\Str;

trait HasRelationships
{
    public $relation = false;

    public function asRelation($joiningTable, $pivotField)
    {
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
            $instance->asRelation($table, Str::snake(class_basename($related)) . '_version')->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
    }
}
