<?php

namespace pvsaintpe\grid\widgets;

use kartik\grid\GridView as KartikGridView;
use pvsaintpe\grid\ClickableAsset;
use pvsaintpe\pager\Pager;
use yii\helpers\ArrayHelper;

/**
 * Class GridView
 * @package pvsaintpe\grid\widgets
 */
class GridView extends KartikGridView
{
    /**
     * Allow to clickable row
     * @var bool
     */
    public $clickable = false;

    /**
     * @var bool
     */
    public $showPageSummary = true;

    /**
     * @var bool
     */
    public $showCustomPageSummary = false;

    /**
     * @var array
     */
    public $beforeSummary = [];

    /**
     * @var array
     */
    public $afterSummary = [];

    /**
     * @var array
     */
    public $captionOptions = [
        'style' => 'display: none'
    ];

    /**
     * Renders the table body.
     * @return string the rendering result.
     * @throws \yii\base\InvalidConfigException
     */
    public function renderTableBody()
    {
        $models = array_values($this->dataProvider->getModels());

        $keys = $this->dataProvider->getKeys();
        $rows = [];
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($model, $key, $index);

            if ($this->afterRow !== null) {
                $row = call_user_func($this->afterRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows)) {
            $colspan = count($this->columns);

            $content = "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        } else {
            $content =  "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
        }

        if (!$this->showPageSummary && $this->showCustomPageSummary) {
            return $content . $this->renderPageSummary();
        }
        return $content;
    }

    /**
     * Custom renders the table page summary.
     *
     * @return string the rendering result.
     * @throws \yii\base\InvalidConfigException
     */
    public function renderPageSummary()
    {
        $content = parent::renderPageSummary();

        if ($this->showCustomPageSummary) {
            if (!$content) {
                $content = "<tfoot></tfoot>";
            }

            if ($this->beforeSummary) {
                foreach ($this->beforeSummary as &$row) {
                    if (!isset($row['options'])) {
                        $row['options'] = $this->pageSummaryRowOptions;
                    }
                }
            }

            if ($this->afterSummary) {
                foreach ($this->afterSummary as &$row) {
                    if (!isset($row['options'])) {
                        $row['options'] = $this->pageSummaryRowOptions;
                    }
                }
            }

            return strtr(
                $content,
                [
                    '<tfoot>' => "<tfoot>\n" . parent::generateRows($this->beforeSummary),
                    '</tfoot>' => parent::generateRows($this->afterSummary) . "\n</tfoot>",
                ]
            );
        }

        return $content;
    }

    /**
     * @var bool
     */
    public $pjax = true;

    /**
     * @var bool
     */
    public $bordered = true;

    /**
     * @var bool
     */
    public $striped = false;

    /**
     * @var bool
     */
    public $condensed = true;

    /**
     * @var bool
     */
    public $responsive = true;

    /**
     * @var bool
     */
    public $summary = true;

    /**
     * @var bool
     */
    public $hover = true;

    /**
     * @var bool
     */
    public $persistResize = true;

    /**
     * @var bool
     */
    public $resizableColumns = false;

    /**
     * @var bool
     */
    public $perfectScrollbar = false;

    /**
     * @var string
     */
    public $panelTemplate = '{panelBefore} {items} {panelAfter} {panelFooter}';

    public $panelBeforeTemplate = '
        <div class="pull-left"></div>
        <div class="pull-right">
            <div class="btn-toolbar kv-grid-toolbar" role="toolbar">{toolbar}</div>
        </div>
        <div class="clearfix"></div>
    ';

    /**
     * @var array
     */
    public $disableColumns = [];

    /**
     * @param array $config
     * @return string
     * @throws \Exception
     */
    public static function widget($config = [])
    {
        $config = ArrayHelper::merge(['panel' => ['heading' => false]], $config);

        if (isset($config['disableColumns']) && sizeof($config['disableColumns']) > 0) {
            foreach ($config['columns'] as $attribute => $column) {
                if (in_array($attribute, $config['disableColumns'])) {
                    unset($config['columns'][$attribute]);
                    unset($column);
                }
            }
        }

        return parent::widget(
            array_merge(
                [
                    'id' => 'w0',
                    'rowOptions' => ['class' => 'clickable-row']
                ],
                $config
            )
        );
    }

    /**
     * @return string|void
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->caption = $this->view->title;
        parent::run();
        if ($this->clickable) {
            $view = $this->getView();
            ClickableAsset::register($view);
        }
    }

    /**
     * @var bool
     */
    public $export = false;

    /**
     * @return string
     */
    public function renderExport()
    {
        return '';
    }

    /**
     * @var array
     */
    public $pager = [
        'class' => Pager::class,
    ];
}
