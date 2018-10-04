<?php

namespace pvsaintpe\grid\widgets;

use yii\helpers\ArrayHelper;
use kartik\detail\DetailView as KartikDetailView;
use yii\helpers\Html;
use yii\base\Model;

/**
 * Class DetailView
 * @package pvsaintpe\search\widgets
 */
class DetailView extends KartikDetailView
{
    public $hideIfEmpty = true;
    public $notSetIfEmpty = true;

    /**
     * @param array $config
     * @return string
     * @throws \Exception
     */
    public static function widget($config = [])
    {
        $config = ArrayHelper::merge([
            'template' => function ($attribute, $index, $widget) {
                if (is_callable($attribute['value'])) {
                    $attribute['value'] = call_user_func($attribute['value'], $widget->model, $index, $widget);
                }

                $value = $widget->formatter->format($attribute['value'], $attribute['format']);

                return strtr("<tr><th>{label}</th><td>{value}</td></tr>", [
                    '{label}' => $attribute['label'],
                    '{value}' => $value,
                ]);
            },
            'condensed' => true,
            'enableEditMode' => false,
            'hover' => true,
            'mode' => static::MODE_VIEW,
            'fadeDelay' => 0,
            'hideIfEmpty' => false,
        ], $config);

        static::enableAttributes($config);
        static::disableAttributes($config);
        return parent::widget($config);
    }

    /**
     * @inheritdoc
     */
    protected function renderDetailView()
    {
        foreach ($this->attributes as $key => $attribute) {
            if (!isset($attribute['attribute'])) {
                continue;
            }
            if ($this->model instanceof Model) {
                if ($hint = $this->model->getAttributeHint($attribute['attribute'])) {
                    $this->attributes[$key]['label'] = Html::tag('span', $attribute['label'], [
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'top',
                        'title' => $hint
                    ]);
                }
            }
        }
        return parent::renderDetailView();
    }

    /**
     * @param $config
     */
    protected static function disableAttributes(&$config)
    {
        if (isset($config['disableAttributes']) && sizeof($config['disableAttributes']) > 0) {
            foreach ($config['attributes'] as $columnId => $gridColumnName) {
                if (isset($gridColumnName['attribute'])
                    && in_array($gridColumnName['attribute'], $config['disableAttributes'])
                ) {
                    unset($config['attributes'][$columnId]);
                    continue;
                }

                if (in_array($gridColumnName, $config['disableAttributes'])) {
                    unset($config['attributes'][$columnId]);
                    continue;
                }
            }
        }
        unset($config['disableAttributes']);
    }

    /**
     * @param $config
     */
    protected static function enableAttributes(&$config)
    {
        if (isset($config['enableAttributes']) && sizeof($config['enableAttributes']) > 0) {
            foreach ($config['attributes'] as $columnId => $gridColumnName) {
                if (is_array($gridColumnName)) {
                    if (isset($gridColumnName['attribute'])
                        && !in_array($gridColumnName['attribute'], $config['enableAttributes'])) {
                        unset($config['attributes'][$columnId]);
                    }
                    continue;
                }
                if (!in_array($gridColumnName, $config['enableAttributes'])) {
                    unset($config['attributes'][$columnId]);
                }
            }
        }
        unset($config['enableAttributes']);
    }
}
