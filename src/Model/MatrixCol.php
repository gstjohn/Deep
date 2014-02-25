<?php

namespace rsanchez\Deep\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MatrixCol extends Model
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $table = 'matrix_cols';

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $primaryKey = 'col_id';

    public function scopeFieldId(Builder $query, $fieldId)
    {
        $fieldId = is_array($fieldId) ? $fieldId : array($fieldId);

        return $this->whereIn('field_id', $fieldId);
    }
}
