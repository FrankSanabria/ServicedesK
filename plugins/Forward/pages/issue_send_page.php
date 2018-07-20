<?php
require_once( 'core.php' );
require_once( 'core/bug_api.php' );
auth_ensure_user_authenticated();
$f_bug_id = gpc_get_int( 'bug_id' );
$t_bug = bug_get( $f_bug_id, true );
html_page_top( bug_format_summary( $f_bug_id, SUMMARY_CAPTION ) );
$g_issue_send = plugin_page( 'issue_send.php' );
echo '<script type="text/javascript" src="' . $g_path . 'plugins/Forward/ckeditor/ckeditor.js"></script>';
?>
<br />
<div align="center">
<form method="post" enctype="multipart/form-data" action="<?php echo $g_issue_send ?>">
<?php echo form_security_field( 'forward_issue' ) ?>

<input type="hidden" name="bug_id" value="<?php echo $f_bug_id ?>" />
<table class="width75" cellspacing="1">

<tr>
	<td class="form-title" colspan="2">
		<?php 
		echo lang_get( 'plugin_forward_title' ) ;
		?>
	</td>
</tr>
<tr>
	<td class="category">
		<?php echo lang_get( 'to' ) ?>
	</td>
	<td>
		<input type="email" size="100" maxlength="200" name="forward_address" placeholder="<?php echo lang_get( 'plugin_forward_helpmail' ) ?>"  required/>
	</td>
</tr>
<tr>
    	 <td class="category">
              <?php echo "Cc" ?>
        </td>
        <td>
            	<input type="email" size="100" maxlength="200" name="forward_address_cc" value=""/>

        </td>

</tr>

<tr>
         <td class="category">
              <?php echo "Cco" ?>
	</td>
        <td>
            	<input type="email" size="100" maxlength="200" name="forward_address_cco" value=""/>

        </td>

</tr>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'plugin_forward_message' ) ?>
	</td>

	<td class="center">
		<textarea name="body" id="editor1" cols="75" rows="15"></textarea>
	</td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		 <?php echo lang_get( 'plugin_forward_attach') ?>
	</td>
	<td>
		<input type='file' name='attach' id='attach'>
	</td>
</tr>
<tr>
	<td class="center" colspan="2">
		<input type="submit" class="button" value="<?php echo lang_get( 'bug_send_button' ) ?>" />
	</td>
</tr>
</table>

</form>
<br />
<table class="width75" cellspacing="1">
<tr>
	<td>
		<?php

		echo "<b>" . lang_get( 'plugin_forward_original_message') . "</b><br /><br />";
		echo string_display_links( $t_bug->description );


		?>

	</td>
</tr>
</table>

</div>
<script>
           CKEDITOR.replace( 'editor1' );
</script>
<?php
	html_footer();
?>
</body>
</html>
