<?php

namespace pvsaintpe\grid\components;

/**
 * Class ColumnTrait
 * @package pvsaintpe\grid\components
 */
trait ColumnTrait
{
    use \kartik\grid\ColumnTrait;

    /**
     * @var array
     */
    protected $columnOptions = [
        'clickable' => 1,
    ];

    /**
     * Parses and fetches updated content options for grid visibility and format
     *
     * @param mixed $model the data model being rendered
     * @param mixed $key the key associated with the data model
     * @param integer $index the zero-based index of the data item among the item array returned by
     * [[GridView::dataProvider]].
     *
     * @return array
     */
    protected function fetchContentOptions($model, $key, $index)
    {
        $options = parent::fetchContentOptions($model, $key, $index);

        if (!empty($this->columnOptions)) {
            foreach ($this->columnOptions as $option => $value) {
                $options['data-column-' . $option] = $value;
            }
        }

        return $options;
    }
}
