<?php /* $Id: Error.tpl 1930 2007-02-22 08:39:53Z will $ */ ?>
<?php (new TemplateUtility())->printHeader('Calendar'); ?>
<?php (new TemplateUtility())->printHeaderBlock(); ?>
<?php (new TemplateUtility())->printTabs($this->active); ?>
    <div id="main">
        <?php (new TemplateUtility())->printQuickSearch(); ?>

        <div id="contents">
            <table>
                <tr>
                    <td width="3%">
                        <img src="images/calendar.gif" width="24" height="24" alt="Calendar" style="border: none; margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>Calendar</h2></td>
                </tr>
            </table>

            <p class="fatalError">
                A fatal error has occurred.<br />
                <br />
                <?php $this->_($this->errorMessage); ?>
            </p>
        </div>
    </div>
<?php (new TemplateUtility())->printFooter(); ?>
