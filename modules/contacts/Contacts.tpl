<?php /* $Id: Contacts.tpl 3430 2007-11-06 20:44:51Z will $ */ ?>
<?php TemplateUtility::printHeader('Contacts', array('js/highlightrows.js', 'js/export.js', 'js/dataGrid.js', 'js/dataGridFilters.js')); ?>
<?php TemplateUtility::printHeaderBlock(); ?>
<?php TemplateUtility::printTabs($this->active); ?>
    <style type="text/css">
    div.addContactsButton { background: #4172E3 url(images/nodata/contactsButton.jpg); cursor: pointer; width: 337px; height: 67px; }
    div.addContactsButton:hover { background: #4172E3 url(images/nodata/contactsButton-o.jpg); cursor: pointer; width: 337px; height: 67px; }
    </style>
    <div id="main">
        <?php TemplateUtility::printQuickSearch(); ?>

        <div id="contents"<?php echo !$this->totalContacts ? ' style="background-color: #E6EEFF; padding: 0px;"' : ''; ?>>
            <?php if ($this->totalContacts): ?>
            <table width="100%">
                <tr>
                    <td width="3%">
                        <img src="images/contact.gif" width="24" height="24" border="0" alt="Contacts" style="margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Contacts: Home</h2></td>
                    <td align="right">
                        <form name="contactsViewSelectorForm" id="contactsViewSelectorForm" action="<?php echo(CATSUtility::getIndexName()); ?>" method="get">
                            <input type="hidden" name="m" value="contacts" />
                            <input type="hidden" name="a" value="listByView" />

                            <table class="viewSelector">
                                <tr>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <?php $this->dataGrid->printNavigation(false); ?>
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyMyCompanies" id="onlyMyContacts" <?php if ($this->dataGrid->getFilterValue('OwnerID') ==  $this->userID): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('OwnerID', '==',  $this->userID); ?>" />
                                        <label for="onlyMyContacts">Only My Contacts</label>&nbsp;
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyHotCompanies" id="onlyHotContacts" <?php if ($this->dataGrid->getFilterValue('IsHot') == '1'): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('IsHot', '==', '\'1\''); ?>" />
                                        <label for="onlyHotContacts">Only Hot Contacts</label>&nbsp;
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
                <legend class="filterAreaLegend">Filter Contacts</legend>
                <form method="get" action="<?php echo htmlspecialchars(CATSUtility::getIndexName()); ?>">
                    <input type="hidden" name="m" value="contacts" />
                    <input type="hidden" name="a" value="listByView" />
                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_first_name">First Name:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_first_name" name="dfct_first_name"
                                    value="<?php echo htmlspecialchars($this->dfct['first_name']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_last_name">Last Name:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_last_name" name="dfct_last_name"
                                    value="<?php echo htmlspecialchars($this->dfct['last_name']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_company">Company:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_company" name="dfct_company"
                                    value="<?php echo htmlspecialchars($this->dfct['company']); ?>"
                                    style="width: 120px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_title">Title:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_title" name="dfct_title"
                                    value="<?php echo htmlspecialchars($this->dfct['title']); ?>"
                                    style="width: 120px;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_email">Email:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_email" name="dfct_email"
                                    value="<?php echo htmlspecialchars($this->dfct['email']); ?>"
                                    style="width: 130px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_phone">Phone:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_phone" name="dfct_phone"
                                    value="<?php echo htmlspecialchars($this->dfct['phone']); ?>"
                                    style="width: 110px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_city">City:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_city" name="dfct_city"
                                    value="<?php echo htmlspecialchars($this->dfct['city']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_state">State:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfct_state" name="dfct_state"
                                    value="<?php echo htmlspecialchars($this->dfct['state']); ?>"
                                    style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_owner">Owner:</label></td>
                            <td style="padding: 3px 6px;">
                                <select id="dfct_owner" name="dfct_owner" class="selectBox">
                                    <option value="0">Any</option>
                                    <?php foreach ($this->usersRS as $u): ?>
                                    <option value="<?php echo (int)$u['userID']; ?>"<?php if ($this->dfct['owner'] === (int)$u['userID']): ?> selected="selected"<?php endif; ?>>
                                        <?php echo htmlspecialchars($u['firstName'] . ' ' . $u['lastName']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_created_from">Created:</label></td>
                            <td style="padding: 3px 6px;" colspan="3">
                                <input type="text" class="inputbox" id="dfct_created_from" name="dfct_created_from"
                                    value="<?php echo htmlspecialchars($this->dfct['created_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfct_created_to" name="dfct_created_to"
                                    value="<?php echo htmlspecialchars($this->dfct['created_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfct_modified_from">Modified:</label></td>
                            <td style="padding: 3px 6px;" colspan="1">
                                <input type="text" class="inputbox" id="dfct_modified_from" name="dfct_modified_from"
                                    value="<?php echo htmlspecialchars($this->dfct['modified_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfct_modified_to" name="dfct_modified_to"
                                    value="<?php echo htmlspecialchars($this->dfct['modified_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px;" colspan="8">
                                <label>
                                    <input type="checkbox" name="dfct_is_hot" value="1"<?php if ($this->dfct['is_hot'] === 1): ?> checked="checked"<?php endif; ?> />
                                    Hot Contacts only
                                </label>
                                &nbsp;&nbsp;
                                <input type="submit" class="button" value="Apply Filters" />
                                <?php if ($this->filterActive): ?>
                                &nbsp;<a href="<?php echo htmlspecialchars(CATSUtility::getIndexName() . '?' . DashboardFilter::getClearUrl('contacts', 'listByView')); ?>">Clear Filters</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </fieldset>

            <p class="note">
                <span style="float:left;">
                    Contacts - Page <?php echo($this->dataGrid->getCurrentPageHTML()); ?>
                    (<?php echo($this->dataGrid->getNumberOfRows()); ?> Items)
                    <?php if ($this->dataGrid->getFilterValue('OwnerID') ==  $this->userID): ?>(Only My Contacts)<?php endif; ?>
                    <?php if ($this->dataGrid->getFilterValue('IsHot') == '1'): ?>(Only Hot Contacts)<?php endif; ?>
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
                    <?php $this->dataGrid->printActionArea(); ?>
                </span>
                <span style="float:right;">
                    <?php $this->dataGrid->printNavigation(true); ?>
                </span>&nbsp;
            </div>

            <?php else: ?>

            <br /><br /><br /><br />
            <div style="height: 95px; background: #E6EEFF url(images/nodata/contactsTop.jpg);">
                &nbsp;
            </div>
            <br /><br />
                <?php if ($this->getUserAccessLevel('contacts.add') >= ACCESS_LEVEL_EDIT): ?>
            <table cellpadding="0" cellspacing="0" border="0" width="956">
                <tr>
                <td style="padding-left: 62px;" align="center" valign="center">

                    <div style="text-align: center; width: 600px; line-height: 22px; font-size: 18px; font-weight: bold; color: #666666; padding-bottom: 20px;">
                    Add contacts to keep track of people you work with.
                    </div>

                    <a href="<?php echo CATSUtility::getIndexName(); ?>?m=contacts&amp;a=add">
                    <div class="addContactsButton">&nbsp;</div>
                    </a>
                </td>

                </tr>
            </table>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
<?php TemplateUtility::printFooter(); ?>
