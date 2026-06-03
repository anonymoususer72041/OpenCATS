<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ExpectationException;

class InstallerContext extends MinkContext implements Context, SnippetAcceptingContext
{
    private $rootPath;
    private $configPath;
    private $installBlockPath;
    private $configBackupPath;
    private $installBlockBackupPath;
    private $installBlockExisted;

    public function __construct()
    {
        $this->rootPath = getcwd();
        $this->configPath = $this->rootPath . '/config.php';
        $this->installBlockPath = $this->rootPath . '/INSTALL_BLOCK';
        $this->configBackupPath = null;
        $this->installBlockBackupPath = null;
        $this->installBlockExisted = false;
    }

    /**
     * @BeforeScenario @installer
     */
    public function prepareInstallerScenario()
    {
        $this->backupInstallerFiles();
    }

    /**
     * @AfterScenario @installer
     */
    public function cleanupInstallerScenario()
    {
        $errors = array();

        try
        {
            $this->restoreInstallerFiles();
        }
        catch (RuntimeException $exception)
        {
            $errors[] = $exception->getMessage();
        }

        try
        {
            $this->resetInstallerDatabase();
        }
        catch (RuntimeException $exception)
        {
            $errors[] = $exception->getMessage();
        }

        if (count($errors) > 0)
        {
            throw new RuntimeException("Installer cleanup failed:\n- " . implode("\n- ", $errors));
        }
    }

    /**
     * @Given the installer is unlocked
     */
    public function theInstallerIsUnlocked()
    {
        if (file_exists($this->installBlockPath) && !unlink($this->installBlockPath))
        {
            throw new RuntimeException('Could not remove INSTALL_BLOCK for installer test.');
        }
    }

    /**
     * @Given the installer database is empty
     */
    public function theInstallerDatabaseIsEmpty()
    {
        $this->resetInstallerDatabase();
    }

    /**
     * @When I open the installation wizard
     */
    public function iOpenTheInstallationWizard()
    {
        $this->visitPath('/installwizard.php');
    }

    /**
     * @Then the installation wizard should show the system check
     */
    public function theInstallationWizardShouldShowTheSystemCheck()
    {
        $this->waitForVisibleElement('startInstall');
        $this->waitForVisibleElement('testPassed');
    }

    /**
     * @When I continue from the system check
     */
    public function iContinueFromTheSystemCheck()
    {
        $this->clickVisibleButtonIn('testPassed', 'Next -->');
    }

    /**
     * @Then the installer should show database configuration
     */
    public function theInstallerShouldShowDatabaseConfiguration()
    {
        $this->waitForVisibleElement('databaseConnectivity');
    }

    /**
     * @When I configure and test the installer database connection
     */
    public function iConfigureAndTestTheInstallerDatabaseConnection()
    {
        $this->waitForVisibleElement('databaseConnectivity');
        $this->fillField('dbname', $this->getInstallerDatabaseName());
        $this->fillField('dbuser', $this->getInstallerDatabaseUser());
        $this->fillField('dbpass', $this->getInstallerDatabasePassword());
        $this->fillField('dbhost', $this->getInstallerDatabaseHostForWizard());
        $this->pressButton('testDatabaseConnectivity');
    }

    /**
     * @Then the installer database connection should pass
     */
    public function theInstallerDatabaseConnectionShouldPass()
    {
        $this->waitForVisibleElement('MySQLTestPassed');
    }

    /**
     * @When I continue after database connectivity
     */
    public function iContinueAfterDatabaseConnectivity()
    {
        $this->clickVisibleButtonIn('MySQLTestPassed', 'Next -->');
    }

    /**
     * @Then the installer should offer a new empty database installation
     */
    public function theInstallerShouldOfferANewEmptyDatabaseInstallation()
    {
        $this->waitForVisibleElement('emptyDatabase');
    }

    /**
     * @When I choose the empty database installation path
     */
    public function iChooseTheEmptyDatabaseInstallationPath()
    {
        $this->waitForVisibleElement('emptyDatabase');
        $this->clickVisibleButtonIn('emptyDatabase', 'Next -->');
    }

    /**
     * @Then the installer should show resume indexing configuration
     */
    public function theInstallerShouldShowResumeIndexingConfiguration()
    {
        $this->waitForVisibleElement('resumeParsing', 120000);
    }

    /**
     * @When I skip resume indexing
     */
    public function iSkipResumeIndexing()
    {
        $this->waitForVisibleElement('resumeParsing');
        $this->clickVisibleButtonIn('resumeParsing', 'Skip this Step');
    }

    /**
     * @Then the installer should show mail settings
     */
    public function theInstallerShouldShowMailSettings()
    {
        $this->waitForVisibleElement('mailSettings');
    }

    /**
     * @When I configure no mail support
     */
    public function iConfigureNoMailSupport()
    {
        $this->waitForVisibleElement('mailSettings');
        $this->fillField('mailFromAddress', $this->getInstallerMailFromAddress());
        $this->selectOption('mailSupport', 'opt0');
        $this->pressButton('setMailSettings');
    }

    /**
     * @Then the installer should show optional component configuration
     */
    public function theInstallerShouldShowOptionalComponentConfiguration()
    {
        $this->waitForVisibleElement('pickOptionalComponents', 30000);
    }

    /**
     * @When I continue through optional component configuration
     */
    public function iContinueThroughOptionalComponentConfiguration()
    {
        $this->waitForVisibleElement('pickOptionalComponents');
        $this->fillField('defaultPhoneCountryCodeDigits', $this->getInstallerDefaultPhoneCountryCode());
        $this->clickVisibleButtonIn('pickOptionalComponents', 'Next -->');
    }

    /**
     * @Then the installer final maintenance should run
     */
    public function theInstallerFinalMaintenanceShouldRun()
    {
        $this->waitForVisibleElement('installingComponentsMaint', 30000);
    }

    /**
     * @Then the installer should reach the installation complete screen
     */
    public function theInstallerShouldReachTheInstallationCompleteScreen()
    {
        $this->waitForVisibleElement('installCompleteProd', 180000);
        $this->assertPageDoesNotContainInstallerError();
    }

    /**
     * @Then INSTALL_BLOCK should exist
     */
    public function installBlockShouldExist()
    {
        if (!file_exists($this->installBlockPath))
        {
            throw new RuntimeException('INSTALL_BLOCK was not created by the installer.');
        }
    }

    /**
     * @When I start OpenCATS from the installer
     */
    public function iStartOpenCATSFromTheInstaller()
    {
        $this->clickVisibleButtonIn('installCompleteProd', 'Start OpenCATS');
    }

    /**
     * @Then the login page should be reachable
     */
    public function theLoginPageShouldBeReachable()
    {
        $this->waitForVisibleElement('loginForm', 30000);
        $this->waitForVisibleElement('username');
        $this->waitForVisibleElement('password');
        $this->assertPageDoesNotContainInstallerError();
    }

    private function backupInstallerFiles()
    {
        if (!is_readable($this->configPath))
        {
            throw new RuntimeException('config.php is not readable.');
        }

        $this->configBackupPath = tempnam(sys_get_temp_dir(), 'opencats-installer-config-');
        if ($this->configBackupPath === false)
        {
            throw new RuntimeException('Could not create config.php backup file.');
        }
        $this->copyRequired($this->configPath, $this->configBackupPath, 'Could not back up config.php.');

        $this->installBlockExisted = file_exists($this->installBlockPath);
        if ($this->installBlockExisted)
        {
            $this->installBlockBackupPath = tempnam(sys_get_temp_dir(), 'opencats-installer-block-');
            if ($this->installBlockBackupPath === false)
            {
                throw new RuntimeException('Could not create INSTALL_BLOCK backup file.');
            }
            $this->copyRequired($this->installBlockPath, $this->installBlockBackupPath, 'Could not back up INSTALL_BLOCK.');
        }
    }

    private function restoreInstallerFiles()
    {
        $errors = array();

        if ($this->configBackupPath !== null && file_exists($this->configBackupPath))
        {
            if ($this->copyRequiredForCleanup($this->configBackupPath, $this->configPath, 'Could not restore config.php.', $errors))
            {
                if ($this->unlinkRequiredForCleanup($this->configBackupPath, 'Could not remove temporary config.php backup file.', $errors))
                {
                    $this->configBackupPath = null;
                }
            }
        }
        else if ($this->configBackupPath !== null)
        {
            $errors[] = 'Could not restore config.php because the backup file is missing.';
        }

        if ($this->installBlockExisted)
        {
            if ($this->installBlockBackupPath !== null && file_exists($this->installBlockBackupPath))
            {
                if ($this->copyRequiredForCleanup($this->installBlockBackupPath, $this->installBlockPath, 'Could not restore INSTALL_BLOCK.', $errors))
                {
                    if ($this->unlinkRequiredForCleanup($this->installBlockBackupPath, 'Could not remove temporary INSTALL_BLOCK backup file.', $errors))
                    {
                        $this->installBlockBackupPath = null;
                    }
                }
            }
            else if ($this->installBlockBackupPath !== null)
            {
                $errors[] = 'Could not restore INSTALL_BLOCK because the backup file is missing.';
            }
        }
        else if (file_exists($this->installBlockPath))
        {
            $this->unlinkRequiredForCleanup($this->installBlockPath, 'Could not remove installer-created INSTALL_BLOCK.', $errors);
        }

        if (count($errors) > 0)
        {
            throw new RuntimeException("Installer file cleanup failed:\n- " . implode("\n- ", $errors));
        }
    }

    private function copyRequired($source, $destination, $message)
    {
        if (!copy($source, $destination))
        {
            throw new RuntimeException($message);
        }
    }

    private function unlinkRequired($path, $message)
    {
        if (file_exists($path) && !unlink($path))
        {
            throw new RuntimeException($message);
        }
    }

    private function copyRequiredForCleanup($source, $destination, $message, &$errors)
    {
        try
        {
            $this->copyRequired($source, $destination, $message);
            return true;
        }
        catch (RuntimeException $exception)
        {
            $errors[] = $exception->getMessage();
            return false;
        }
    }

    private function unlinkRequiredForCleanup($path, $message, &$errors)
    {
        try
        {
            $this->unlinkRequired($path, $message);
            return true;
        }
        catch (RuntimeException $exception)
        {
            $errors[] = $exception->getMessage();
            return false;
        }
    }

    private function resetInstallerDatabase()
    {
        $databaseName = $this->getInstallerDatabaseName();
        $this->assertInstallerDatabaseNameIsSafe($databaseName);

        $connection = $this->connectToDatabaseServer();
        $this->query($connection, sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8 COLLATE utf8_unicode_ci',
            $this->escapeIdentifier($databaseName)
        ));
        $this->grantInstallerDatabaseAccess($connection, $databaseName);

        $this->query($connection, sprintf('USE `%s`', $this->escapeIdentifier($databaseName)));
        $tables = array();
        $result = $this->query($connection, 'SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        while ($row = mysqli_fetch_row($result))
        {
            $tables[] = $row[0];
        }

        $this->query($connection, 'SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table)
        {
            $this->query($connection, sprintf('DROP TABLE `%s`', $this->escapeIdentifier($table)));
        }
        $this->query($connection, 'SET FOREIGN_KEY_CHECKS = 1');
        mysqli_close($connection);
    }

    private function connectToDatabaseServer()
    {
        $connection = @mysqli_connect(
            $this->getInstallerDatabaseAdminHost(),
            $this->getInstallerDatabaseAdminUser(),
            $this->getInstallerDatabaseAdminPassword(),
            '',
            $this->getInstallerDatabasePort()
        );

        if (!$connection)
        {
            $connection = @mysqli_connect(
                $this->getInstallerDatabaseHost(),
                $this->getInstallerDatabaseUser(),
                $this->getInstallerDatabasePassword(),
                '',
                $this->getInstallerDatabasePort()
            );
        }

        if (!$connection)
        {
            throw new RuntimeException(
                'Could not connect to installer database server. Set OPENCATS_INSTALLER_DB_ADMIN_USER, '
                . 'OPENCATS_INSTALLER_DB_ADMIN_PASS, and related OPENCATS_INSTALLER_DB_* variables if needed.'
            );
        }

        return $connection;
    }

    private function grantInstallerDatabaseAccess($connection, $databaseName)
    {
        $user = mysqli_real_escape_string($connection, $this->getInstallerDatabaseUser());
        $password = mysqli_real_escape_string($connection, $this->getInstallerDatabasePassword());
        $escapedDatabaseName = $this->escapeIdentifier($databaseName);

        @mysqli_query(
            $connection,
            sprintf("GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%' IDENTIFIED BY '%s'", $escapedDatabaseName, $user, $password)
        );
    }

    private function query($connection, $query)
    {
        $result = mysqli_query($connection, $query);
        if ($result === false)
        {
            throw new RuntimeException(sprintf(
                'Installer database query failed: %s; query: %s',
                mysqli_error($connection),
                $query
            ));
        }

        return $result;
    }

    private function assertInstallerDatabaseNameIsSafe($databaseName)
    {
        if (stripos($databaseName, 'installer') === false)
        {
            throw new RuntimeException(sprintf(
                'Refusing to reset installer database "%s" because the name does not contain "installer".',
                $databaseName
            ));
        }
    }

    private function escapeIdentifier($identifier)
    {
        return str_replace('`', '``', $identifier);
    }

    private function waitForVisibleElement($id, $timeout = 30000)
    {
        $escapedId = json_encode($id);
        $this->getSession()->wait($timeout, sprintf(
            '(function () {
                var element = document.getElementById(%s);
                if (!element) {
                    return false;
                }
                while (element) {
                    var style = window.getComputedStyle(element);
                    if (style.display === "none" || style.visibility === "hidden") {
                        return false;
                    }
                    element = element.parentElement;
                }
                return true;
            })()',
            $escapedId
        ));

        if (!$this->isElementVisible($id))
        {
            throw new ExpectationException(sprintf('Element "%s" did not become visible.', $id), $this->getSession());
        }
    }

    private function isElementVisible($id)
    {
        return (bool) $this->getSession()->evaluateScript(sprintf(
            '(function () {
                var element = document.getElementById(%s);
                if (!element) {
                    return false;
                }
                while (element) {
                    var style = window.getComputedStyle(element);
                    if (style.display === "none" || style.visibility === "hidden") {
                        return false;
                    }
                    element = element.parentElement;
                }
                return true;
            })()',
            json_encode($id)
        ));
    }

    private function clickVisibleButtonIn($containerId, $buttonValue)
    {
        $script = sprintf(
            '(function () {
                var container = document.getElementById(%s);
                if (!container) {
                    return false;
                }
                var buttons = container.querySelectorAll("input[type=button], button");
                for (var i = 0; i < buttons.length; i++) {
                    if (buttons[i].value === %s || buttons[i].textContent.trim() === %s) {
                        buttons[i].click();
                        return true;
                    }
                }
                return false;
            })()',
            json_encode($containerId),
            json_encode($buttonValue),
            json_encode($buttonValue)
        );

        $clicked = $this->getSession()->evaluateScript($script);
        if (!$clicked)
        {
            throw new ExpectationException(sprintf(
                'Button "%s" was not found in "%s".',
                $buttonValue,
                $containerId
            ), $this->getSession());
        }
    }

    private function assertPageDoesNotContainInstallerError()
    {
        $text = $this->getSession()->getPage()->getText();
        $unexpectedText = array(
            'Error Connecting to Database',
            'Error Selecting Database',
            'Query Error -- Please Report This Bug',
            'One or more tests failed'
        );

        foreach ($unexpectedText as $needle)
        {
            if (strpos($text, $needle) !== false)
            {
                throw new ExpectationException(sprintf('Installer page contains unexpected error "%s".', $needle), $this->getSession());
            }
        }
    }

    private function getInstallerDatabaseName()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_NAME', 'cats_installer_test');
    }

    private function getInstallerDatabaseUser()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_USER', 'dev');
    }

    private function getInstallerDatabasePassword()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_PASSWORD', $this->getEnv('OPENCATS_INSTALLER_DB_PASS', 'dev'));
    }

    private function getInstallerDatabaseHost()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_HOST', 'opencatsdb');
    }

    private function getInstallerDatabaseHostForWizard()
    {
        $host = $this->getInstallerDatabaseHost();
        $port = $this->getInstallerDatabasePort();

        if ($port === 3306 || strpos($host, ':') !== false)
        {
            return $host;
        }

        return $host . ':' . $port;
    }

    private function getInstallerDatabasePort()
    {
        return (int) $this->getEnv('OPENCATS_INSTALLER_DB_PORT', '3306');
    }

    private function getInstallerDatabaseAdminUser()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_ADMIN_USER', 'root');
    }

    private function getInstallerDatabaseAdminPassword()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_ADMIN_PASS', 'dev');
    }

    private function getInstallerDatabaseAdminHost()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DB_ADMIN_HOST', $this->getInstallerDatabaseHost());
    }

    private function getInstallerMailFromAddress()
    {
        return $this->getEnv('OPENCATS_INSTALLER_MAIL_FROM', 'installer@example.test');
    }

    private function getInstallerDefaultPhoneCountryCode()
    {
        return $this->getEnv('OPENCATS_INSTALLER_DEFAULT_PHONE_COUNTRY_CODE', '1');
    }

    private function getEnv($name, $default)
    {
        $value = getenv($name);
        if ($value !== false && $value !== '')
        {
            return $value;
        }

        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '')
        {
            return $_SERVER[$name];
        }

        if (isset($_ENV[$name]) && $_ENV[$name] !== '')
        {
            return $_ENV[$name];
        }

        return $default;
    }
}
