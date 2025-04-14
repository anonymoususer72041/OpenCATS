<?php /* $Id: Backup.tpl 3582 2007-11-12 22:58:48Z brian $ */ ?>
<?php TemplateUtility::printHeader('Settings', array('js/backup.js')); ?>
<?php TemplateUtility::printHeaderBlock(); ?>
<?php TemplateUtility::printTabs($this->active, $this->subActive); ?>
<?php require_once __DIR__ . '/../../config.php'; ?>
<div id="main">
<?php TemplateUtility::printQuickSearch(); ?>
<div id="contents">
<table>
<tr>
<td width="3%">
<img src="images/settings.gif" width="24" height="24" border="0" alt="Settings" style="margin-top: 3px;" />&nbsp;
</td>
<td><h2>Settings: Site Backup</h2></td>
</tr>
</table>
<p class="note">Create Site Backup</p>
<table class="searchTable" width="100%">
<tr>
<td>
Create a backup of your entire CATS database and attachments.<br />
Note: Multiple backups are now supported.<br /><br />
</td>
</tr>
<tr>
<td>
<span id="backupRunning" style="display:none;">
Backing up database, please wait...
<br /><br />
Status:<br />
</span>
<span id="progressHistory"></span>
<span id="progress">
<table class="attachmentsTable" id="backupList">
<tr>
<th>Database Backup</th>
<th>Attachments Backup</th>
<th>Timestamp</th>
<th>Actions</th>
</tr>
</table>
<br />
<input type="button" class="button" value="Create Full System Backup" onclick="startBackup();" style="margin:3px; width:200px;">
</span>
<span id="progressBar" style="display:none;"></span>
</td>
</tr>
</table>
</div>
</div>
<?php TemplateUtility::printFooter(); ?>
<script type="text/javascript">
function startBackup() {
    fetch('<?php echo rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/"); ?>/modules/settings/ajax/backup.php?a=backup')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Backup created successfully!');
            loadBackupList();
        } else {
            alert('Backup failed!');
        }
    });
}

function loadBackupList() {
    fetch('<?php echo rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/"); ?>/modules/settings/ajax/backup.php?a=list')
    .then(response => response.json())
    .then(data => {
        let backupList = document.getElementById('backupList');
        backupList.innerHTML = '<tr><th>Database Backup</th><th>Attachments Backup</th><th>Timestamp</th><th>Actions</th></tr>';
    data.forEach(backup => {
        backupList.innerHTML += `<tr>
        <td><a href='backups/${backup.database}'>${backup.database}</a></td>
        <td>${backup.attachments !== 'N/A' ? `<a href='backups/${backup.attachments}'>${backup.attachments}</a>` : 'No attachments'}</td>
        <td>${backup.timestamp}</td>
        <td><button onclick="deleteBackup('${backup.timestamp}')">Delete</button></td>
        </tr>`;
    });
    });
}

function deleteBackup(timestamp) {
    if (confirm('Are you sure you want to delete this backup?')) {
        fetch(`ajax/backup.php?a=delete&timestamp=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            loadBackupList();
        });
    }
}

document.addEventListener("DOMContentLoaded", loadBackupList);
</script>
