table.sortable
{
text-align:left;
empty-cells: show;
width: 940px;
}
td
{
padding:5px;
}
tr.rowHeading
{
 background: #e0e0e0; border: 1px solid #cccccc; border-left: none; border-right: none;
}
tr.oddTableRow
{
background: #ebebeb; 
}
tr.evenTableRow
{
 background: #ffffff; 
}
a.sortheader:hover,
a.sortheader:link,
a.sortheader:visited
{
color:#000;
}

body, html { margin: 0; padding: 0; background: #ffffff; font: normal 12px/14px Arial, Helvetica, sans-serif; color: #000000; }
#container { margin: 0 auto; padding: 0; width: 940px; height: auto; }
#logo { float: left; margin: 0; }
	#logo img { width: 424px; height: 103px; }
#actions { float: right; margin: 0; width: 310px; height: 100px; background: #efefef; border: 1px solid #cccccc; }
	#actions img { float: left; margin: 2px 6px 2px 15px; width: 130px; height: 25px; }
#footer { clear: both; margin: 20px auto 0 auto; width: 150px; }
	#footer img { width: 137px; height: 38px; }

a:link, a:active { color: #1763b9; }
a:hover { color: #c75a01; }
a:visited { color: #333333; }
img { border: none; }

h1 { margin: 0 0 10px 0; font: bold 18px Arial, Helvetica, sans-serif; }
h2 { margin: 8px 0 8px 15px; font: bold 14px Arial, Helvetica, sans-serif; }
h3 { margin: 0; font: bold 14px Arial, Helvetica, sans-serif; }
p { font: normal 12px Arial, Helvetica, sans-serif; }
p.instructions { margin: 0 0 0 10px; font: italic 12px Arial, Helvetica, sans-serif; color: #666666; }


/* CONTENTS ON PAGE SPECS */
#careerContent { clear: both; padding: 15px 0 0 0; }

	
/* DISPLAY JOB DETAILS */
#detailsTable { width: 400px; }
	#detailsTable td.detailsHeader { width: 30%; }
div#descriptive { float: left; width: 585px; }
div#detailsTools { float: right; padding: 0 0 8px 0; width: 280px; background: #ffffff; border: 1px solid #cccccc; }
	div#detailsTools img { margin: 2px 6px 5px 15px;  }

/* DISPLAY APPLICATION FORM */
div.applyBoxLeft, div.applyBoxRight { width: 450px; height: 470px; background: #f9f9f9; border: 1px solid #cccccc; border-top: none; }
div.applyBoxLeft { float: left; margin: 0 10px 0 0; }
div.applyBoxRight { float: right; margin: 0 0 0 10px; }
	div.applyBoxLeft div, div.applyBoxRight div { margin: 0 0 5px 0; padding: 3px 10px; background: #efefef; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc; }
	div.applyBoxLeft table, div.applyBoxRight table { margin: 0 auto; width: 420px; }
	div.applyBoxLeft table td, div.applyBoxRight table td { padding: 3px; vertical-align: top; }
		td.label { text-align: right; width: 110px; }
        form#applyToJobForm {  }
	form#applyToJobForm label { font-weight: bold; }
	form#applyToJobForm input.inputBoxName, form#applyToJobForm input.inputBoxNormal { width: 285px; height: 15px; }
        form#applyToJobForm input.submitButton { width: 197px; height: 27px; background: url('images/careers_submit.gif') no-repeat; }

        form#applyToJobForm input.submitButtonDown { width: 197px; height: 27px; background: url('images/careers_submit-o.gif') no-repeat; }
	form#applyToJobForm textarea { margin: 8px 0 0 0; width: 410px; height: 170px; }
	form#applyToJobForm textarea.inputBoxArea{ width: 285px; height: 70px; }

