<?php

namespace pvsaintpe\grid\widgets;

use kartik\base\Config;
use kartik\grid\GridView as KartikGridView;
use pvsaintpe\freeze\FreezeAsset;
use pvsaintpe\grid\ClickableAsset;
use pvsaintpe\grid\GridViewAsset;
use pvsaintpe\grid\ResizeableAsset;
use pvsaintpe\helpers\Html;
use pvsaintpe\pager\Pager;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\View;

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
    public $clickable = true;

    /**
     * @var array|\Closure
     */
    public $clickableOptions = null;

    /**
     * Auto-select first row in grid
     * @var bool
     */
    public $firstSelected = false;

    /**
     * @var int
     */
    public static $rowCounter = 0;

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
     * @var bool
     */
    public $toggleData = false;

    /**
     * Allow to custom resize columns
     * @var bool
     */
    public $customResizable = false;

    /**
     * @var array
     */
    public $defaultResizeOptions = [
        'saveTo' => 'cookie',
        'url' => '#'
    ];

    /**
     * @var array
     * @example array('container' => '#w0-container', 'urlOptions' => [], etc..)
     */
    public $customResizeOptions = [];

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
    public $bordered = false;

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
     * Включение закрепления колонок
     * @var bool
     */
    public $freezed = true;

    /**
     * Параметры закрепления (перечислить колонки, которые надо закрепить)
     * @var array
     *
     * ```php
     * $this->freezeOptions = [
     *   'freezeLeftColumns' => ['id', 'name', ...]
     *   'freezeRightColumns' => ['id', 'name', ...]
     * ]
     * ```
     */
    public $freezeOptions = [];

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
                    'rowOptions' => function ($model, $key, $index, GridView $grid) {
                        $options = [];
                        if ($grid->clickable) {
                            $options = array_merge($options, ['class' => 'clickable-row']);
                        }
                        if ($grid->firstSelected && $grid::$rowCounter == 0) {
                            if (isset($options['class'])) {
                                $options['class'] .= ' active';
                            } else {
                                $options['class'] = 'active';
                            }
                            $grid::$rowCounter++;
                        }
                        if (is_callable($grid->clickableOptions)) {
                            $func = $grid->clickableOptions;
                            $customOptions = $func($model, $key, $index, $grid);
                        } else {
                            if (!($customOptions = $grid->clickableOptions)) {
                                $customOptions = [];
                            }
                        }
                        if (isset($options['class']) && isset($customOptions['class'])) {
                            $options['class'] = join(' ', [
                                $options['class'],
                                $customOptions['class']
                            ]);
                            unset($customOptions['class']);
                        }
                        return array_merge_recursive($options, $customOptions);
                    }
                ],
                $config
            )
        );
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function run()
    {
        $this->caption = $this->view->title;
        $this->initToggleData();
        $this->initExport();
        if ($this->export !== false && isset($this->exportConfig[self::PDF])) {
            Config::checkDependency(
                'mpdf\Pdf',
                'yii2-mpdf',
                'for PDF export functionality. To include PDF export, follow the install steps below. If you do not ' .
                "need PDF export functionality, do not include 'PDF' as a format in the 'export' property. You can " .
                "otherwise set 'export' to 'false' to disable all export functionality"
            );
        }
        $this->initHeader();
        $this->initBootstrapStyle();
        $this->containerOptions['id'] = $this->options['id'] . '-container';
        Html::addCssClass($this->containerOptions, 'kv-grid-container');
        $this->initPanel();
        $this->initLayout();
        $this->registerAssets();
        if ($this->pjax) {
            $this->beginPjax();
            $this->baseRun();
            $this->endPjax();
        } else {
            $this->baseRun();
        }
        if ($this->customResizable) {
            $view = $this->getView();
            $container_id = '#' . $this->options['id'] . '-container';
            ResizeableAsset::register($view);
            $options = Json::htmlEncode(
                array_merge($this->defaultResizeOptions, $this->customResizeOptions, ['container' => $container_id])
            );
            $view->registerJs("var resizeableOptions = {$options};", View::POS_HEAD);
            $view->registerJs("$('{$container_id}').resizableColumns();");
        }
    }

    public function baseRun()
    {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view = $this->getView();
        GridViewAsset::register($view);
        $view->registerJs("jQuery('#$id').yiiGridView($options);");

        if ($this->freezed && $this->freezeOptions) {
            $freezeOptions = Json::htmlEncode(array_merge($this->freezeOptions, ['container' => "#{$id}-container"]));
            FreezeAsset::register($view);
            $view->registerJs("jQuery('#$id-container').freezeGridView('init', $freezeOptions);");
        }

        if ($this->clickable) {
            ClickableAsset::register($view);
        }

        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $content = preg_replace_callback('/{\\w+}/', function ($matches) {
                $content = $this->renderSection($matches[0]);
                return $content === false ? $matches[0] : $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        echo Html::tag($tag, $content, $options);
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
