<?php
/**
 * CATS
 * Graph Generation Library
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 *
 *
 * The contents of this file are subject to the CATS Public License
 * Version 1.1a (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.catsone.com/.
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "CATS Standard Edition".
 *
 * The Initial Developer of the Original Code is Cognizo Technologies, Inc.
 * Portions created by the Initial Developer are Copyright (C) 2005 - 2007
 * (or from the year in which this file was created to the year 2007) by
 * Cognizo Technologies, Inc. All Rights Reserved.
 *
 *
 * @package    CATS
 * @subpackage Library
 * @copyright Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 * @version    $Id: GraphGenerator.php 3705 2007-11-26 23:34:51Z will $
 */

use Amenadiel\JpGraph\Graph\Graph as JpGraphGraph;
use Amenadiel\JpGraph\Graph\PieGraph as JpGraphPieGraph;
use Amenadiel\JpGraph\Image\AntiSpam as JpGraphAntiSpam;
use Amenadiel\JpGraph\Plot\BarPlot as JpGraphBarPlot;
use Amenadiel\JpGraph\Plot\LinePlot as JpGraphLinePlot;
use Amenadiel\JpGraph\Plot\PiePlot as JpGraphPiePlot;
use Amenadiel\JpGraph\Text\Text as JpGraphText;

define('GRAPH_TREND_LINES', false);

/* Is GD2 installed? */
if (function_exists('ImageCreateFromJpeg'))
{
    include_once(LEGACY_ROOT . '/vendor/autoload.php');

    /* Keep compatibility with legacy color/gradient objects constructed in GraphsUI. */
    include_once(LEGACY_ROOT . '/lib/artichow/Artichow.cfg.php');
    include_once(LEGACY_ROOT . '/lib/artichow/common.php');
    include_once(LEGACY_ROOT . '/lib/artichow/inc/Color.class.php');
    include_once(LEGACY_ROOT . '/lib/artichow/inc/Gradient.class.php');
}

class GraphGeneratorUtility
{
    public static function normalizeValues($values)
    {
        $newValues = array();

        foreach ($values as $value)
        {
            if (is_numeric($value))
            {
                $newValues[] = (float) $value;
            }
            else
            {
                $newValues[] = 0;
            }
        }

        return $newValues;
    }

    public static function colorToJpGraph($color, $defaultColor = 'darkgreen')
    {
        if (is_string($color) && trim($color) !== '')
        {
            return strtolower(trim($color));
        }

        if (is_array($color) && count($color) >= 3)
        {
            return array((int) $color[0], (int) $color[1], (int) $color[2]);
        }

        if (is_object($color))
        {
            if (isset($color->from))
            {
                return self::colorToJpGraph($color->from, $defaultColor);
            }

            if (isset($color->red) && isset($color->green) && isset($color->blue))
            {
                return array((int) $color->red, (int) $color->green, (int) $color->blue);
            }
        }

        return $defaultColor;
    }

    public static function buildBarColors($colorArray, $valueCount, $defaultColors)
    {
        $colors = array();

        for ($i = 0; $i < $valueCount; $i++)
        {
            if (isset($colorArray[$i]))
            {
                $colors[] = self::colorToJpGraph($colorArray[$i], $defaultColors[$i % count($defaultColors)]);
            }
            else
            {
                $colors[] = $defaultColors[$i % count($defaultColors)];
            }
        }

        return $colors;
    }

    public static function maxValue($values, $minimum)
    {
        if (empty($values))
        {
            return $minimum;
        }

        $maxValue = max($values);

        if ($maxValue < $minimum)
        {
            return $minimum;
        }

        return $maxValue;
    }

    public static function applyCommonStyle($graph, $title, $shadowSize)
    {
        $graph->SetMarginColor(array(0xF4, 0xF4, 0xF4));
        $graph->SetColor('white');
        $graph->SetFrame(true, array(187, 187, 187), 1);

        if ($shadowSize > 0)
        {
            $graph->SetShadow(true, $shadowSize, 'gray@0.35');
        }

        $graph->title->Set($title);
        $graph->title->SetFont(FF_FONT1, FS_BOLD, 10);
        $graph->title->SetColor('darkblue');
    }

    public static function strokeGraph($graph, $format)
    {
        $handler = $graph->Stroke(_IMG_HANDLER);

        if (self::isJpegFormat($format))
        {
            header('Content-type: image/jpeg');
            imagejpeg($handler);
        }
        else
        {
            header('Content-type: image/png');
            imagepng($handler);
        }

        imagedestroy($handler);
    }

    private static function isJpegFormat($format)
    {
        if (defined('IMG_JPG') && $format == IMG_JPG)
        {
            return true;
        }

        if (defined('IMG_JPEG') && $format == IMG_JPEG)
        {
            return true;
        }

        return false;
    }
}

/**
 *	Simple Graph Generator
 *	@package    CATS
 *	@subpackage Library
 */
class GraphSimple
{
    private $xLabels;
    private $xValues;
    private $color;
    private $title;

    public function __construct($xLabels, $xValues, $color, $title, $width, $height)
    {
        $this->xLabels = $xLabels;
        $this->xValues = $xValues;
        $this->color = $color;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
    }

    // FIXME: Document me.
    public function draw($format = false)
    {
        /* Make sure we have GD support. */
        if (!function_exists('imagecreatefromjpeg'))
        {
            die();
        }

        if ($format === false)
        {
            $format = IMG_PNG;
        }

        $values = GraphGeneratorUtility::normalizeValues($this->xValues);

        $graph = new JpGraphGraph($this->width, $this->height);
        $graph->SetScale('textlin', 0, GraphGeneratorUtility::maxValue($values, 1));
        $graph->SetMargin(35, 20, 35, 45);

        GraphGeneratorUtility::applyCommonStyle($graph, $this->title, 3);

        $graph->xaxis->SetTickLabels($this->xLabels);
        $graph->xaxis->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->yaxis->SetFont(FF_FONT1, FS_NORMAL, 8);

        $barColor = GraphGeneratorUtility::colorToJpGraph($this->color, 'darkgreen');

        $plot = new JpGraphBarPlot($values);
        $plot->SetWidth(0.6);
        $plot->SetColor($barColor);
        $plot->SetFillGradient($barColor, 'white');

        if (GRAPH_TREND_LINES)
        {
            $plot2 = new JpGraphLinePlot($values);
            $plot2->SetBarCenter();
            $plot2->SetColor('darkblue');
            $plot2->SetWeight(1);
            $plot2->mark->Hide();
            $graph->Add($plot2);
        }

        $graph->Add($plot);

        GraphGeneratorUtility::strokeGraph($graph, $format);
    }
}

/**
 *	Simple Pie Graph Generator
 *	@package    CATS
 *	@subpackage Library
 */
class GraphPie
{
    private $xLabels;
    private $xValues;
    private $title;

    public function __construct($xLabels, $xValues, $title, $width, $height)
    {
        $this->xLabels = $xLabels;
        $this->xValues = $xValues;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
    }

    // FIXME: Document me.
    public function draw($format = false)
    {
        /* Make sure we have GD support. */
        if (!function_exists('imagecreatefromjpeg'))
        {
            die();
        }

        if ($format === false)
        {
            $format = IMG_PNG;
        }

        $values = GraphGeneratorUtility::normalizeValues($this->xValues);
        $labels = $this->xLabels;

        if (array_sum($values) <= 0)
        {
            $values = array(1);
            $labels = array('No Data');
        }

        $graph = new JpGraphPieGraph($this->width, $this->height);
        GraphGeneratorUtility::applyCommonStyle($graph, $this->title, 3);

        $plot = new JpGraphPiePlot($values);
        $plot->SetCenter(0.5, 0.45);
        $plot->SetSize(80);
        $plot->SetSliceColors(array('green', 'orange'));
        $plot->SetLegends($labels);

        $graph->legend->SetPos(0.5, 0.98, 'center', 'bottom');
        $graph->legend->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->legend->SetShadow(false);

        $graph->Add($plot);

        GraphGeneratorUtility::strokeGraph($graph, $format);
    }
}

/**
 *	Comparison Chart Generator
 *	@package    CATS
 *	@subpackage Library
 */
class GraphComparisonChart
{
    private $xLabels;
    private $xValues;
    private $color;
    private $title;
    private $totalValue;


    public function __construct($xLabels, $xValues, $colorArray, $title, $width, $height, $totalValue)
    {
        $this->xLabels = $xLabels;
        $this->xValues = $xValues;
        $this->colorArray = $colorArray;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
        $this->totalValue = $totalValue;
    }

    // FIXME: Document me.
    public function draw($format = false)
    {
        /* Make sure we have GD support. */
        if (!function_exists('imagecreatefromjpeg'))
        {
            die();
        }

        if ($format === false)
        {
            $format = IMG_PNG;
        }

        $values = GraphGeneratorUtility::normalizeValues($this->xValues);
        $maxValue = GraphGeneratorUtility::maxValue($values, 1);

        if (is_numeric($this->totalValue) && $this->totalValue > $maxValue)
        {
            $maxValue = (float) $this->totalValue;
        }

        $graph = new JpGraphGraph($this->width, $this->height);
        $graph->SetScale('textlin', 0, $maxValue);
        $graph->SetMargin(20, 20, 35, 70);

        GraphGeneratorUtility::applyCommonStyle($graph, $this->title, 0);

        $graph->xaxis->SetTickLabels($this->xLabels);
        $graph->xaxis->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->yaxis->Hide();

        $plot = new JpGraphBarPlot($values);
        $plot->SetWidth(0.7);
        $plot->SetFillColor(GraphGeneratorUtility::buildBarColors(
            $this->colorArray,
            count($values),
            array('darkgreen', 'orange', 'darkblue', 'lightgray')
        ));
        $plot->SetColor('white');
        $plot->value->Show();
        $plot->value->HideZero();
        $plot->value->SetFormat('%.0f');
        $plot->value->SetFont(FF_FONT1, FS_NORMAL, 8);

        $graph->Add($plot);

        GraphGeneratorUtility::strokeGraph($graph, $format);
        die();
    }
}


/**
 *	Pipeline Report Graph Generator
 *	@package    CATS
 *	@subpackage Library
 */
class pipelineStatisticsGraph
{
    private $xLabels;
    private $xValues;
    private $color;
    private $totalValue;
    private $legend1;
    private $legend2;
    private $legend3;
    private $view;
    private $noData;


    public function __construct($xLabels, $xValues, $colorArray, $width, $height, $legend1, $legend2, $legend3, $view, $noData)
    {
        $this->xLabels = $xLabels;
        $this->xValues = $xValues;
        $this->colorArray = $colorArray;
        $this->width = $width;
        $this->height = $height;
        $this->legend1 = $legend1;
        $this->legend2 = $legend2;
        $this->legend3 = $legend3;
        $this->view = $view;
        $this->noData = $noData;
    }


    // FIXME: Document me.
    public function draw($format = false)
    {
        /* Make sure we have GD support. */
        if (!function_exists('imagecreatefromjpeg'))
        {
            die();
        }

        if ($format === false)
        {
            $format = IMG_PNG;
        }

        $values = GraphGeneratorUtility::normalizeValues($this->xValues);
        $maxValue = GraphGeneratorUtility::maxValue($values, 10);

        $graph = new JpGraphGraph($this->width, $this->height);
        $graph->SetScale('textlin', 0, $maxValue);
        $graph->SetMargin(25, 105, 15, 30);

        $graph->SetMarginColor(array(0xF4, 0xF4, 0xF4));
        $graph->SetColor('white');
        $graph->SetFrame(true, array(0xD0, 0xD0, 0xD0), 1);

        $graph->xaxis->SetTickLabels($this->xLabels);
        $graph->xaxis->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->xaxis->SetColor(array(0xD0, 0xD0, 0xD0));
        $graph->yaxis->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->yaxis->SetColor(array(0xD0, 0xD0, 0xD0));

        $plot = new JpGraphBarPlot($values);
        $plot->SetWidth(0.8);
        $plot->SetFillColor(GraphGeneratorUtility::buildBarColors(
            $this->colorArray,
            count($values),
            array('blue', 'orange', 'green', 'darkgreen')
        ));
        $plot->SetColor('white');
        $plot->value->Show();
        $plot->value->HideZero();
        $plot->value->SetFormat('%.0f');
        $plot->value->SetFont(FF_FONT1, FS_NORMAL, 8);

        $graph->Add($plot);

        $legendColors = GraphGeneratorUtility::buildBarColors(
            $this->colorArray,
            3,
            array('blue', 'orange', 'green')
        );

        $graph->legend->Add($this->legend1, $legendColors[0], '', 0);
        $graph->legend->Add($this->legend2, $legendColors[1], '', 0);
        $graph->legend->Add($this->legend3, $legendColors[2], '', 0);
        $graph->legend->SetPos(0.98, 0.82, 'right', 'top');
        $graph->legend->SetFont(FF_FONT1, FS_NORMAL, 8);
        $graph->legend->SetFillColor('white');
        $graph->legend->SetFrameWeight(1);
        $graph->legend->SetColor('black', array(0xD0, 0xD0, 0xD0));
        $graph->legend->SetShadow(false);

        if ($this->noData)
        {
            $text = new JpGraphText('No Data');
            $text->SetPos(0.5, 0.45, 'center', 'center');
            $text->SetColor('gray');
            $text->SetFont(FF_FONT1, FS_BOLD, 10);
            $graph->AddText($text);
        }

        GraphGeneratorUtility::strokeGraph($graph, $format);
        die();
    }
}


/**
 *	Job Order Report Graph Generator
 *	@package    CATS
 *	@subpackage Library
 */
class jobOrderReportGraph
{
    private $xLabels;
    private $xValues;
    private $color;
    private $title;
    private $totalValue;


    public function __construct($xLabels, $xValues, $colorArray, $title, $width, $height)
    {
        $this->xLabels = $xLabels;
        $this->xValues = $xValues;
        $this->colorArray = $colorArray;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
    }


    // FIXME: Document me.
    public function draw($format = false)
    {
        /* Make sure we have GD support. */
        if (!function_exists('imagecreatefromjpeg'))
        {
            die();
        }

        if ($format === false)
        {
            $format = IMG_JPEG;
        }

        $values = GraphGeneratorUtility::normalizeValues($this->xValues);

        $graph = new JpGraphGraph($this->width, $this->height);
        $graph->SetScale('textlin', 0, GraphGeneratorUtility::maxValue($values, 1));
        $graph->SetMargin(40, 40, 20, 55);

        GraphGeneratorUtility::applyCommonStyle($graph, $this->title, 6);

        $graph->xaxis->SetTickLabels($this->xLabels);
        $graph->xaxis->SetFont(FF_FONT2, FS_NORMAL, 8);
        $graph->yaxis->SetFont(FF_FONT1, FS_NORMAL, 8);

        $plot = new JpGraphBarPlot($values);
        $plot->SetWidth(0.7);
        $plot->SetFillColor(GraphGeneratorUtility::buildBarColors(
            $this->colorArray,
            count($values),
            array('red', 'darkgreen', 'darkblue', 'orange')
        ));
        $plot->SetColor('white');

        $graph->Add($plot);

        GraphGeneratorUtility::strokeGraph($graph, $format);
        die();
    }
}


/**
 *	Word Verification / CAPTCHA Generator
 *	@package    CATS
 *	@subpackage Library
 */
class WordVerify
{
    private $text;


    public function __construct($text)
    {
        $this->text = $text;
    }

    // FIXME: Document me.
    public function draw()
    {
        $object = new JpGraphAntiSpam();
        $object->Set($this->text);
        $object->Stroke();
    }
}

?>
