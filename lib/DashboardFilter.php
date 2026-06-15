<?php
/*
 * CATS
 * Dashboard Filter Helpers
 *
 * Utility class for reading, normalizing, and URL-building for the
 * GET-based advanced filter blocks added to the Companies, Contacts,
 * and Candidates dashboard/list pages.
 *
 * Design notes:
 *  - All filter GET parameters use module-scoped prefixes (dfco_, dfct_,
 *    dfca_) to avoid cross-module interference when the DataGrid navigation
 *    preserves the full query string via _getUnrelatedRequestString().
 *  - No raw request values are ever interpolated into SQL; all values are
 *    escaped through DatabaseConnection before use.
 *  - Multi-value fields (tags) use a comma-separated string so that the
 *    DataGrid's _getUnrelatedRequestString() can pass them through unchanged.
 */

class DashboardFilter
{
    /**
     * Returns a trimmed string from $_GET for the given key, or $default
     * if the key is absent or the trimmed value is empty.
     */
    public static function getString($key, $default = '')
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $val = trim((string) $_GET[$key]);
        return ($val === '') ? $default : $val;
    }

    /**
     * Returns a non-negative integer from $_GET for the given key, or
     * $default if the key is absent or the value is not a positive integer.
     */
    public static function getInt($key, $default = 0)
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $val = (int) $_GET[$key];
        return ($val > 0) ? $val : $default;
    }

    /**
     * Returns a date string in YYYY-MM-DD format from $_GET, or $default
     * if the key is absent or the value does not match the expected format.
     */
    public static function getDate($key, $default = '')
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $val = trim((string) $_GET[$key]);
        if ($val === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            return $default;
        }
        return $val;
    }

    /**
     * Returns an array of positive integers parsed from a comma-separated
     * string in $_GET[$key].  Returns an empty array if the key is absent
     * or contains no valid positive integers.
     *
     * Using a comma-separated string (rather than name[]=) keeps the value
     * as a scalar so that DataGrid::_getUnrelatedRequestString() preserves
     * it correctly across pagination and sort links.
     */
    public static function getIntList($key)
    {
        if (!isset($_GET[$key]) || trim((string) $_GET[$key]) === '') {
            return array();
        }
        $parts  = explode(',', (string) $_GET[$key]);
        $result = array();
        foreach ($parts as $part) {
            $intVal = (int) trim($part);
            if ($intVal > 0 && !in_array($intVal, $result)) {
                $result[] = $intVal;
            }
        }
        return $result;
    }

    /**
     * Returns true if any of the supplied GET keys contains a non-empty
     * value after trimming.  Used to determine whether a filter block is
     * currently active (so the "Clear Filters" link can be shown).
     */
    public static function isActive(array $keys)
    {
        foreach ($keys as $key) {
            if (isset($_GET[$key]) && trim((string) $_GET[$key]) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the URL query string for the "Clear Filters" link for the
     * given module and action.  Only the m= and a= parameters are kept;
     * all df*_ filter parameters are dropped.  DataGrid parameters are
     * also dropped so that the list resets to page 1.
     *
     * @param string $module  e.g. 'companies'
     * @param string $action  e.g. 'listByView'
     * @return string  Full query string, e.g. 'm=companies&a=listByView'
     */
    public static function getClearUrl($module, $action)
    {
        return 'm=' . urlencode($module) . '&a=' . urlencode($action);
    }

    /**
     * Returns true when the current GET request looks like a filter form
     * submission rather than a DataGrid navigation link.
     *
     * Specifically, returns true when at least one key with $prefix is
     * present in GET AND the DataGrid's own parameter key ($dgParamKey)
     * is absent.  When the DataGrid generates navigation links it always
     * includes its own parameters key, so its absence means the request
     * came from the filter form.
     *
     * Use this in listByView() to reset rangeStart to 0 on filter change:
     *
     *   if (DashboardFilter::isFilterFormSubmission('dfco_', 'parameterscompanies:CompaniesListByViewDataGrid')) {
     *       $dataGridProperties['rangeStart'] = 0;
     *   }
     *
     * @param string $prefix      Module-scoped GET key prefix, e.g. 'dfco_'
     * @param string $dgParamKey  DataGrid parameters key, e.g.
     *                            'parameterscompanies:CompaniesListByViewDataGrid'
     * @return bool
     */
    public static function isFilterFormSubmission($prefix, $dgParamKey)
    {
        if (isset($_GET[$dgParamKey])) {
            return false;
        }
        foreach ($_GET as $key => $value) {
            if (strncmp($key, $prefix, strlen($prefix)) === 0
                && trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }
}
?>
