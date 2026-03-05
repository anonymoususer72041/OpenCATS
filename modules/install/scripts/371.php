<?php
/*
 * CATS
 * Update 371 - export custom career portal templates to file storage
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
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
 */

include_once('lib/FileUtility.php');

function schemaCareerPortalSlugify371($value, $separator)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', $separator, $value);
    $value = trim($value, $separator);

    if ($value === '')
    {
        return 'template';
    }

    return $value;
}

function schemaCareerPortalJSONEscape371($value)
{
    $replace = array(
        '\\' => '\\\\',
        '"' => '\\"',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
        "\f" => '\\f',
        "\b" => '\\b'
    );

    return strtr((string) $value, $replace);
}

function schemaCareerPortalGetDefaultTemplateNames371()
{
    $defaultNames = array();
    $defaultRoot = 'modules/careers/templates/default';

    if (!@is_dir($defaultRoot))
    {
        return $defaultNames;
    }

    $rootHandle = @opendir($defaultRoot);
    if ($rootHandle === false)
    {
        return $defaultNames;
    }

    while (($entry = readdir($rootHandle)) !== false)
    {
        if ($entry === '.' || $entry === '..')
        {
            continue;
        }

        $templateDirectory = $defaultRoot . '/' . $entry;
        if (!@is_dir($templateDirectory))
        {
            continue;
        }

        $metaPath = $templateDirectory . '/meta.json';
        if (!@file_exists($metaPath))
        {
            continue;
        }

        $metaRaw = @file_get_contents($metaPath);
        if ($metaRaw === false)
        {
            continue;
        }

        $metaData = @json_decode($metaRaw, true);
        if (!is_array($metaData) || !isset($metaData['career_portal_name']))
        {
            continue;
        }

        $defaultNames[strtolower(trim((string) $metaData['career_portal_name']))] = true;
    }

    @closedir($rootHandle);

    return $defaultNames;
}

function schemaCareerPortalBuildMetaJSON371($templateName, $sectionToFile)
{
    $metaJSON = "{\n";
    $metaJSON .= '  "career_portal_name": "' . schemaCareerPortalJSONEscape371($templateName) . "\",\n";
    $metaJSON .= "  \"sections\": {\n";

    $sectionIndex = 0;
    $sectionCount = count($sectionToFile);

    foreach ($sectionToFile as $sectionName => $sectionFile)
    {
        ++$sectionIndex;
        $metaJSON .= '    "' . schemaCareerPortalJSONEscape371($sectionName)
            . '": "' . schemaCareerPortalJSONEscape371($sectionFile) . '"';

        if ($sectionIndex < $sectionCount)
        {
            $metaJSON .= ',';
        }

        $metaJSON .= "\n";
    }

    $metaJSON .= "  }\n";
    $metaJSON .= "}\n";

    return $metaJSON;
}

function update_371($db)
{
    $tableExists = $db->getAssoc("SHOW TABLES LIKE 'career_portal_template_site'");
    if (empty($tableExists))
    {
        return;
    }

    $baseExportPath = FileUtility::getUploadPath(0, 'careerportaltemplates');
    if ($baseExportPath === false || !@is_writable($baseExportPath))
    {
        return false;
    }

    $defaultTemplateNames = schemaCareerPortalGetDefaultTemplateNames371();

    $rows = $db->getAllAssoc(
        "SELECT
            career_portal_template_site.career_portal_template_id AS templateRowID,
            career_portal_template_site.site_id AS siteID,
            career_portal_template_site.career_portal_name AS templateName,
            career_portal_template_site.setting AS setting,
            career_portal_template_site.value AS value
        FROM
            career_portal_template_site
        ORDER BY
            career_portal_template_site.career_portal_name ASC,
            career_portal_template_site.site_id ASC,
            career_portal_template_site.career_portal_template_id ASC"
    );

    if (empty($rows))
    {
        return;
    }

    $templates = array();

    foreach ($rows as $row)
    {
        $templateName = trim((string) $row['templateName']);
        $templateNameKey = strtolower($templateName);

        if ($templateName === '' || isset($defaultTemplateNames[$templateNameKey]))
        {
            continue;
        }

        $templateKey = (int) $row['siteID'] . '||' . $templateName;
        if (!isset($templates[$templateKey]))
        {
            $templates[$templateKey] = array(
                'templateID' => (int) $row['templateRowID'],
                'templateName' => $templateName,
                'sections' => array()
            );
        }

        if ((int) $row['templateRowID'] < $templates[$templateKey]['templateID'])
        {
            $templates[$templateKey]['templateID'] = (int) $row['templateRowID'];
        }

        $templates[$templateKey]['sections'][(string) $row['setting']] = ($row['value'] === null) ? '' : (string) $row['value'];
    }

    if (empty($templates))
    {
        return;
    }

    $usedSlugs = array();

    foreach ($templates as $template)
    {
        $slugBase = schemaCareerPortalSlugify371($template['templateName'], '-');
        $slug = $slugBase;
        $templatePath = $baseExportPath . '/' . $slug;

        if (isset($usedSlugs[$slug]) || @file_exists($templatePath))
        {
            $slug = $slugBase . '-' . $template['templateID'];
            $templatePath = $baseExportPath . '/' . $slug;
        }

        $usedSlugs[$slug] = true;

        if (@file_exists($templatePath))
        {
            continue;
        }

        if (!@mkdir($templatePath, 0777, true))
        {
            continue;
        }

        @chmod($templatePath, 0777);

        ksort($template['sections'], SORT_STRING);

        $sectionToFile = array();
        $usedSectionFiles = array();

        foreach ($template['sections'] as $sectionName => $sectionContent)
        {
            $sectionBase = schemaCareerPortalSlugify371($sectionName, '_');
            $sectionFile = $sectionBase . '.tpl';

            if (isset($usedSectionFiles[$sectionFile]))
            {
                $sectionFile = $sectionBase . '_' . substr(md5($sectionName), 0, 8) . '.tpl';
            }

            $usedSectionFiles[$sectionFile] = true;
            $sectionToFile[$sectionName] = $sectionFile;

            $sectionPath = $templatePath . '/' . $sectionFile;
            if (@file_put_contents($sectionPath, $sectionContent) === false)
            {
                continue 2;
            }
            @chmod($sectionPath, 0666);
        }

        $metaPath = $templatePath . '/meta.json';
        $metaJSON = schemaCareerPortalBuildMetaJSON371($template['templateName'], $sectionToFile);

        if (@file_put_contents($metaPath, $metaJSON) === false)
        {
            continue;
        }
        @chmod($metaPath, 0666);
    }
}

?>
