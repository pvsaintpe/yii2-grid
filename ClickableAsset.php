<?php

namespace pvsaintpe\grid;

use kartik\base\AssetBundle;

/**
 * Class ClickableAsset
 * @package pvsaintpe\grid
 */
class ClickableAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('css', ['clickable']);
        $this->setupAssets('js', ['clickable']);
        parent::init();
    }
}