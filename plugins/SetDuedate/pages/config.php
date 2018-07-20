<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
html_page_top1( lang_get( 'plugin_format_title' ) );
html_page_top2();
print_manage_menu();
$link=plugin_page('cat_definition');

?>
<br/>
<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
<table align="center" class="width50" cellspacing="1">
<tr>
<td class="form-title" colspan="3">
<a href="<?php echo $link ?>"><?php echo lang_get( 'cat_definition' ) ?></a>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">
<?php echo lang_get( 'duedate_days' ) ?>
</td>
<td class="center">
<input type="text" size="3" maxlength="3" name="duedate_days_default" value="<?php echo plugin_config_get( 'duedate_days_default' )?>"/>
</td>
</tr>

<?php
$t_priority_levels = MantisEnum::getValues( config_get( 'priority_enum_string' ) );

		foreach( $t_priority_levels as $t_priority_level ) 
			{
			$t_priority_string = MantisEnum::getLabel( lang_get( 'priority_enum_string' ), $t_priority_level); 
			?>
			<tr <?php echo helper_alternate_class() ?>>
			<td class="category">
			<?php echo lang_get( 'duedate_priority' ) . $t_priority_string ?>
			</td>
			<td class="center">
			<input type="text" size="3" maxlength="3" name="<?php echo('duedate_days_priority_' . $t_priority_level)?>" value="<?php echo plugin_config_get( "duedate_days_priority_" . $t_priority_level )?>"/>
			</td>
			</tr>
<td></td>

			<?php
			}
?>

<tr <?php echo helper_alternate_class( )?>>
<td class="category" width="60%">
<?php echo lang_get( 'overrule_duedate' )?>
</td>
<td class="center" width="20%">
<label><input type="radio" name='duedate_overrule' value="1" <?php echo( ON == plugin_config_get( 'duedate_overrule' ) ) ? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_enabled' )?></label>
<label><input type="radio" name='duedate_overrule' value="0" <?php echo( OFF == plugin_config_get( 'duedate_overrule' ) )? 'checked="checked" ' : ''?>/>
<?php echo lang_get( 'duedate_disabled' )?></label>
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