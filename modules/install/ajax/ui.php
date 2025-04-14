<?php
/*
 * OPENCATS
 * AJAX Installer Interface
 *
 */

include_once(LEGACY_ROOT . '/config.php');
include_once(LEGACY_ROOT . '/lib/InstallationTests.php');
include_once(LEGACY_ROOT . '/lib/CATSUtility.php');

if (ini_get('safe_mode')) {
    //don't do anything in safe mode
} else {
    /* limit the execution time to 300 secs. */
    set_time_limit(300);
}
@ini_set('memory_limit', '192M');

if (file_exists('modules.cache')) {
    @unlink('modules.cache');
}

if (! isset($_REQUEST['a']) || empty($_REQUEST['a'])) {
    die('Invalid action.');
}

$action = $_REQUEST['a'];

/* Don't allow installation if ./INSTALL_BLOCK exists. */
if (file_exists('INSTALL_BLOCK')) {
    echo '
        <script type="text/javascript">
            setActiveStep(1);
            showTextBlock(\'installLocked\');
        </script>';
    die();
}

switch ($action) {
    case 'startInstall':
        echo '
            <script type="text/javascript">
                setActiveStep(1);
                showTextBlock(\'startInstall\');
                Installpage_append(\'a=installTest\', \'Please wait while your system is tested...\');
            </script>';
        break;

    case 'installTest':
        $result = true;
        $warningsOccurred = false;

        echo '<br />',
        '<span style="font-weight: bold;">Test Results</span>',
        '<table class="test_output">';


        InstallationTests::runInstallerTests();

        if (! $result) {
            if ($warningsOccurred) {
                echo '<script type="text/javascript">showTextBlock(\'testFailedWarning\');</script>';
            } else {
                echo '<script type="text/javascript">showTextBlock(\'testFailed\');</script>';
            }
        } elseif ($warningsOccurred) {
            echo '<script type="text/javascript">showTextBlock(\'testWarning\');</script>';
        } else {
            echo '<script type="text/javascript">showTextBlock(\'testPassed\');</script>';
        }

        echo '</table>';
        break;

    case 'databaseConnectivity':
        /* If $_REQUEST['user'] is set, we have been passed parameters to test
         * the connection.
         */
        if (isset($_REQUEST['user'])) {
            if (isset($_REQUEST['user']) && ! empty($_REQUEST['user'])) {
                CATSUtility::changeConfigSetting('DATABASE_USER', "'" . $_REQUEST['user'] . "'");
            }

            if (isset($_REQUEST['pass'])) {
                CATSUtility::changeConfigSetting('DATABASE_PASS', "'" . $_REQUEST['pass'] . "'");
            }

            if (isset($_REQUEST['host']) && ! empty($_REQUEST['host'])) {
                CATSUtility::changeConfigSetting('DATABASE_HOST', "'" . $_REQUEST['host'] . "'");
            }

            if (isset($_REQUEST['name']) && ! empty($_REQUEST['name'])) {
                CATSUtility::changeConfigSetting('DATABASE_NAME', "'" . $_REQUEST['name'] . "'");
            }

            echo '
                <script type="text/javascript">
                    setActiveStep(2);
                    showTextBlock(\'databaseConnectivity\');
                    document.getElementById(\'testDatabaseConnectivity\').disabled = true;
                    document.getElementById(\'testDatabaseConnectivityIndicator\').style.visibility = \'visible\';
                    Installpage_append(\'a=testDatabaseConnectivity\', \'Please wait while your connection is tested...\');
                </script>';
            die();
        }

        echo '
            <script type="text/javascript">
                setActiveStep(2);
                showTextBlock(\'databaseConnectivity\');
                document.getElementById(\'dbname\').value = \'' . htmlspecialchars(DATABASE_NAME) . '\';
                document.getElementById(\'dbuser\').value = \'' . htmlspecialchars(DATABASE_USER) . '\';
                document.getElementById(\'dbpass\').value = \'' . htmlspecialchars(DATABASE_PASS) . '\';
                document.getElementById(\'dbhost\').value = \'' . htmlspecialchars(DATABASE_HOST) . '\';
            </script>';
        break;

    case 'mailSettings':
        MySQLConnect();

        // Determine mail option based on constants
        $mailOption = MAIL_MAILER == 3 && MAIL_SMTP_AUTH == 1 ? '4' : '';

        // Initialize default mail address
        $mailFromAddress = '';

        // Query for existing mail address
        if (isset($tables['settings'])) {
            $rs = MySQLQuery('SELECT value FROM settings WHERE setting = "fromAddress" LIMIT 1');
            if ($rs && mysqli_num_rows($rs) > 0) {
                $row = mysqli_fetch_row($rs);  // Get first row
                $mailFromAddress = $row[0];   // Assign first value
            }
        }

        // Ensure a fallback value if not set
        $mailFromAddress = empty($mailFromAddress) ? 'admin@example.com' : $mailFromAddress;

        // Output JavaScript with safe values
        echo '
        <script type="text/javascript">
        setActiveStep(5);
        showTextBlock(\'mailSettings\');
        document.getElementById(\'mailSupport\').value = \'opt' . ($mailOption !== '' ? $mailOption : htmlspecialchars(MAIL_MAILER)) . '\';
        document.getElementById(\'mailSendmail\').value = \'' . htmlspecialchars(MAIL_SENDMAIL_PATH) . '\';
        document.getElementById(\'mailSmtpHost\').value = \'' . htmlspecialchars(MAIL_SMTP_HOST) . '\';
        document.getElementById(\'mailSmtpPort\').value = \'' . htmlspecialchars(MAIL_SMTP_PORT) . '\';
        document.getElementById(\'mailSmtpUsername\').value = \'' . htmlspecialchars(MAIL_SMTP_USER) . '\';
        document.getElementById(\'mailSmtpPassword\').value = \'' . htmlspecialchars(MAIL_SMTP_PASS) . '\';
        document.getElementById(\'mailFromAddress\').value = \'' . htmlspecialchars((string) $mailFromAddress) . '\';
        changeMailForm();
        </script>';
        break;


    case 'setMailSettings':
        $mailSupportTxt = $_REQUEST['mailSupport'];
        $mailSendmailPath = trim((string) $_REQUEST['mailSendmail']);
        $mailSmtpHost = trim((string) $_REQUEST['mailSmtpHost']);
        $mailSmtpPort = (int)(trim((string) $_REQUEST['mailSmtpPort']));
        $mailSmtpUsername = trim((string) $_REQUEST['mailSmtpUsername']);
        $mailSmtpPassword = trim((string) $_REQUEST['mailSmtpPassword']);
        $fromAddress = substr(trim((string) $_REQUEST['mailFromAddress']), 0, 255);

        // validate e-mail address reply-to field
        if (strlen($fromAddress) < 4) {
            echo('
                <script type="text/javascript">
                    setActiveStep(5);
                    showTextBlock(\'mailSettings\');
                    var objLabel = document.getElementById(\'mailFromAddressLabel\');
                    objLabel.style.color = \'#ff0000\';
                    changeMailForm();
                    alert(\'You must enter your e-mail address to continue.\');
                </script>
                '
            );
        } else {
            if (strlen((string) $mailSupportTxt) == 4) {
                $mailSupport = (int)(substr((string) $mailSupportTxt, 3, 1));
            }

            if ($mailSupport == 4) {
                CATSUtility::changeConfigSetting('MAIL_MAILER', '3');
                CATSUtility::changeConfigSetting('MAIL_SMTP_AUTH', 'true');
            } else {
                CATSUtility::changeConfigSetting('MAIL_MAILER', sprintf('%d', $mailSupport));
                CATSUtility::changeConfigSetting('MAIL_SMTP_AUTH', 'false');
            }

            CATSUtility::changeConfigSetting('MAIL_SENDMAIL_PATH', '"' . $mailSendmailPath . '"');
            CATSUtility::changeConfigSetting('MAIL_SMTP_HOST', '"' . $mailSmtpHost . '"');
            CATSUtility::changeConfigSetting('MAIL_SMTP_PORT', sprintf('%d', $mailSmtpPort));
            CATSUtility::changeConfigSetting('MAIL_SMTP_USER', '"' . $mailSmtpUsername . '"');
            CATSUtility::changeConfigSetting('MAIL_SMTP_PASS', '"' . $mailSmtpPassword . '"');

            @session_name(CATS_SESSION_NAME);
            session_start();

            $_SESSION['fromAddressInstaller'] = $fromAddress;

            echo '<script type="text/javascript">
                      setActiveStep(6);
                      showTextBlock(\'detectingOptional\');
                      setTimeout("Installpage_populate(\'a=optionalComponents\');", 5000);
                  </script>';
        }
        break;

    case 'testDatabaseConnectivity':
        echo '<br /><span style="font-weight: bold;">Test Results</span>';

        echo '<table class="test_output">';

        if (InstallationTests::checkMySQL(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME)) {
            echo '<script type="text/javascript">showTextBlock(\'MySQLTestPassed\');</script>';
        } else {
            echo '<script type="text/javascript">showTextBlock(\'MySQLTestFailed\');</script>';
        }

        echo '</table>';

        echo '
            <script type="text/javascript">
                document.getElementById(\'testDatabaseConnectivity\').disabled = false;
                document.getElementById(\'testDatabaseConnectivityIndicator\').style.visibility = \'hidden\';
            </script>';
        break;

    case 'resumeParsing':
        echo '<script type="text/javascript">setActiveStep(4);</script>';

        if (ANTIWORD_PATH == '') {
            echo '
                <script type="text/javascript">
                    document.getElementById(\'docEnabled\').checked = false;
                    document.getElementById(\'docExecutable\').disabled = true;
                    document.getElementById(\'docExecutable\').value = \'\';
                    document.getElementById(\'docExecutableOrg\').value = \'\';
                </script>';
        } else {
            $antiwordWithSlashes = str_replace('\\', '\\\\', ANTIWORD_PATH);

            include_once(LEGACY_ROOT . '/lib/SystemUtility.php');
            /* Change Windows default command to UNIX default command hack. */
            if (stripos($antiwordWithSlashes, "c:\\") === 0 && ! SystemUtility::isWindows()) {
                $antiwordWithSlashes = '/usr/bin/antiword';
            }

            echo '
                <script type="text/javascript">
                    document.getElementById(\'docEnabled\').checked = true;
                    document.getElementById(\'docExecutable\').disabled = false;
                    document.getElementById(\'docExecutable\').value = \'' . $antiwordWithSlashes . '\';
                    document.getElementById(\'docExecutableOrg\').value = \'' . $antiwordWithSlashes . '\';
                </script>';
        }

        if (PDFTOTEXT_PATH == '') {
            echo '
                <script type="text/javascript">
                    document.getElementById(\'pdfEnabled\').checked = false;
                    document.getElementById(\'pdfExecutable\').disabled = true;
                    document.getElementById(\'pdfExecutable\').value = \'\';
                    document.getElementById(\'pdfExecutableOrg\').value = \'\';
                </script>';
        } else {
            $pdftotextWithSlashes = str_replace('\\', '\\\\', PDFTOTEXT_PATH);

            include_once(LEGACY_ROOT . '/lib/SystemUtility.php');
            /* Change Windows default command to UNIX default command hack. */
            if (stripos($pdftotextWithSlashes, "c:\\") === 0 && ! SystemUtility::isWindows()) {
                $pdftotextWithSlashes = '/usr/bin/pdftotext';
            }

            echo '
                <script type="text/javascript">
                    document.getElementById(\'pdfEnabled\').checked = true;
                    document.getElementById(\'pdfExecutable\').disabled = false;
                    document.getElementById(\'pdfExecutable\').value = \'' . $pdftotextWithSlashes . '\';
                    document.getElementById(\'pdfExecutableOrg\').value = \'' . $pdftotextWithSlashes . '\';
                </script>';
        }

        if (HTML2TEXT_PATH == '') {
            echo '
                <script type="text/javascript">
                    document.getElementById(\'htmlEnabled\').checked = false;
                    document.getElementById(\'htmlExecutable\').disabled = true;
                    document.getElementById(\'htmlExecutable\').value = \'\';
                    document.getElementById(\'htmlExecutableOrg\').value = \'\';
                </script>';
        } else {
            $html2textWithSlashes = str_replace('\\', '\\\\', HTML2TEXT_PATH);

            include_once(LEGACY_ROOT . '/lib/SystemUtility.php');
            /* Change Windows default command to UNIX default command hack. */
            if (stripos($html2textWithSlashes, "c:\\") === 0 && ! SystemUtility::isWindows()) {
                $html2textWithSlashes = '/usr/bin/html2text';
            }

            echo '
                <script type="text/javascript">
                    document.getElementById(\'htmlEnabled\').checked = true;
                    document.getElementById(\'htmlExecutable\').disabled = false;
                    document.getElementById(\'htmlExecutable\').value = \'' . $html2textWithSlashes . '\';
                    document.getElementById(\'htmlExecutableOrg\').value = \'' . $html2textWithSlashes . '\';
                </script>';
        }

        if (UNRTF_PATH == '') {
            echo '
                <script type="text/javascript">
                    document.getElementById(\'rtfEnabled\').checked = false;
                    document.getElementById(\'rtfExecutable\').disabled = true;
                    document.getElementById(\'rtfExecutable\').value = \'\';
                    document.getElementById(\'rtfExecutableOrg\').value = \'\';
                </script>';
        } else {
            $unrtfWithSlashes = str_replace('\\', '\\\\', UNRTF_PATH);

            include_once(LEGACY_ROOT . '/lib/SystemUtility.php');
            /* Change Windows default command to UNIX default command hack. */
            if (stripos($unrtfWithSlashes, "c:\\") === 0 && ! SystemUtility::isWindows()) {
                $unrtfWithSlashes = '/usr/bin/unrtf';
            }

            echo '
                <script type="text/javascript">
                    document.getElementById(\'rtfEnabled\').checked = true;
                    document.getElementById(\'rtfExecutable\').disabled = false;
                    document.getElementById(\'rtfExecutable\').value = \'' . $unrtfWithSlashes . '\';
                    document.getElementById(\'rtfExecutableOrg\').value = \'' . $unrtfWithSlashes . '\';
                </script>';
        }

        echo '<script type="text/javascript">showTextBlock(\'resumeParsing\');</script>';
        break;

    case 'testResumeParsing':
        echo '
            <script type="text/javascript">
                showTextBlock(\'resumeParsing\');
                Installpage_append(\'a=testResumeParsing2\', \'Please wait while your settings are tested...\');
            </script>';

        $antiwordPath = $_REQUEST['docExecutable'];
        $antiwordWithSlashes = str_replace('\\', '\\\\', $antiwordPath);
        CATSUtility::changeConfigSetting('ANTIWORD_PATH', '"' . $antiwordWithSlashes . '"');

        $pdftotextPath = $_REQUEST['pdfExecutable'];
        $pdftotextWithSlashes = str_replace('\\', '\\\\', $pdftotextPath);
        CATSUtility::changeConfigSetting('PDFTOTEXT_PATH', '"' . $pdftotextWithSlashes . '"');

        $html2textPath = $_REQUEST['htmlExecutable'];
        $html2textWithSlashes = str_replace('\\', '\\\\', $html2textPath);
        CATSUtility::changeConfigSetting('HTML2TEXT_PATH', '"' . $html2textWithSlashes . '"');

        $unrtfPath = $_REQUEST['rtfExecutable'];
        $unrtfWithSlashes = str_replace('\\', '\\\\', $unrtfPath);
        CATSUtility::changeConfigSetting('UNRTF_PATH', '"' . $unrtfWithSlashes . '"');

        break;

    case 'testResumeParsing2':
        echo '<script type="text/javascript">showTextBlock(\'resumeParsing\');</script>';

        $result = true;

        echo '<br />',
        '<span style="font-weight: bold;">Test Results</span>',
        '<table class="test_output">';

        $antiwordResults = ! (ANTIWORD_PATH != '' && ! InstallationTests::checkAntiword());
        $pdftotextResults = ! (PDFTOTEXT_PATH != '' && ! InstallationTests::checkPdftotext());
        $html2textResults = ! (HTML2TEXT_PATH != '' && ! InstallationTests::checkHtml2text());
        if (UNRTF_PATH != '' && ! $html2textResults) {
            echo '<tr class="fail"><td>UnRTF depends on Html2Text and can not execute.</td></tr>';
            $unrtfResults = false;
        } else {
            $unrtfResults = ! (UNRTF_PATH != '' && ! InstallationTests::checkUnrtf());
        }

        if (! $antiwordResults || ! $pdftotextResults) {
            echo '<script type="text/javascript">showTextBlock(\'testFailed\');</script>';
        } else {
            echo '<script type="text/javascript">showTextBlock(\'testPassedParsing\');</script>';
        }

        break;

    case 'optionalComponents':
        MySQLConnect();
        initializeOptionalComponents();

        echo '<script type="text/javascript">';

        /* Detect date format preferences. */
        $rs = MySQLQuery('SELECT date_format_ddmmyy FROM site', true);
        $record = $rs ? mysqli_fetch_assoc($rs) : [];

        if (! isset($record['date_format_ddmmyy']) || $record['date_format_ddmmyy'] == 0) {
            echo 'document.getElementById(\'dateFormat\').value = \'mdy\';';
        } else {
            echo 'document.getElementById(\'dateFormat\').value = \'dmy\';';
        }

        echo 'setActiveStep(6);';
        echo 'showTextBlock(\'pickOptionalComponents\');';
        echo '</script>';

        $onClick = 'document.getElementById(\'pickOptionalComponents\').style.display = \'none\'; ';
        $onClick .= 'showTextBlock(\'installingComponentsExtra\'); ';
        $onClick .= 'Installpage_populate(\'a=setupOptional&list=';
        foreach ($optionalComponents as $index => $component) {
            $onClick .= htmlspecialchars((string) $index) . ',\' + encodeURIComponent(getCheckedValue(document.getElementsByName(\'' . htmlspecialchars((string) $index) . '\'))) + \',';
        }
        $onClick .= '&timeZone=\' + encodeURIComponent(document.getElementById(\'timeZone\').value) + \'&dateFormat=\' + encodeURIComponent(document.getElementById(\'dateFormat\').value) + \'\');';

        echo '<script type="text/javascript">';
        echo 'var onClick = \'' . addslashes($onClick) . '\';';
        echo 'document.getElementById(\'extrasList\').innerHTML = \'<table style="width: 450px;"><tr><td style="font-weight: bold;">Feature Name</td><td style="width: 85px; font-weight: bold">Install</td><td style="width: 85px; font-weight: bold">Do Not Install</td></tr>';
        foreach ($optionalComponents as $index => $component) {
            echo '<tr>';
            echo '<td><a href="javascript:void(0);" onclick="function HTML' . htmlspecialchars((string) $index) . '() { return \\\'<p style=\\\' + String.fromCharCode(34) + \\\'font-weight: bold; padding-left: 8px; padding-right: 8px;\\\' + String.fromCharCode(34) + \\\'>' . htmlspecialchars((string) $component['name']) . '</p><p style=\\\' + String.fromCharCode(34) + \\\'padding-left: 8px; padding-right: 8px;\\\' + String.fromCharCode(34) + \\\'>' . htmlspecialchars((string) $component['description']) . '</p>\\\'; } showPopWinHTML(HTML' . htmlspecialchars((string) $index) . '(), 400, 100, null); return false;">' . htmlspecialchars((string) $component['name']) . '</a>&nbsp;&nbsp;&nbsp;</td>';
            echo '<td><input type="radio" name="' . htmlspecialchars((string) $index) . '" value="true"' . ($component['componentExists'] ? ' checked' : '') . '></td>';
            echo '<td><input type="radio" name="' . htmlspecialchars((string) $index) . '" value="false"' . ($component['componentExists'] ? '' : ' checked') . '></td>';
            echo '</tr>';
        }

        echo '</table><br /><br />';

        echo '<input type="button" style="float: right;" class="button" value="Next -->" onclick="\' + onClick + \'">\';</script>';
        break;

    case 'setupOptional':
        MySQLConnect();
        initializeOptionalComponents();

        @session_name(CATS_SESSION_NAME);
        session_start();

        // FIXME: Input validation.
        $timeZone = $_REQUEST['timeZone'];
        CATSUtility::changeConfigSetting('OFFSET_GMT', ($timeZone));

        $dateFormat = $_REQUEST['dateFormat'];

        $_SESSION['timeZoneInstaller'] = $timeZone;
        $_SESSION['dateFormatInstaller'] = $dateFormat;

        $list = explode(',', (string) $_REQUEST['list']);
        $counter = count($list);

        for ($i = 0; $i < $counter; $i += 2) {
            if (! isset($list[$i + 1])) {
                continue;
            }

            if ($optionalComponents[$list[$i]]['componentExists'] == false) {
                if ($list[$i + 1] === 'true') {
                    eval($optionalComponents[$list[$i]]['installCode']);
                }
            } elseif ($list[$i + 1] === 'false') {
                eval($optionalComponents[$list[$i]]['removeCode']);
            }
        }

        echo '<script type="text/javascript">
                  setActiveStep(7);
                  showTextBlock(\'installingComponentsMaint\');
                  setTimeout("Installpage_populate(\'a=maint\');", 5000);
              </script>';
        break;

    case 'detectRevision':
        MySQLConnect();

        echo '<script type="text/javascript">setActiveStep(3);</script>';

        if (count($tables) == 0) {
            echo '<script type="text/javascript">
                      showTextBlock(\'emptyDatabase\');
                      document.getElementById(\'emptyCheckBox\').checked = true;
                  </script>';
            die();
        }

        $rs = MySQLQuery('SELECT * FROM candidate', true);
        $fields = [];
        while ($meta = @mysqli_fetch_field($rs)) {
            if ($meta) {
                $fields[$meta->name] = true;
            }
        }

        $catsVersion = '';

        /* Look for more versions here. */
        if (! isset($fields['date_available']) && isset($tables['client'])) {
            $catsVersion = 'CATS 0.5.0.';
        } elseif (! isset($tables['candidate_joborder_status']) && isset($tables['client'])) {
            $catsVersion = 'CATS 0.5.1 or 0.5.2.';
        } elseif (! isset($tables['candidate_foreign']) && isset($tables['client'])) {
            $catsVersion = 'CATS 0.5.5.';
        } elseif (! isset($tables['history']) && isset($tables['client'])) {
            $catsVersion = 'CATS 0.6.x.';
        } elseif (isset($tables['history'])) {
            echo '
                <script type="text/javascript">
                    showTextBlock(\'catsUpToDate\');
                    document.getElementById(\'currentCheckBox\').checked = true;
                </script>';

            echo '<br /><br />';
            die();
        }

        if ($catsVersion === '') {
            echo '
                <script type="text/javascript">
                    showTextBlock(\'unknownDataInDatabase\');
                    document.getElementById(\'tableNamesUnknown\').innerHTML = \'\';
                </script>';

            foreach ($tables as $table => $data) {
                echo '<script type="text/javascript">document.getElementById(\'tableNamesUnknown\').innerHTML += \'' . htmlspecialchars((string) $table) . ', \';</script>';
            }
        } else {
            echo '
                <script type="text/javascript">
                    showTextBlock(\'databaseUpgrade\');
                    document.getElementById(\'upgradeVersion\').innerHTML = \'' . htmlspecialchars($catsVersion) . '\';
                </script>';
        }
        break;

    case 'queryResetDatabase':
        echo '<script type="text/javascript">showTextBlock(\'queryResetDatabase\');</script>';
        break;

    case 'resetDatabase':
        MySQLConnect();

        foreach ($tables as $table => $data) {
            $queryResult = MySQLQuery(sprintf("DROP TABLE %s", $table));
        }

        if (! isset($_REQUEST['type'])) {
            echo '<script type="text/javascript">Installpage_populate(\'a=detectRevision\');</script>';
        } else {
            echo '<script type="text/javascript">Installpage_populate(\'a=selectDBType&type=' . urlencode((string) $_REQUEST['type']) . '\');</script>';
        }
        break;

    case 'selectDBType':
        $type = $_REQUEST['type'];

        switch ($type) {
            case 'empty':
                echo '<script type="text/javascript">
                          showTextBlock(\'installingComponents\');
                          Installpage_populate(\'a=doInstallEmptyDatabase\');
                      </script>';
                break;

            case 'demo':
                echo '<script type="text/javascript">showTextBlock(\'queryInstallDemo\');</script>';
                break;

            case 'restore':
                echo '<script type="text/javascript">
                          document.getElementById(\'continueRestoreCheck\').checked = false;
                          showTextBlock(\'queryInstallBackup\');
                      </script>';
                break;

            default:
                break;
        }
        break;

            case 'restoreFromBackup':
                include_once(LEGACY_ROOT . '/lib/FileCompressor.php');

                MySQLConnect();
                global $mySQLConnection;

                mysqli_set_charset($mySQLConnection, 'utf8mb4');
                mysqli_query($mySQLConnection, "SET SESSION sql_mode = ''");

                // Retrieve timestamp from request
                if (!isset($_REQUEST['timestamp']) || empty($_REQUEST['timestamp'])) {
                    die('Error: No backup selected for restoration.');
                }

                $timestamp = preg_replace('/[^0-9_]/', '', $_REQUEST['timestamp']);
                $backupFile = LEGACY_ROOT . "/backups/backup_${timestamp}.sql.gz";
                $attachmentsFile = LEGACY_ROOT . "/backups/attachments_${timestamp}.tar.gz";

                if (!file_exists($backupFile)) {
                    die('Error: Selected backup file does not exist.');
                }

                error_log("Restoring from backup: " . $backupFile);

                // Run backupDB.php restore
                $restoreCommand = "php " . LEGACY_ROOT . "/modules/install/backupDB.php restore " . escapeshellarg($backupFile);
                exec($restoreCommand, $output, $returnVar);

                if ($returnVar !== 0) {
                    error_log("Backup restore failed: " . implode("\n", $output));
                    die('Error: Database restore failed.');
                }

                // Restore attachments if available
                if (file_exists($attachmentsFile)) {
                    error_log("Restoring attachments from: " . $attachmentsFile);
                    exec("tar -xzf " . escapeshellarg($attachmentsFile) . " -C " . escapeshellarg(LEGACY_ROOT . '/'), $output, $returnVar);
                    if ($returnVar !== 0) {
                        error_log("Attachment restore failed: " . implode("\n", $output));
                    }
                }

                echo '<script type="text/javascript">Installpage_populate(\'a=upgradeCats\');</script>';
                break;





    case 'doDeleteBackup':
        echo '<script type="text/javascript">Installpage_populate(\'a=detectRevision\', \'subFormBlock\', \'\');</script>';
        break;

        case 'doInstallEmptyDatabase':
            MySQLConnect();

            CATSUtility::changeConfigSetting('ENABLE_DEMO_MODE', 'false');

            $schema = file_get_contents('db/cats_schema.sql');
            MySQLQueryMultiple($schema, ";\n");

            //Check if we need to update from 0.6.0 to 0.7.0
            $tables = [];
            $result = MySQLQuery(sprintf("SHOW TABLES FROM `%s`", DATABASE_NAME));
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $tables[$row[0]] = true;
            }

            if (! isset($tables['history'])) {
                // FIXME: File exists?!
                $schema = file_get_contents('db/upgrade-0.6.x-0.7.0.sql');
                MySQLQueryMultiple($schema);
            }

            echo '<script type="text/javascript">Installpage_populate(\'a=resumeParsing\');</script>';
            break;

    case 'onLoadDemoData':
        CATSUtility::changeConfigSetting('ENABLE_DEMO_MODE', 'true');

        include_once(LEGACY_ROOT . '/lib/FileCompressor.php');
        MySQLConnect();
        $extractor = new ZipFileExtractor('./db/cats_testdata.bak');

        /* Extract the file.  This command also executes all sql commands in the file. */
        /* Normally, we could just do the following lines, but we want a custom extractor
           that ignores the file 'database', and executes all of the catsbackup.sql.xxx
           files rather than extracting them. */
        /*
            if (!$extractor->open() || !$extractor->extractAll())
            {
                echo($extractor->getErrorMessage());
            }
        */

        if (! $extractor->open()) {
            echo($extractor->getErrorMessage());
        }

        $metaData = $extractor->getMetaData();

        foreach ($metaData['centralDirectory'] as $index => $data) {
            $fileName = $data['filename'];

            /* Execute all sql files */
            if (strpos((string) $fileName, 'db/catsbackup.sql.') === 0) {
                $fileContents = $extractor->getFile($index);
                MySQLQueryMultiple($fileContents, '((ENDOFQUERY))');
            }
            /* Extract everything else but ./database */
            elseif ($fileName != 'database') {
                if (strpos((string) $fileName, '/') !== false) {
                    $directorySplit = explode('/', (string) $fileName);
                    unset($directorySplit[count($directorySplit) - 1]);
                    $directory = implode('/', $directorySplit);
                    @mkdir($directory, 0777, true);
                }

                $fileContents = $extractor->getFile($index);

                if ($fileContents === false) {
                    error_log("Failed to extract file contents for: " . $fileName);
                    continue; // Skip this file
                }

                // Ensure $fileName is not a directory before writing
                if (is_dir($fileName)) {
                    error_log("Skipping directory instead of writing to file: " . $fileName);
                    continue;
                }

                // Ensure the parent directory exists
                $dirPath = dirname((string) $fileName);
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0777, true); // Create directory if missing
                }

                error_log("Writing to file: " . $fileName);
                file_put_contents($fileName, $fileContents);

            }
        }

        echo '
            <script type="text/javascript">
                showTextBlock(\'installingComponents\');
                Installpage_populate(\'a=upgradeCats\');
            </script>';
        break;

    case 'upgradeCats':
        MySQLConnect();

        /* This shouldn't be possible - there is no option to upgrade CATS if no tables are in the database. */
        if (count($tables) == 0) {
            echo 'Error - no schema present.<br /><br /> ';
            echo '<input type="button" class="button" value="Retry Installation" onclick="Installpage_populate(\'a=detectConnectivity\', \'subFormBlock\', \'Checking database connectivity...\');">&nbsp;&nbsp;&nbsp;';
            die();
        }

        $revision = 0;
        $rs = MySQLQuery('SELECT * FROM candidate', true);
        $fields = [];
        while ($meta = mysqli_fetch_field($rs)) {
            $fields[$meta->name] = true;
        }

        /* Look for more versions here. */
        if (! isset($fields['date_available'])) {
            /* 0.5.0 */
            $revision = 50;
        } elseif (! isset($tables['candidate_joborder_status'])) {
            /* 0.5.2 */
            $revision = 52;
        } elseif (! isset($tables['candidate_foreign']) && ! isset($tables['extra_field'])) {
            /* 0.5.5 */
            $revision = 55;
        } elseif (! isset($tables['history'])) {
            /* 0.6.0 */
            $revision = 60;
        } elseif (! isset($tables['candidate_duplicates'])) {
            /* 0.9.4 */
            $revision = 94;
        } elseif (isset($tables['candidate_duplicates'])) {
            /* 0.9.5 */
            $revision = 95;
        }

        if ($revision <= 50) {
            // FIXME: File exists?!
            $schema = file_get_contents('db/upgrade-0.5.0-0.5.1.sql');
            MySQLQueryMultiple($schema);
        }
        if ($revision <= 52) {
            // FIXME: File exists?!
            $schema = file_get_contents('db/upgrade-0.5.2-0.5.5.sql');
            MySQLQueryMultiple($schema);
        }
        if ($revision <= 55) {
            // FIXME: File exists?!
            $schema = file_get_contents('db/upgrade-0.5.5-0.6.x.sql');
            MySQLQueryMultiple($schema);
        }
        if ($revision <= 60) {
            // FIXME: File exists?!
            $schema = file_get_contents('db/upgrade-0.6.x-0.7.0.sql');
            MySQLQueryMultiple($schema);
        }
        if ($revision <= 94) {
            // FIXME: File exists?!
            $schema = file_get_contents('db/upgrade-0.9.4-0.9.5.sql');
            MySQLQueryMultiple($schema);
        }

        // FIXME: File exists?!
        $schema = @file_get_contents('db/upgrade-zipcodes.sql');
        MySQLQueryMultiple($schema);

        echo '<script type="text/javascript">Installpage_populate(\'a=resumeParsing\');</script>';
        break;

    case 'maint':
        @session_name(CATS_SESSION_NAME);
        session_start();

        if (isset($_SESSION['CATS'])) {
            unset($_SESSION['CATS']);
        }

        if (isset($_SESSION['modules'])) {
            unset($_SESSION['modules']);
        }

        echo '<script type="text/javascript">
                  showTextBlock(\'installingComponentsMaint\');
                  setTimeout("Installpage_maint();", 2000);
              </script>';
        break;

    case 'reindexResumes':
        echo '<script type="text/javascript">
                  showTextBlock(\'installingComponentsMaintResume\');
                  Installpage_populate(\'a=onReindexResumes\');
              </script>';
        break;

    case 'onReindexResumes':
        include_once(LEGACY_ROOT . '/modules/install/ajax/attachmentsReindex.php');

        echo '<script type="text/javascript">
                  Installpage_populate(\'a=maintComplete\');
              </script>';

        break;

    case 'maintComplete':
        MySQLConnect();

        // FIXME: Make sure we have permissions to create INSTALL_BLOCK.
        file_put_contents(
            'INSTALL_BLOCK',
            'This file prevents the installer from running. Remove this file to edit or reset your CATS installation.'
        );

        @session_name(CATS_SESSION_NAME);
        session_start();


        $fromAddress = $_SESSION['fromAddressInstaller'];

        // If this is an existing database, just set all the fromAddress settings to new
        MySQLQuery(sprintf('UPDATE settings SET value = "%s" WHERE setting = "fromAddress"', $fromAddress));
        // This is a new install, insert a settings value for each site in the database
        if (mysqli_affected_rows($mySQLConnection) == 0) {
            // Insert a "fromAddress" = $fromAddress for each site
            MySQLQuery(sprintf(
                'INSERT INTO settings (setting, value, site_id, settings_type) '
                . 'SELECT "fromAddress", "%s", site_id, 1 FROM site',
                $fromAddress
            ));
            // Insert a "configured" = 1 setting for each site
            MySQLQuery(
                'INSERT INTO settings (setting, value, site_id, settings_type) '
                . 'SELECT "configured", "1", site_id, 1 FROM site'
            );
        }

        /* We can't set date format ortime zone until installer is complete
         * (rows don't exist in schema till now.)
         */

        $dateFormat = $_SESSION['dateFormatInstaller'];

        if ($dateFormat == 'mdy') {
            MySQLQuery('UPDATE site SET date_format_ddmmyy = 0');
        } else {
            MySQLQuery('UPDATE site SET date_format_ddmmyy = 1');
        }

        $timeZone = $_SESSION['timeZoneInstaller'];

        MySQLQuery(sprintf("UPDATE site SET time_zone = %s", $timeZone));

        if (isset($_SESSION['CATS'])) {
            unset($_SESSION['CATS']);
        }

        if (isset($_SESSION['modules'])) {
            unset($_SESSION['modules']);
        }

        echo '<script type="text/javascript">setActiveStep(7);</script>';

        if (ENABLE_DEMO_MODE) {
            echo '<script type="text/javascript">showTextBlock("installCompleteDemo");</script>';
        } else {
            echo '<script type="text/javascript">showTextBlock("installCompleteProd");</script>';
        }
        break;

    case 'loginCATS':
        MySQLConnect();

        /* Determine if a default user is set. */
        $rs = MySQLQuery("SELECT * FROM user WHERE user_name = 'admin' AND password = 'cats'");
        if ($rs && mysqli_fetch_row($rs)) {
            //Default user set
            echo '<script type="text/javascript">document.location.href="index.php?defaultlogin=true";</script>';
        } else {
            echo '<script type="text/javascript">document.location.href="index.php";</script>';
        }
        break;

    default:
        die('Invalid action.');
}

function MySQLConnect(): ?false
{
    global $tables, $mySQLConnection;

    $mySQLConnection = @mysqli_connect(
        DATABASE_HOST,
        DATABASE_USER,
        DATABASE_PASS
    );

    if (! $mySQLConnection) {
        $error = "errno: " . mysqli_connect_errno() . ", ";
        $error .= "error: " . mysqli_connect_error();

        die(
            '<p style="background: #ec3737; padding: 4px; margin-top: 0; font:'
            . ' normal normal bold 12px/130% Arial, Tahoma, sans-serif;">Error '
            . " Connecting to Database</p><pre>\n\n" . $error . "</pre>\n\n"
        );
    }


    /* Create an array of all tables in the database. */
    $tables = [];
    $result = MySQLQuery(sprintf("SHOW TABLES FROM `%s`", DATABASE_NAME));
    while ($row = mysqli_fetch_row($result)) {
        $tables[$row[0]] = true;
    }

    /* Select CATS database. */
    $isDBSelected = @mysqli_select_db($mySQLConnection, DATABASE_NAME);
    if (! $isDBSelected) {
        $error = "errno: " . mysqli_connect_errno() . ", ";
        $error .= "error: " . mysqli_connect_error();

        die(
            '<p style="background: #ec3737; padding: 4px; margin-top: 0; font:'
            . ' normal normal bold 12px/130% Arial, Tahoma, sans-serif;">Error'
            . " Selecting Database</p><pre>\n\n" . $error . "</pre>\n\n"
        );
    }
    return null;
}

function MySQLQuery(string $query, $ignoreErrors = false): bool|\mysqli_result
{
    global $mySQLConnection;

    error_log("SQL Query: " . $query);
    $queryResult = mysqli_query($mySQLConnection, $query);

    // Check for connection errors
    if (! $mySQLConnection) {
        $error = "errno: " . mysqli_connect_errno() . ", ";
        $error .= "error: " . mysqli_connect_error();

        die(
            '<p style="background: #ec3737; padding: 4px; margin-top: 0; font:'
            . ' normal normal bold 12px/130% Arial, Tahoma, sans-serif;">Query'
            . " Error -- Please Report This Bug!</p><pre>\n\nMySQL Query "
            . "Failed: " . $error . "\n\n" . $query . "</pre>\n\n"
        );
    }

    // Check for SQL query errors
    if (! $queryResult && !$ignoreErrors) {
        die(
            '<p style="background: #ec3737; padding: 4px; margin-top: 0; font:'
            . ' normal normal bold 12px/130% Arial, Tahoma, sans-serif;">SQL Query'
            . " Error:</p><pre>\n\nError: " . mysqli_error($mySQLConnection)
            . "\n\nQuery: " . $query . "</pre>\n\n"
        );
    }

    return $queryResult;
}


function MySQLQueryMultiple($SQLData, $delimiter = ';'): void
{
    $SQLStatments = explode($delimiter, (string) $SQLData);

    foreach ($SQLStatments as $SQL) {
        $SQL = trim($SQL);

        if ($SQL === '' || $SQL === '0') {
            continue;
        }

        try {
            MySQLQuery($SQL);
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e; // Rethrow the exception if it's not a duplicate column
            }
        }

    }
}

function initializeOptionalComponents(): void
{
    global $optionalComponents;

    //Detect which components are installed and which ones are not
    include_once(LEGACY_ROOT . '/modules/install/OptionalComponents.php');

    foreach ($optionalComponents as $index => $data) {
        $optionalComponents[$index]['componentExists'] = isset($data['detectCode']) ? eval($data['detectCode']) : false;
    }
}
