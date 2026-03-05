<div id="careerContent">
        <h1>Applying to: <title></h1>
        <div class="applyBoxLeft">
            <div><h3>1. Import Resume (or CV) and Populate Fields</h3></div>
            <table>
                <tr>
                    <td>
                      
                    <input-resumeUploadPreview>
                    </td>
                </tr>
            </table>
            <br />

            <div><h3>2. Tell us about yourself</h3></div>
            <p class="instructions">All fields marked with asterisk (*) are required.</p>
            <table>
                <tr>
                    <td class="label"><label id="firstNameLabel" for="firstName">*First Name:</label></td>
                    <td><input-firstName></td>
                </tr>
                <tr>
                    <td class="label"><label id="lastNameLabel" for="lastName">*Last Name:</label></td>
                    <td><input-lastName></td>
                </tr>
                <tr>
                    <td class="label"><label id="emailLabel" for="email">*Email Adddress:</label></td>
                    <td><input-email></td>
                </tr>
                <tr>
                    <td class="label"><label id="emailConfirmLabel" for="emailconfirm">*Confirm Email:</label></td>
                    <td><input-emailconfirm></td>
                </tr>
            </table>
        </div>
       
        <div class="applyBoxRight">
            <div><h3>3. How may we contact you?</h3></div>
            <table>
                <tr>
                    <td class="label"><label id="homePhoneLabel" for="homePhone">Home Phone:</label></td>
                    <td><input-phone-home></td>
                </tr>
                <tr>
                    <td class="label"><label id="mobilePhoneLabel" for="mobilePhone">Mobile Phone:</label></td>
                    <td><input-phone-cell></td>
                </tr>
                <tr>
                    <td class="label"><label id="workPhoneLabel" for="workPhone">Work Phone:</label></td>
                    <td><input-phone></td>
                </tr>
                <tr>
                    <td class="label"><label id="bestTimeLabel" for="bestTime">*Best time to call:</label></td>
                    <td><input-best-time-to-call></td>
                </tr>
                <tr>
                    <td class="label"><label id="mailingAddressLabel" for="mailingAddress">Mailing Address:</label></td>
                    <td><input-address></td>
                </tr>
                <tr>
                    <td class="label"><label id="cityProvinceLabel" for="cityProvince">*City/Province:</label></td>
                    <td><input-city></td>
                </tr>
                <tr>
                    <td class="label"><label id="stateCountryLabel" for="stateCountry">*State/Country:</label></td>
                    <td><input-state></td>
                </tr>
                <tr>
                    <td class="label"><label id="zipPostalLabel" for="zipPostal">*Zip/Postal Code:</label></td>
                    <td><input-zip></td>
                </tr>
            </table>
            <br />
            <div><h3>4. Additional Information</h3></div>
            <table>
                <tr>
                    <td class="label"><label id="keySkillsLabel" for="keySkills">*Key Skills:</label></td>
                    <td><input-keySkills></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><img src="images/careers_submit.gif" onmouseover="buttonMouseOver('submitApplicationNow',true)" onmouseout="buttonMouseOver('submitApplicationNow',false)" style="cursor: pointer;" id="submitApplicationNow" alt="Submit Application Now" onclick="if (applyValidate()) { document.applyToJobForm.submit(); }" /></td>
                </tr>
            </table>
               </div>
    </div>
