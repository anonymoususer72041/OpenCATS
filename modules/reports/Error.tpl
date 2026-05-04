<?php /* $Id: Error.tpl 3078 2007-09-21 20:25:28Z will $ */ ?>
<?php (new TemplateUtility())->printHeader('Reports'); ?>
<?php (new TemplateUtility())->printHeaderBlock(); ?>
<?php (new TemplateUtility())->printTabs($this->active); ?>

    <div id="main">
        <?php (new TemplateUtility())->printQuickSearch(); ?>

        <div id="contents">
            <table>
                <tr>
                    <td width="3%">
                        <img src="images/reports.gif" width="24" height="24" border="0" alt="Reports" style="margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Reports: Error</h2></td>
                </tr>
            </table>

            <p class="fatalError">
                A fatal error has occurred.<br />
                <br />
                <?php echo($this->errorMessage); ?>
            </p>
        </div>
    </div>
<?php (new TemplateUtility())->printFooter(); ?>
