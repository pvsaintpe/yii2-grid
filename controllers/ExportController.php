<?php

namespace pvsaintpe\grid\controllers;

use kartik\grid\controllers\ExportController as DefaultController;
use Yii;
use yii\helpers\HtmlPurifier;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;
use pvsaintpe\grid\widgets\GridView;
use kartik\mpdf\Pdf;

class ExportController extends DefaultController
{
    /**
     * Download the exported file
     *
     * @return mixed
     */
    public function actionDownload()
    {
        $request = Yii::$app->request;
        $type = $request->post('export_filetype', 'html');
        $name = $request->post('export_filename', Yii::t('kvgrid', 'export'));

        $content = $request->post('export_content', Yii::t('kvgrid', 'No data found'));
        static::replaceDelimiters($content);

        $mime = $request->post('export_mime', 'text/plain');
        $encoding = $request->post('export_encoding', 'utf-8');
        $bom = $request->post('export_bom', true);
        $config = $request->post('export_config', '{}');
        if ($type == GridView::PDF) {
            $config = Json::decode($config);
            $this->generatePDF($content, "{$name}.pdf", $config);
            /** @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }  elseif ($type == GridView::HTML) {
            $content = HtmlPurifier::process($content);
        } elseif (($type == GridView::CSV || $type == GridView::TEXT) && $encoding == 'utf-8' && $bom) {
            $content = chr(239) . chr(187) . chr(191) . $content; // add BOM
        }
        $this->setHttpHeaders($type, $name, $mime, $encoding);
        return $content;
    }

    public static function replaceDelimiters(&$htmlString)
    {
        preg_match_all('/([0-9]+\.[0-9]+)/', $htmlString, $doubles);
        $oldDoubles = $doubles[0];
        $newDoubles = array_map(function ($double) {
            return str_replace('.', ',', $double);
        }, $oldDoubles);
        $htmlString = str_replace($oldDoubles, $newDoubles, $htmlString);
    }
}