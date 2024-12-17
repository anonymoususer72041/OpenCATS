<?php
// Candidate merge duplicates template

TemplateUtility::printModalHeader('Candidates', array(), 'Select information to keep in merge duplicates');

if (!$this->isFinishedMode): ?>
    <table class="searchTable">
    <form id="chooseMergeInformation" name="chooseMergeInformationForm" action="<?php echo(CATSUtility::getIndexName()); ?>?m=candidates&amp;a=mergeInfo" method="post">
    <input type="hidden" id="oldCandidateID" name="oldCandidateID" value="<?php echo isset($this->oldCandidateID) ? $this->oldCandidateID : ''; ?>" />
    <input type="hidden" id="newCandidateID" name="newCandidateID" value="<?php echo isset($this->newCandidateID) ? $this->newCandidateID : ''; ?>" />

    <!-- First Name Section -->
    <tr><td colspan=4 style="text-align:center;">First Name&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['firstName']) ? $this->rsOld['firstName'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="firstName" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="firstName" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['firstName']) ? $this->rsNew['firstName'] : '(none)'; ?></td>
    </tr>

    <!-- Middle Name Section -->
    <tr><td colspan=4 style="text-align:center;">Middle Name&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['middleName']) ? $this->rsOld['middleName'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="middleName" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="middleName" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['middleName']) ? $this->rsNew['middleName'] : '(none)'; ?></td>
    </tr>

    <!-- Last Name Section -->
    <tr><td colspan=4 style="text-align:center;">Last Name&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['lastName']) ? $this->rsOld['lastName'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="lastName" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="lastName" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['lastName']) ? $this->rsNew['lastName'] : '(none)'; ?></td>
    </tr>

    <!-- Email Section -->
    <tr><td colspan=4 style="text-align:center;">E-mails (max. 2)&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['email1']) && $this->rsOld['email1'] !== '' ? $this->rsOld['email1'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="checkbox" name="email[]" value="<?php echo isset($this->rsOld['email1']) && $this->rsOld['email1'] !== '' ? $this->rsOld['email1'] : ''; ?>" onclick="return keepCount('email')"/></td>
    <td style="text-align:center;"><input type="checkbox" name="email[]" value="<?php echo isset($this->rsNew['email1']) && $this->rsNew['email1'] !== '' ? $this->rsNew['email1'] : ''; ?>" onclick="return keepCount('email')" checked/></td>
    <td align="left"><?php echo isset($this->rsNew['email1']) && $this->rsNew['email1'] !== '' ? $this->rsNew['email1'] : '(none)'; ?></td>
    </tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['email2']) && $this->rsOld['email2'] !== '' ? $this->rsOld['email2'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="checkbox" name="email[]" value="<?php echo isset($this->rsOld['email2']) && $this->rsOld['email2'] !== '' ? $this->rsOld['email2'] : ''; ?>" onclick="return keepCount('email')"/></td>
    <td style="text-align:center;"><input type="checkbox" name="email[]" value="<?php echo isset($this->rsNew['email2']) && $this->rsNew['email2'] !== '' ? $this->rsNew['email2'] : ''; ?>" onclick="return keepCount('email')" checked/></td>
    <td align="left"><?php echo isset($this->rsNew['email2']) && $this->rsNew['email2'] !== '' ? $this->rsNew['email2'] : '(none)'; ?></td>
    </tr>

    <!-- Cell Phone Section -->
    <tr><td colspan=4 style="text-align:center;">Cell phone&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['phoneCell']) && $this->rsOld['phoneCell'] !== '' ? $this->rsOld['phoneCell'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="phoneCell" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="phoneCell" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['phoneCell']) && $this->rsNew['phoneCell'] !== '' ? $this->rsNew['phoneCell'] : '(none)'; ?></td>
    </tr>

    <!-- Home Phone Section -->
    <tr><td colspan=4 style="text-align:center;">Home phone&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['phoneHome']) && $this->rsOld['phoneHome'] !== '' ? $this->rsOld['phoneHome'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="phoneHome" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="phoneHome" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['phoneHome']) && $this->rsNew['phoneHome'] !== '' ? $this->rsNew['phoneHome'] : '(none)'; ?></td>
    </tr>

    <!-- Work Phone Section -->
    <tr><td colspan=4 style="text-align:center;">Work phone&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['phoneWork']) && $this->rsOld['phoneWork'] !== '' ? $this->rsOld['phoneWork'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="phoneWork" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="phoneWork" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['phoneWork']) && $this->rsNew['phoneWork'] !== '' ? $this->rsNew['phoneWork'] : '(none)'; ?></td>
    </tr>

    <!-- Website Section -->
    <tr><td colspan=4 style="text-align:center;">Website&nbsp;</td></tr>
    <tr>
    <td align="right"><?php echo isset($this->rsOld['webSite']) && $this->rsOld['webSite'] !== '' ? $this->rsOld['webSite'] : '(none)'; ?></td>
    <td style="text-align:center;"><input type="radio" name="website" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="website" value=1 checked/></td>
    <td align="left"><?php echo isset($this->rsNew['webSite']) && $this->rsNew['webSite'] !== '' ? $this->rsNew['webSite'] : '(none)'; ?></td>
    </tr>

    <!-- Address Section -->
    <tr><td colspan=4 style="text-align:center;">Address&nbsp;</td></tr>
    <tr>
    <?php if (empty($this->rsOld['address']) && empty($this->rsOld['city']) && empty($this->rsOld['state']) && empty($this->rsOld['zip'])): ?>
    <td align="right"><?php echo "(none)"; ?></td>
    <?php else: ?>
    <td align="right"><?php echo htmlspecialchars(($this->rsOld['address'] ?? '').'<br/>'.($this->rsOld['city'] ?? '').' '.($this->rsOld['zip'] ?? '').'<br/>'.($this->rsOld['state'] ?? '')); ?></td>
    <?php endif; ?>
    <td style="text-align:center;"><input type="radio" name="address" value=0 /></td>
    <td style="text-align:center;"><input type="radio" name="address" value=1 checked/></td>
    <?php if (empty($this->rsNew['address']) && empty($this->rsNew['city']) && empty($this->rsNew['state']) && empty($this->rsNew['zip'])): ?>
    <td align="left"><?php echo "(none)"; ?></td>
    <?php else: ?>
    <td align="left"><?php echo htmlspecialchars(($this->rsNew['address'] ?? '').'<br/>'.($this->rsNew['city'] ?? '').' '.($this->rsNew['zip'] ?? '').'<br/>'.($this->rsNew['state'] ?? '')); ?></td>
    <?php endif; ?>
    </tr>

    <tr><td colspan=4 style="text-align:center;"><input type="submit" class="button" id="mergeInfo" name="mergeInfo" value="Merge" /></td></tr>
    <tr><td>&nbsp;</td></tr>
    </form>
    </table>
    <?php else: ?>
    <p>These candidates have been successfully merged.</p>
    <form method="get" action="<?php echo(CATSUtility::getIndexName()); ?>">
    <input type="button" name="close" value="Close" onclick="parentHidePopWinRefresh();" />
    </form>
    <?php endif; ?>
