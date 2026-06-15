<?php /* $Id: Companies.tpl 3460 2007-11-07 03:50:34Z brian $ */ ?>
<?php TemplateUtility::printHeader('Companies', array('js/highlightrows.js', 'js/export.js', 'js/dataGrid.js', 'js/dataGridFilters.js')); ?>
<?php TemplateUtility::printHeaderBlock(); ?>
<?php TemplateUtility::printTabs($this->active); ?>
    <style type="text/css">
    div.addCompaniesButton { background: #4172E3 url(images/nodata/companiesButton.jpg); cursor: pointer; width: 337px; height: 67px; }
    div.addCompaniesButton:hover { background: #4172E3 url(images/nodata/companiesButton-o.jpg); cursor: pointer; width: 337px; height: 67px; }
    </style>
    <div id="main">
        <?php TemplateUtility::printQuickSearch(); ?>

        <div id="contents">
            <table width="100%">
                <tr>
                    <td width="3%">
                        <img src="images/companies.gif" width="24" height="24" border="0" alt="Companies" style="margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Companies: Home</h2></td>
                    <td align="right">
                        <form name="companiesViewSelectorForm" id="companiesViewSelectorForm" action="<?php echo(CATSUtility::getIndexName()); ?>" method="get">
                            <input type="hidden" name="m" value="companies" />
                            <input type="hidden" name="a" value="listByView" />
                            <table class="viewSelector">
                                <tr>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <?php $this->dataGrid->printNavigation(false); ?>
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyMyCompanies" id="onlyMyCompanies" <?php if ($this->dataGrid->getFilterValue('OwnerID') ==  $this->userID): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('OwnerID', '==',  $this->userID); ?>" />
                                        <label for="onlyMyCompanies">Only My Companies</label>&nbsp;
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyHotCompanies" id="onlyHotCompanies" <?php if ($this->dataGrid->getFilterValue('IsHot') == '1'): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('IsHot', '==', '\'1\''); ?>" />
                                        <label for="onlyHotCompanies">Only Hot Companies</label>&nbsp;
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            </table>

            <?php if ($this->errMessage != ''): ?>
            <div id="errorMessage" style="padding: 25px 0px 25px 0px; border-top: 1px solid #800000; border-bottom: 1px solid #800000; background-color: #f7f7f7;margin-bottom: 15px;">
            <table>
                <tr>
                    <td align="left" valign="center" style="padding-right: 5px;">
                        <img src="images/large_error.gif" align="left">
                    </td>
                    <td align="left" valign="center">
                        <span style="font-size: 12pt; font-weight: bold; color: #800000; line-height: 12pt;">There was a problem with your request:</span>
                        <div style="font-size: 10pt; font-weight: bold; padding: 3px 0px 0px 0px;"><?php echo $this->errMessage; ?></div>
                    </td>
                </tr>
            </table>
            </div>
            <?php endif; ?>

            <fieldset class="filterAreaFieldSet">
                <legend class="filterAreaLegend">Filter Companies</legend>
                <form method="get" action="<?php echo htmlspecialchars(CATSUtility::getIndexName()); ?>">
                    <input type="hidden" name="m" value="companies" />
                    <input type="hidden" name="a" value="listByView" />
                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_name">Name:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfco_name" name="dfco_name"
                                    value="<?php echo htmlspecialchars($this->dfco['name']); ?>"
                                    style="width: 140px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_city">City:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfco_city" name="dfco_city"
                                    value="<?php echo htmlspecialchars($this->dfco['city']); ?>"
                                    style="width: 110px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_state">State:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfco_state" name="dfco_state"
                                    value="<?php echo htmlspecialchars($this->dfco['state']); ?>"
                                    style="width: 60px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_phone">Phone:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfco_phone" name="dfco_phone"
                                    value="<?php echo htmlspecialchars($this->dfco['phone']); ?>"
                                    style="width: 110px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_website">Website:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfco_website" name="dfco_website"
                                    value="<?php echo htmlspecialchars($this->dfco['website']); ?>"
                                    style="width: 110px;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_owner">Owner:</label></td>
                            <td style="padding: 3px 6px;">
                                <select id="dfco_owner" name="dfco_owner" class="selectBox">
                                    <option value="0">Any</option>
                                    <?php foreach ($this->usersRS as $u): ?>
                                    <option value="<?php echo (int)$u['userID']; ?>"<?php if ($this->dfco['owner'] === (int)$u['userID']): ?> selected="selected"<?php endif; ?>>
                                        <?php echo htmlspecialchars($u['firstName'] . ' ' . $u['lastName']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_created_from">Created:</label></td>
                            <td style="padding: 3px 6px;" colspan="3">
                                <input type="text" class="inputbox" id="dfco_created_from" name="dfco_created_from"
                                    value="<?php echo htmlspecialchars($this->dfco['created_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfco_created_to" name="dfco_created_to"
                                    value="<?php echo htmlspecialchars($this->dfco['created_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfco_modified_from">Modified:</label></td>
                            <td style="padding: 3px 6px;" colspan="3">
                                <input type="text" class="inputbox" id="dfco_modified_from" name="dfco_modified_from"
                                    value="<?php echo htmlspecialchars($this->dfco['modified_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfco_modified_to" name="dfco_modified_to"
                                    value="<?php echo htmlspecialchars($this->dfco['modified_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px;" colspan="10">
                                <label>
                                    <input type="checkbox" name="dfco_is_hot" value="1"<?php if ($this->dfco['is_hot'] === 1): ?> checked="checked"<?php endif; ?> />
                                    Hot Companies only
                                </label>
                                &nbsp;&nbsp;
                                <input type="submit" class="button" value="Apply Filters" />
                                <?php if ($this->filterActive): ?>
                                &nbsp;<a href="<?php echo htmlspecialchars(CATSUtility::getIndexName() . '?' . DashboardFilter::getClearUrl('companies', 'listByView')); ?>">Clear Filters</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </fieldset>

            <p class="note">
                <span style="float:left;">Companies  -
                    Page <?php echo($this->dataGrid->getCurrentPageHTML()); ?>
                    (<?php echo($this->dataGrid->getNumberOfRows()); ?> Items)
                    <?php if ($this->dataGrid->getFilterValue('OwnerID') ==  $this->userID): ?>(Only My Companies)<?php endif; ?>
                    <?php if ($this->dataGrid->getFilterValue('IsHot') == '1'): ?>(Only Hot Companies)<?php endif; ?>
                </span>
                <span style="float:right;">
                    <?php $this->dataGrid->drawRowsPerPageSelector(); ?>
                    <?php $this->dataGrid->drawShowFilterControl(); ?>
                </span>&nbsp;
            </p>

            <?php $this->dataGrid->drawFilterArea(); ?>
            <?php $this->dataGrid->draw();  ?>

            <div style="display:block;">
                <span style="float:left;">
                    <?php $this->dataGrid->printActionArea(); ?>&nbsp;
                </span>
                <span style="float:right;">
                    <?php $this->dataGrid->printNavigation(true); ?>
                </span>&nbsp;
            </div>
        </div>
    </div>
<?php TemplateUtility::printFooter(); ?>
