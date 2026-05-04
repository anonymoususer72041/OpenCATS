<?php /* $Id: Error.tpl 770 2006-09-06 19:04:57Z will $ */ ?>
<?php (new TemplateUtility())->printHeader('Fatal Error'); ?>
<?php (new TemplateUtility())->printHeaderBlock(); ?>

<p />
<p class="fatalError">
    A fatal error has occurred.<br />
    <br />
    <?php $this->_($this->errorMessage); ?>
</p>

<?php (new TemplateUtility())->printFooter(); ?>
