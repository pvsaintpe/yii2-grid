<?php

namespace pvsaintpe\grid\components;

use pvsaintpe\helpers\Html;

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

    protected function initTooltip()
    {
        if ($this->grid && $this->grid->filterModel) {
            if (($hint = $this->grid->filterModel->getAttributeHint($this->attribute))) {
                $this->sortLinkOptions = ['data-toggle' => 'tooltip', 'data-original-title' => $hint];
            }
        }
    }

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

    /**
     * @return string
     */
    public function renderFilterCell()
    {
        return Html::tag('td', $this->renderFilterCellContent(), array_merge(
            $this->filterOptions,
            isset($this->attribute)
                ? ['data-col-seq' => $this->attribute]
                : []
        ));
    }
}
