<?php
	auth_reauthenticate();
	access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
	html_page_top( lang_get( 'plugin_forward_title' ) );
	print_manage_menu();
?>
<br/>
<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
	<?php echo form_security_field( 'plugin_forward_config_update' ) ?>

	<table align="center" class="width50" cellspacing="1">

		<tr>
			<td class="form-title" colspan="2">
				<?php echo lang_get( 'plugin_forward_title' ) . ': ' . lang_get( 'plugin_forward_config' )?>
			</td>
		</tr>

		<tr <?php echo helper_alternate_class() ?>>
			<td class="category">
				<?php echo lang_get( 'plugin_forward_threshold' ) ?>
			</td>
			<td class="center">
				<select name="forward_threshold">
				<?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'forward_threshold'  ) ) ?>;
				</select>
			</td>
		</tr>

		<tr <?php echo helper_alternate_class() ?>>
			<td class="category">
			<?php echo lang_get( 'plugin_forward_address' ) ?>

			</td>
			<td class="center">
				<input type="text" size="30" maxlength="200" name="forward_address" value="Email 1"/><br />

			</td>
		</tr>
		
		<tr>
			<td class="center" colspan="3">
				<input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' ) ?>" />
			</td>
		</tr>

	</table>
<form>

<?php
html_page_bottom1( __FILE__ );
