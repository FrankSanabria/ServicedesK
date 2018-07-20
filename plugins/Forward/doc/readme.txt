Forward plugin
=====================

A plugin for MantisBT that allows to forward an issue to any email address.
This can be multiple addresses provided those addresses are seperated with a semi-colon.

The email contains (for now):
Summary
Description
Additional information
Steps to reproduce
Duedate


Revision history
1.00	2011-04-19	Initial release
1.01	2011-04-20	Bugfix
1.02			Include History option

================== EDIT BY Frank Sanabria www.sugeek.co ===================
Revision history
1.03	2017-02-13	Many Changes

Changelog:
Re edited pages codes: issue_send_page.php, issue_send.php and translate to spanish

Now we can:
* Attach files
* Send in HTML code for	enterprise templates
* Add Legal Notice
* Add auto note in the bug (thanks to fjvelascop)

1.04	2017-03-23	Add Bcc Mail
1.05	2017-03-29	Add Editor WYSIWYG from Ckeditor http://ckeditor.com/ and Add Description from Bug in a redact page, update translate spanish and english.
1.06	2017-05-17	Bugfix Zoho mail and Outlook don't read attach file, add user login validation.
1.07	2017-09-21	Attach send file into issue, HTML5 field validation , Add CC Field
1.08	2017-11-16	Add email importance
============ HOW INSTALL =======
1: Define $g_path variable with MantisBT path installation in your Config_inc.php file. 
2: Same installation that another plugin


======== TO DO ============
* Automatically attach send file in bug_id
