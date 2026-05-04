<?php /* $Id: Error.tpl 3078 2007-09-21 20:25:28Z will $ */ ?>
<?php (new TemplateUtility())->printHeader('Home'); ?>
<?php (new TemplateUtility())->printHeaderBlock(); ?>
<?php (new TemplateUtility())->printTabs($this->active); ?>
    <div id="main">
        <?php (new TemplateUtility())->printQuickSearch(); ?>

        <div id="contents">
            <table>
                <tr>
                    <td width="3%">
                        <img src="images/candidate.gif" width="24" height="24" border="0" alt="Candidates" style="margin-top: 3px;" />&nbsp;
                    </td>
                    <td><h2>CATS: Error</h2></td>
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
