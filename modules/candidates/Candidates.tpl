<?php /* $Id: Candidates.tpl 3445 2007-11-06 23:17:04Z will $ */ ?>
<?php TemplateUtility::printHeader('Candidates', array( 'js/highlightrows.js', 'js/export.js', 'js/dataGrid.js', 'js/dataGridFilters.js')); ?>
<?php TemplateUtility::printHeaderBlock(); ?>
<?php TemplateUtility::printTabs($this->active); ?>
<?php $md5InstanceName = md5($this->dataGrid->getInstanceName());?>
    <style type="text/css">
    div.addCandidateButton { background: #4172E3 url(images/nodata/candidatesButton.jpg); cursor: pointer; width: 337px; height: 67px; }
    div.addCandidateButton:hover { background: #4172E3 url(images/nodata/candidateButton-o.jpg); cursor: pointer; width: 337px; height: 67px; }
    div.addMassImportButton { background: #4172E3 url(images/nodata/addMassImport.jpg); cursor: pointer; width: 337px; height: 67px; }
    div.addMassImportButton:hover { background: #4172E3 url(images/nodata/addMassImport-o.jpg); cursor: pointer; width: 337px; height: 67px; }
    </style>
    <div id="main">
        <?php TemplateUtility::printQuickSearch(); ?>

        <div id="contents"<?php echo !$this->totalCandidates ? ' style="background-color: #E6EEFF; padding: 0px;"' : ''; ?>>
            <?php if ($this->totalCandidates): ?>
            <table width="100%">
                <tr>
                    <td width="3%">
                        <img src="images/candidate.gif" width="24" height="24" alt="Candidates" style="border: none; margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Candidates: Home</h2></td>
                    <td align="right">
                        <form name="candidatesViewSelectorForm" id="candidatesViewSelectorForm" action="<?php echo(CATSUtility::getIndexName()); ?>" method="get">
                            <input type="hidden" name="m" value="candidates" />
                            <input type="hidden" name="a" value="listByView" />

                            <table class="viewSelector">
                                <tr>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <?php $this->dataGrid->printNavigation(false); ?>
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyMyCandidates" id="onlyMyCandidates" <?php if ($this->dataGrid->getFilterValue('OwnerID') ==  $this->userID): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('OwnerID', '==',  $this->userID); ?>" />
                                        Only My Candidates&nbsp;
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
                                        <input type="checkbox" name="onlyHotCandidates" id="onlyHotCandidates" <?php if ($this->dataGrid->getFilterValue('IsHot') == '1'): ?>checked<?php endif; ?> onclick="<?php echo $this->dataGrid->getJSAddRemoveFilterFromCheckbox('IsHot', '==', '\'1\''); ?>" />
                                        <label for="onlyHotCandidates">Only Hot Candidates</label>&nbsp;
                                    </td>
                                    <td valign="top" align="right" nowrap="nowrap">
	                					<a href="javascript:void(0);" id="exportBoxLink<?= $md5InstanceName ?>" onclick="toggleHideShowControls('<?= $md5InstanceName ?>-tags'); return false;">Filter by tag</a>
	                					<div id="tagsContainer" style="position:relative">
	                					<div class="ajaxSearchResults" id="ColumnBox<?= $md5InstanceName ?>-tags" align="left"  style="position:absolute;width:200px;right:0<?= isset($this->globalStyle)?$this->globalStyle:"" ?>">
	                						<table width="100%"><tr><td style="font-weight:bold; color:#000000;">Tag list</td>
	                						<td align="right">
	                							<input type="button" onclick="applyTagFilter()" value="Save&amp;Close" />
	                							<input type="button" onclick="document.getElementById('ColumnBox<?= $md5InstanceName?>').style.display='none';" value="Close" />
	                						</td>
	                						</tr></table>


	                                        <ul>
	                                        <script type="text/javascript">
	                                        function applyTagFilter(){
	                                        	var arrValues=[];
	                                        	var tags=document.getElementsByName('candidate_tags[]');
	                                        	for(var el in tags){
	                                        		if (tags[el].checked) arrValues.push(tags[el].value);
	                                        	};

	                                        	<?php echo $this->dataGrid->getJSAddFilter('Tags', '=#',  "arrValues.join('/')")?>;
	                                        }
	                                        </script>
											<?php $i=1;

											function drw($data, $id){
												global $i;
												foreach($data as $k => $v){
													if ($v['tag_parent_id'] == $id){
														?><li><input type="checkbox" name="candidate_tags[]" id="checkbox<?= $i ?>" value="<?= $v['tag_id'] ?>"><label for="checkbox<?= $i++ ?>"><?= $v['tag_title'] ?></label></li><?php
														echo "\n<ul>";
														drw($data, $v['tag_id']);
														echo "\n</ul>";
													}
												}
											}
											drw($this->tagsRS, '');
											?></ul>
	                					</div>
	                					</div>
										<span style="display:none;" id="ajaxTableIndicator<?= $md5InstanceName ?>"><img src="images/indicator_small.gif" alt="" /></span>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            </table>

            <?php if ($this->topLog != ''): ?>
            <div style="margin: 20px 0px 20px 0px;">
                <?php echo $this->topLog; ?>
            </div>
            <?php endif; ?>

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
                <legend class="filterAreaLegend">Filter Candidates</legend>
                <form method="get" action="<?php echo htmlspecialchars(CATSUtility::getIndexName()); ?>">
                    <input type="hidden" name="m" value="candidates" />
                    <input type="hidden" name="a" value="listByView" />
                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_first_name">First Name:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_first_name" name="dfca_first_name"
                                    value="<?php echo htmlspecialchars($this->dfca['first_name']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_last_name">Last Name:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_last_name" name="dfca_last_name"
                                    value="<?php echo htmlspecialchars($this->dfca['last_name']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_email">Email:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_email" name="dfca_email"
                                    value="<?php echo htmlspecialchars($this->dfca['email']); ?>"
                                    style="width: 130px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_phone">Phone:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_phone" name="dfca_phone"
                                    value="<?php echo htmlspecialchars($this->dfca['phone']); ?>"
                                    style="width: 110px;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_city">City:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_city" name="dfca_city"
                                    value="<?php echo htmlspecialchars($this->dfca['city']); ?>"
                                    style="width: 100px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_state">State:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_state" name="dfca_state"
                                    value="<?php echo htmlspecialchars($this->dfca['state']); ?>"
                                    style="width: 60px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_key_skills">Key Skills:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_key_skills" name="dfca_key_skills"
                                    value="<?php echo htmlspecialchars($this->dfca['key_skills']); ?>"
                                    style="width: 130px;" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_source">Source:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_source" name="dfca_source"
                                    value="<?php echo htmlspecialchars($this->dfca['source']); ?>"
                                    style="width: 110px;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_owner">Owner:</label></td>
                            <td style="padding: 3px 6px;">
                                <select id="dfca_owner" name="dfca_owner" class="selectBox">
                                    <option value="0">Any</option>
                                    <?php foreach ($this->usersRS as $u): ?>
                                    <option value="<?php echo (int)$u['userID']; ?>"<?php if ($this->dfca['owner'] === (int)$u['userID']): ?> selected="selected"<?php endif; ?>>
                                        <?php echo htmlspecialchars($u['firstName'] . ' ' . $u['lastName']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_created_from">Created:</label></td>
                            <td style="padding: 3px 6px;" colspan="3">
                                <input type="text" class="inputbox" id="dfca_created_from" name="dfca_created_from"
                                    value="<?php echo htmlspecialchars($this->dfca['created_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfca_created_to" name="dfca_created_to"
                                    value="<?php echo htmlspecialchars($this->dfca['created_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                            <td style="padding: 3px 6px; white-space: nowrap;"><label for="dfca_modified_from">Modified:</label></td>
                            <td style="padding: 3px 6px;">
                                <input type="text" class="inputbox" id="dfca_modified_from" name="dfca_modified_from"
                                    value="<?php echo htmlspecialchars($this->dfca['modified_from']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                                &ndash;
                                <input type="text" class="inputbox" id="dfca_modified_to" name="dfca_modified_to"
                                    value="<?php echo htmlspecialchars($this->dfca['modified_to']); ?>"
                                    style="width: 90px;" placeholder="YYYY-MM-DD" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 6px;" colspan="8">
                                <label>
                                    <input type="checkbox" name="dfca_is_hot" value="1"<?php if ($this->dfca['is_hot'] === 1): ?> checked="checked"<?php endif; ?> />
                                    Hot Candidates only
                                </label>
                                &nbsp;&nbsp;
                                <input type="submit" class="button" value="Apply Filters" />
                                <?php if ($this->filterActive): ?>
                                &nbsp;<a href="<?php echo htmlspecialchars(CATSUtility::getIndexName() . '?' . DashboardFilter::getClearUrl('candidates', 'listByView')); ?>">Clear Filters</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </fieldset>

            <p class="note">
                <span style="float:left;">Candidates - Page <?php echo($this->dataGrid->getCurrentPageHTML()); ?> (<?php echo($this->dataGrid->getNumberOfRows()); ?> Items)</span>
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
            <div style="height: 95px; background: #E6EEFF url(images/nodata/candidatesTop.jpg);">
                &nbsp;
            </div>
            <br /><br />
                <?php if ($this->getUserAccessLevel('candidates.add') >= ACCESS_LEVEL_EDIT): ?>
            <table cellpadding="0" cellspacing="0" border="0" width="956">
                <tr>
                <td style="padding-left: 62px;" align="center" valign="center">

                    <div style="text-align: center; width: 600px; line-height: 22px; font-size: 18px; font-weight: bold; color: #666666; padding-bottom: 20px;">
                    Add candidates to keep track of possible applicants you can consider for your job orders.
                    </div>

                    <table cellpadding="10" cellspacing="0" border="0">
                        <tr>
                            <td style="padding-right: 20px;">
                                <a href="<?php echo CATSUtility::getIndexName(); ?>?m=candidates&amp;a=add">
                                <div class="addCandidateButton">&nbsp;</div>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo CATSUtility::getIndexName(); ?>?m=import&amp;a=massImport">
                                <div class="addMassImportButton">&nbsp;</div>
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>

                </tr>
            </table>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
<?php TemplateUtility::printFooter(); ?>
