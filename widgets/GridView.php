<?php

namespace pvsaintpe\grid\widgets;

namespace pvsaintpe\search\widgets;

use kartik\grid\GridView as KartikGridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use Yii;

/**
 * Class GridView
 * @package pvsaintpe\search\widgets
 */
class GridView extends KartikGridView
{
    public static $exportFilename = null;

    public $showPageSummary = true;

    public $showCustomPageSummary = false;
    public $beforeSummary = [];
    public $afterSummary = [];

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

    public static $exportCustomConfig = [
        self::CSV => [
            'config' => [
                'colDelimiter' => ";",
                'mime' => "application/csv",
                'encoding' => 'utf-8',
            ],
            'mime' => "application/csv",
        ],
        self::JSON => [],
        self::EXCEL => []
    ];

    /**
     * @param array $config
     * @return string
     * @throws \Exception
     */
    public static function widget($config = [])
    {
        if (isset($config['exportFilename'])) {
            static::$exportFilename = $config['exportFilename'];
        } else {
            $class = 'Export';
            if (isset($config['filterModel'])) {
                $class = get_class($config['filterModel']);
            }

            static::$exportFilename = $class;
        }

        unset($config['exportFilename']);

        foreach (static::$exportCustomConfig as $format => $settings) {
            static::$exportCustomConfig[$format]['filename'] = static::$exportFilename;
        }

        if (!isset($config['toolbar'])) {
            $config['toolbar'] = [
                [
                    'content' => \yii\helpers\Html::a(
                        '<i class="glyphicon glyphicon-repeat"></i>',
                        ['index'],
                        ['data-pjax' => 0,
                            'class' => 'btn btn-default btn-sm',
                            'title' => Yii::t('info', 'Сбросить')
                        ]),
                ],
                '{export}',
            ];
        }

        if (!isset($config['toggleDataOptions'])) {
            $config['toggleDataOptions'] = [
                'all' => [
                    'icon' => 'resize-full',
                    'class' => 'btn btn-default btn-sm',
                ],
                'page' => [
                    'icon' => 'resize-small',
                    'class' => 'btn btn-default btn-sm',
                ],
            ];
        }

        $config = ArrayHelper::merge([
            'exportConfig' => static::$exportCustomConfig,
            'export' => [
                'encoding' => 'utf-8',
            ],
        ], [
            'pjax' => true,
            'bordered' => true,
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'summary' => true,
            'hover' => false,
            'showPageSummary' => true,
            'persistResize' => true,
            'resizableColumns' => false,
            'perfectScrollbar' => false,
            'panel' => [
                'heading' => false,
            ],
            'panelTemplate' => '
                {panelBefore}
                {items}
                {panelAfter}
                {panelFooter}
            ',
            'export' => [
                'target' => static::TARGET_SELF,
            ],
            'panelBeforeTemplate' => '
            <div class="pull-left"></div>
            <div class="pull-right">
                <div class="btn-toolbar kv-grid-toolbar" role="toolbar">{toolbar}</div>
            </div>
            <div class="clearfix"></div>',
        ], $config);

        if (isset($config['disableColumns']) && sizeof($config['disableColumns']) > 0) {
            foreach ($config['columns'] as $columnId => $column) {
                if (isset($column['attribute'])) {
                    $attribute = $column['attribute'];
                } else {
                    $classPath = explode('\\', str_replace('Column', '', $column['class']));
                    $className = array_pop($classPath);
                    $attribute = Inflector::camel2id($className, '_');
                }

                if (in_array($attribute, $config['disableColumns'])) {
                    unset($config['columns'][$columnId]);
                }
            }
        }

        unset($config['disableColumns']);

        return parent::widget($config);
    }

    /**
     * @return string|void
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->caption = $this->view->title;
        parent::run();
    }
}
