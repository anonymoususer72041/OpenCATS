<?php
/**
 * CATS
 * Career Portal Library
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
 *
 *
 * @package    CATS
 * @subpackage Library
 * @copyright Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 * @version    $Id: CareerPortal.php 3811 2007-12-05 19:32:16Z andrew $
 */

include_once(LEGACY_ROOT . '/lib/Mailer.php');

class CareerPortalTemplateRepository
{
    private $_requiredTemplateFields;
    private $_templates;


    public function __construct($requiredTemplateFields)
    {
        $this->_requiredTemplateFields = $requiredTemplateFields;
        $this->_templates = null;
    }


    public function getTemplates()
    {
        if ($this->_templates === null)
        {
            $this->_templates = $this->_loadTemplates();
        }

        return $this->_templates;
    }

    public function getTemplateList()
    {
        return array_values($this->getTemplates());
    }

    public function resolveTemplateIdentifier($templateIdentifier)
    {
        $templateIdentifier = trim((string) $templateIdentifier);
        if ($templateIdentifier == '')
        {
            return '';
        }

        $templates = $this->getTemplates();
        if (isset($templates[$templateIdentifier]))
        {
            return $templateIdentifier;
        }

        foreach ($templates as $template)
        {
            if (!strcasecmp($template['careerPortalName'], $templateIdentifier))
            {
                return $template['templateID'];
            }
        }

        return '';
    }

    public function getTemplateByIdentifier($templateIdentifier)
    {
        $templateIdentifier = $this->resolveTemplateIdentifier($templateIdentifier);
        if ($templateIdentifier == '')
        {
            return false;
        }

        $templates = $this->getTemplates();

        return $templates[$templateIdentifier];
    }

    public function getDefaultTemplateIdentifier()
    {
        if ($this->resolveTemplateIdentifier('cats_2.0') != '')
        {
            return 'cats_2.0';
        }

        $templates = $this->getTemplateList();
        if (!empty($templates))
        {
            return $templates[0]['templateID'];
        }

        return '';
    }

    private function _loadTemplates()
    {
        $templates = array();
        $baseDirectory = LEGACY_ROOT . '/career_portal_templates';
        if (!is_dir($baseDirectory))
        {
            return $templates;
        }

        $directories = scandir($baseDirectory);
        if ($directories === false)
        {
            return $templates;
        }

        foreach ($directories as $directoryName)
        {
            if ($directoryName == '.' || $directoryName == '..')
            {
                continue;
            }

            $templateDirectory = $baseDirectory . '/' . $directoryName;
            if (!is_dir($templateDirectory))
            {
                continue;
            }

            $template = $this->_loadTemplate($directoryName, $templateDirectory);
            if ($template === false)
            {
                continue;
            }

            $templates[$directoryName] = $template;
        }

        uasort($templates, array($this, '_compareTemplates'));

        return $templates;
    }

    private function _loadTemplate($templateIdentifier, $templateDirectory)
    {
        $metaFileName = $templateDirectory . '/meta.json';
        if (!is_file($metaFileName) || !is_readable($metaFileName))
        {
            return false;
        }

        $metaContents = file_get_contents($metaFileName);
        if ($metaContents === false)
        {
            return false;
        }

        $meta = json_decode($metaContents, true);
        if (!is_array($meta))
        {
            return false;
        }

        if (
            !isset($meta['career_portal_name']) ||
            !is_string($meta['career_portal_name']) ||
            trim($meta['career_portal_name']) == '' ||
            !isset($meta['sections']) ||
            !is_array($meta['sections'])
        )
        {
            return false;
        }

        $template = array(
            'templateID' => $templateIdentifier,
            'careerPortalName' => trim($meta['career_portal_name'])
        );

        foreach ($meta['sections'] as $sectionName => $fileName)
        {
            if (!is_string($sectionName) || trim($sectionName) == '')
            {
                continue;
            }

            if (!is_string($fileName) || !$this->_isValidTemplateFileName($fileName))
            {
                continue;
            }

            $sectionFileName = $templateDirectory . '/' . $fileName;
            if (!is_file($sectionFileName) || !is_readable($sectionFileName))
            {
                $template[$sectionName] = '';
                continue;
            }

            $sectionContents = file_get_contents($sectionFileName);
            if ($sectionContents === false)
            {
                $sectionContents = '';
            }

            $template[$sectionName] = $sectionContents;
        }

        foreach ($this->_requiredTemplateFields as $fieldName)
        {
            if (!isset($template[$fieldName]))
            {
                $template[$fieldName] = '';
            }
        }

        if (!isset($template['Left']))
        {
            $template['Left'] = '';
        }

        return $template;
    }

    private function _isValidTemplateFileName($fileName)
    {
        if ($fileName == '' || preg_match('/[\\\\\\/]/', $fileName))
        {
            return false;
        }

        if (substr($fileName, -4) != '.tpl')
        {
            return false;
        }

        return true;
    }

    private function _compareTemplates($templateA, $templateB)
    {
        return strcasecmp($templateA['careerPortalName'], $templateB['careerPortalName']);
    }
}

/**
 *	Career Portal Settings Library
 *	@package    CATS
 *	@subpackage Library
 */
class CareerPortalSettings
{
    // FIXME: Make this private and use a getter.
    public $requiredTemplateFields = array(
        'Header',
        'Content - Main',
        'Content - Search Results',
        'Content - Job Details',
        'Content - Candidate Registration',
        'Content - Candidate Profile',
        'Content - Apply for Position',
        'Content - Questionnaire',
        'Content - Thanks for your Submission',
        'Footer',
        'CSS'
    );
    private $_db;
    private $_siteID;
    private $_templateRepository;


    public function __construct($siteID)
    {
        $this->_siteID = $siteID;
        $this->_db = DatabaseConnection::getInstance();
        $this->_templateRepository = new CareerPortalTemplateRepository($this->requiredTemplateFields);
    }


    /**
     * Returns all career portal settings and their current values as an
     * associative array.
     *
     * @return array Associative array of all career portal settings and their
     *               current values.
     */
    public function getAll()
    {
        /* Default values. */
        $settings = array(
            'enabled'               => '0', /* false */
            'allowBrowse'           => '1', /* true */
            'candidateRegistration' => '0', /* false */
            'showDepartment'        => '1', /* true */
            'showCompany'           => '0', /* false */
            'activeBoard'           => $this->_templateRepository->getDefaultTemplateIdentifier(),
            'allowXMLSubmit'        => '1', /* true */
            'useCATSTemplate'       => ''
        );

        /* Get all career portal settings for this site from the database. */
        $sql = sprintf(
            "SELECT
                settings.setting AS setting,
                settings.value AS value,
                settings.site_id AS siteID
            FROM
                settings
            WHERE
                settings.site_id = %s
            AND
                settings.settings_type = %s",
            $this->_siteID,
            SETTINGS_CAREER_PORTAL
        );
        $rs = $this->_db->getAllAssoc($sql);

        // Override default settings with settings from the database.
        foreach ($rs as $rowIndex => $row)
        {
            if (isset($settings[$id=$row['setting']]))
            {
                $settings[$id] = $row['value'];
            }
        }

        $activeBoard = $this->_templateRepository->resolveTemplateIdentifier($settings['activeBoard']);
        if ($activeBoard == '')
        {
            $activeBoard = $this->_templateRepository->getDefaultTemplateIdentifier();
        }

        $settings['activeBoard'] = $activeBoard;

        $template = $this->getTemplate($activeBoard);
        foreach ($template as $setting => $value)
        {
            $settings[$setting] = $value;
        }

        return $settings;
    }

    /**
     * Returns all template data for a filesystem template identifier.
     *
     * @param string Template identifier or display name.
     * @return array Multi-dimensional associative result set array of
     *               template data, or array() if no records were
     *               returned.
     */
    public function getAllFromTemplate($template)
    {
        $templateData = $this->_templateRepository->getTemplateByIdentifier($template);
        if ($templateData === false)
        {
            return array();
        }

        $templateRows = array();
        foreach ($templateData as $setting => $value)
        {
            if ($setting == 'templateID' || $setting == 'careerPortalName')
            {
                continue;
            }

            $templateRows[] = array(
                'setting' => $setting,
                'value' => $value
            );
        }

        return $templateRows;
    }

    /**
     * Returns all filesystem template definitions.
     *
     * @return array Multi-dimensional associative result set array of
     *               template data, or array() if no records were
     *               returned.
     */
    public function getDefaultTemplates()
    {
        return $this->_templateRepository->getTemplateList();
    }

    /**
     * Returns all available filesystem templates.
     *
     * @return array Multi-dimensional associative result set array of
     *               template data, or array() if no records were
     *               returned.
     */
    public function getAllTemplates()
    {
        return $this->getDefaultTemplates();
    }

    /**
     * Returns all template settings and values from a filesystem template.
     *
     * @param string Template name.
     * @return array Multi-dimensional associative result set array of
     *               template data, or array() if no records were
     *               returned.
     */
    public function getTemplate($templateName)
    {
        $templateData = $this->_templateRepository->getTemplateByIdentifier($templateName);
        if ($templateData === false)
        {
            $templateData = $this->_templateRepository->getTemplateByIdentifier(
                $this->_templateRepository->getDefaultTemplateIdentifier()
            );
        }

        $template = array();
        if ($templateData !== false)
        {
            foreach ($templateData as $setting => $value)
            {
                if ($setting == 'templateID' || $setting == 'careerPortalName')
                {
                    continue;
                }

                $template[$setting] = $value;
            }
        }

        foreach ($this->requiredTemplateFields as $index => $value)
        {
            if (!isset($template[$value]))
            {
                $template[$value] = '';
            }
        }

        return $template;
    }

    /**
     * Sets a career portal setting for the current site.
     *
     * @param string Setting name.
     * @param string Setting value.
     * @return void
     */
    public function set($setting, $value)
    {
        /* Delete old setting. */
        $sql = sprintf(
            "DELETE FROM
                settings
            WHERE
                settings.setting = '%s'
            AND
                settings.settings_type = %s
            AND
                settings.site_id = %s",
            SETTINGS_CAREER_PORTAL,
            $this->_db->makeQueryString($setting),
            $this->_siteID
        );
        $this->_db->query($sql);

        /* Add new setting. */
        $sql = sprintf(
            "INSERT INTO settings (
                settings_type,
                setting,
                value,
                site_id
            )
            VALUES (
                %s,
                %s,
                %s,
                %s
            )",
            SETTINGS_CAREER_PORTAL,
            $this->_db->makeQueryString($setting),
            $this->_db->makeQueryString($value),
            $this->_siteID
         );
         $this->_db->query($sql);
    }

    /**
     * Sends an e-mail.
     *
     * @param integer Current user ID.
     * @param string Destination e-mail address.
     * @param string E-mail subject.
     * @param string E-mail body.
     * @return void
     */
    public function sendEmail($userID, $destination, $subject, $body)
    {
        if (empty($destination))
        {
            return;
        }

        /* Send e-mail notification. */
        //FIXME: Make subject configurable.
        $mailer = new Mailer($this->_siteID, $userID);
        $mailerStatus = $mailer->sendToOne(
            array($destination, ''),
            $subject,
            $body,
            true
        );
    }
}

?>
