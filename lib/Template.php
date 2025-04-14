<?php
/**
 * CATS
 * Template Library
 *
 * The Original Code is "CATS Standard Edition".
 * The Initial Developer of the Original Code is Cognizo Technologies, Inc.
 *
 * @package    CATS
 * @subpackage Library
 * @copyright Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 * @version    $Id: Template.php 3587 2007-11-13 03:55:57Z will $
 */

/**
 * Template Library
 * @package    CATS
 * @subpackage Library
 */
class Template
{
    public $regardingID;
    public $oldStatusDescription;
    public $newStatusDescription;
    public $pipelineData;
    public $jobOrderStatuses;
    public $departmentsString;
    public $wildCardQuickSearch;
    public $EEOReportStatistics;
    public $urlEthnicGraph;
    public $urlVeteranGraph;
    public $urlGenderGraph;
    public $urlDisabilityGraph;
    public $revisionRS;
    public $duplicateCandidateID;
    public $statusChanged;
    public $OSType;
    public $databaseVersion;
    public $installationDirectory;
    public $schemaVersions;
    public $reportParameters;
    public $extraFieldSettingsJobOrdersRS;
    public $extraFieldSettingsCandidatesRS;
    public $extraFieldSettingsCompaniesRS;
    public $extraFieldSettingsContactsRS;
    public $extraFieldTypes;
    public $day;
    public $versionCheck;
    public $versionCheckPref;
    public $activityType;
    public $modePeriod = "all";
    public $modeStatus = "";
    public $departmentString;
    public $placementsJobOrdersRS;
    protected $_templateFile;
    public $oldCandidateID;
    public $newCandidateID;
    public $rsOld = [];
    public $rsNew = [];
    public $theImage;
    public $companyRS;
    protected $_filters = [];

    protected $messageSuccess;

    protected $message;

    protected $username;

    protected $reloginVars;

    protected $siteName;

    protected $siteNameFull;

    protected $dateString;

    protected $dataGrid;

    protected $dataGrid2;

    protected $placedRS;

    protected $upcomingEventsFupHTML;

    protected $upcomingEventsHTML;

    protected $active;

    protected $numActivities;

    protected $quickLinks;

    protected $totalJobOrders;

    protected $errMessage;

    protected $totalCandidates;

    protected $userID;

    protected $totalContacts;

    protected $summaryHTML;

    protected $statisticsData;

    protected $isDemoUser;

    protected $subActive;

    protected $userIsSuperUser;

    protected $superUserActive;

    protected $allowAjax;

    protected $defaultPublic;

    protected $firstDayMonday;

    protected $userEmail;

    protected $calendarEventTypes;

    protected $eventsString;

    protected $view;

    protected $year;

    protected $month;

    protected $showEvent;

    protected $currentDateMDY;

    protected $allowEventReminders;

    protected $dayHourStart;

    protected $dayHourEnd;

    protected $militaryTime;

    protected $currentMonth;

    protected $currentYear;

    protected $currentDay;

    protected $currentHour;

    protected $md5InstanceName;

    protected $arrayKeysString;

    protected $counterFilters;

    protected $data;

    protected $isPopup;

    protected $attachmentsRS;

    protected $extraFieldRS;

    public $EEOSettingsRS;

    public $EEOValues;

    protected $isShortNotes;

    protected $calendarRS;

    protected $assignedTags;

    protected $privledgedUser;

    protected $pipelinesRS;

    protected $lists;

    protected $activityRS;

    protected $listRS;

    protected $savedSearchRS;

    protected $isResumeMode;

    protected $isResultsMode;

    protected $mode;

    protected $pager;

    protected $exportForm;

    protected $departmentsRS;

    protected $jobOrdersRS;

    protected $contactsRSWC;

    protected $contactsRS;

    protected $companyID;

    protected $contactID;

    protected $isFinishedMode;

    protected $onlyScheduleEvent;

    protected $changesMade;

    protected $eventHTML;

    protected $modal;

    protected $errorTitle;

    protected $errorMessage;

    protected $isDemo;

    protected $careerPortalUnlock;

    protected $careerPortalSettings;

    protected $careerPortalSettingsRS;

    protected $careerPortalURL;

    protected $careerPortalTemplateNames;

    protected $careerPortalTemplateCustomNames;

    protected $template;

    protected $submissionJobOrdersRS;

    protected $reportTitle;

    public $sessionCookie;

    protected $candidateID;

    protected $defaultCompanyID;

    protected $RS;

    protected $selectedCompanyID;

    protected $noCompanies;

    protected $jobTypes;

    protected $careerPortalEnabled;

    protected $questionnaires;

    protected $systemAdministration;

    protected $calendarSettingsRS;

    protected $timeZone;

    protected $isDateDMY;

    protected $rs;

    protected $regardingRS;

    protected $activityAdded;

    protected $reportsToRS;

    protected $tagsRS;

    protected $topLog;

    protected $sourceInRS;

    protected $sourcesRS;

    protected $sourcesString;

    protected $emailTemplateDisabled;

    protected $canEmail;

    protected $usersRS;

    protected $isModal;

    protected $isParsingEnabled;

    protected $associatedAttachment;

    protected $associatedTextResume;

    protected $parsingStatus;

    protected $contents;

    protected $associatedAttachmentRS;

    protected $subTemplateContents;

    protected $multipleFilesEnabled;

    protected $uploadPath;

    protected $isPublic;

    protected $questionnaireData;

    protected $questionnaireID;

    protected $pipelineEntriesPerPage;

    protected $jobOrderID;

    protected $pipelineGraph;

    protected $license;

    protected $auth_mode;

    protected $accessLevels;

    protected $defaultAccessLevel;

    protected $categories;

    protected $privledged;

    protected $loginAttempts;

    protected $jobOrderFilters;

    protected $pageStart;

    protected $pageEnd;

    protected $totalResults;

    protected $templateName;

    protected $wildCardString;

    protected $defaultCompanyRS;

    protected $extraFieldsForJobOrders;

    public $EEOEnabled;

    protected $extraFieldsForCandidates;

    protected $isJobOrdersMode;

    protected $pipelineRS;

    protected $statusRS;

    protected $selectedJobOrderID;

    protected $selectedStatusID;

    protected $statusChangeTemplate;

    protected $emailDisabled;

    protected $notificationHTML;

    protected $success;

    protected $recipients;

    protected $emailTemplatesRS;

    protected $dataItemDesc;

    protected $dataItemIDArray;

    protected $savedListsRS;

    protected $dataItemType;

    protected $success_to;

    protected $candidateIDArrayStored;

    protected $candidateIDArray;

    protected $candidateJoborderStatusSendsMessage;

    protected $mailerSettingsRS;

    protected $bulk;

    protected $typeOfImport;

    /**
     * Prints $string with all HTML special characters converted to &codes;.
     *$isModa
     * Ex: 'If x < 2 & x > 0, x = 1.' -> 'If x &lt; 2 &amp; x &gt; 0, x = 1.'.protected $
     *
     * @param string $string
     */
    public function _($string)
    {
        echo(htmlspecialchars($string ?? ''));
    }

    /**
     * Assigns the specified property value to the specified property name
     * for access within the template.
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public function assign($propertyName, $propertyValue)
    {
        if (property_exists($this, $propertyName)) {
            $this->$propertyName = $propertyValue;
        }
    }

    /**
     * Assigns the specified property value to the specified property name,
     * by reference, for access within the template.
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public function assignByReference($propertyName, &$propertyValue)
    {
        if (property_exists($this, $propertyName)) {
            $this->$propertyName = &$propertyValue;
        }
    }

    /**
     * TODO: Document me.
     */
    public function addFilter($code)
    {
        $this->_filters[] = $code;
    }

    /**
     * Evaluates a template file. All assignments (see the Template::assign()
     * and Template::assignByReference() methods) must be made before calling
     * this method. The template filename is relative to index.php.
     *
     * @param string $template
     */
    public function display($template)
    {
        /* File existence checking. */
        $file = realpath('./' . $template);
        if (! $file) {
            echo 'Template error: File \'', $template, '\' not found.', "\n\n";
            return;
        }

        $this->_templateFile = $file;

        /* We don't want any variable name conflicts here. */
        unset($file, $template);

        /* Include the template, with output buffering on, and echo it. */
        ob_start();
        include($this->_templateFile);
        $html = ob_get_clean();

        if (strpos($html, '<!-- NOSPACEFILTER -->') === false && strpos($html, 'textarea') === false) {
            $html = preg_replace('/^\s+/m', '', $html);
        }

        foreach ($this->_filters as $filter) {
            eval($filter);
        }

        echo($html);
    }

    /**
     * Returns access level of logged in user for securedObject
     * Intended to be used in tpl classes to check if user has access to a particular part of the page and if it shall be generated or not.
     *
     * @param string $securedObjectName
     * @return mixed
     */
    protected function getUserAccessLevel($securedObjectName)
    {
        return $_SESSION['CATS']->getAccessLevel($securedObjectName);
    }
}
