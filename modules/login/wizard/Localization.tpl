<b>Please select your application time zone, display GMT offset, and date format.</b>
<p />
<div id="localizationBeacon" style="display: none;">&nbsp;</div>
<table class="editTable">
    <tr>
        <td style="font-size: 14px;">Application Time Zone</td>
        <td style="padding-bottom: 10px;"><?php TemplateUtility::printApplicationTimeZoneSelect('applicationTimeZone', 'width: 420px;', '', DateUtility::getApplicationTimeZone()); ?></td>
    </tr>

    <tr>
        <td style="font-size: 14px;">Display GMT Offset</td>
        <td style="padding-bottom: 10px;"><?php TemplateUtility::printTimeZoneSelect('timeZone', 'width: 420px;', '', $this->timeZone); ?></td>
    </tr>

    <tr>
        <td style="font-size: 14px;">Date Format</td>
        <td style="font-size: 14px; padding-bottom: 5px;">
            <select id="dateFormat" name="dateFormat" style="width: 150px;">
                <option value="mdy"<?php if (!$this->isDateDMY): ?> selected<?php endif; ?>>MM-DD-YYYY (US)</option>
                <option value="dmy"<?php if ($this->isDateDMY): ?> selected<?php endif; ?>>DD-MM-YYYY (UK)</option>
            </select>
        </td>
    </tr>
</table>
<p />
The application time zone is used for daylight-saving-time-aware conversions. The display GMT offset is retained for compatibility with existing date displays.
