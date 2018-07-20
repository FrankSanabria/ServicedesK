<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );
html_page_top1( lang_get( 'plugin_format_title' ) );
html_page_top2();
print_manage_menu();
$link=plugin_page('config');
$cat_table= db_get_table( 'mantis_category_table' );
$prj_table= db_get_table( 'mantis_project_table' );
$cat_def_table	= plugin_table('defined');
?>
<br/>
<form action="<?php echo plugin_page( 'due_cat_add' ) ?>" method="post">
<input type="hidden" name="table" value="<?php echo $cat_def_table;  ?>">
<table align="center" class="width50" cellspacing="1">
<tr>
<td class="form-title" colspan="8">
<a href="<?php echo $link ?>"><?php echo lang_get( 'prio_definition' ) ?></a>
</td>
</tr>
<br>
<tr class="row-category">
<td></td>
<td><div align="center"><?php echo lang_get( 'cat_title' ); ?></div>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 20); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 30); ?></td>
<td><?php echo MantisEnum::getLabel( lang_get( 'priority_enum_string' ), 40); ?></td>
<td>&nbsp;</td>
</tr
<tr <?php echo helper_alternate_class() ?>>

<td></td>
<td>
<?php
$query="select a.id,b.name as prjname,a.name as catname from $cat_table as a,$prj_table as b where a.project_id=b.id order by prjname,catname";
$result=db_query_bound($query);
echo '<select name="sel_cat">';
while ($row1= db_fetch_array($result)){
	$projcatname  = $row1['prjname'];
	$projcatname .= '<=>';
	$projcatname .= $row1['catname'];
	echo '<option value="'. $row1['id'] .'"';
	echo '>' . $projcatname . '</option>'; 
}
echo '</select>';
?>
</td>
<td><input name="days20" type="text" size=5 maxlength=5 ></td>
<td><input name="days30" type="text" size=5 maxlength=5 ></td>
<td><input name="days40" type="text" size=5 maxlength=5 ></td>
<td><input name="<?php echo lang_get( 'due_submit' ) ?>" type="submit" value="<?php echo lang_get( 'due_submit' ) ?>">
</td>
</tr>
<?php
	# Pull all Record entries
	$query = "SELECT a.id,c.name as prjname,b.name as catname,a.days10,a.days20,a.days30,a.days40,a.days50,a.days60 FROM $cat_def_table as a,$cat_table as b,$prj_table as c  WHERE a.id =b.id and b.project_id=c.id order by prjname,catname";
	$result = db_query_bound($query);
	while ($row = db_fetch_array($result)) {
		$projcatname  = $row['prjname'];
		$projcatname .= '<=>';
		$projcatname .= $row['catname'];
		?>
		<tr>
		<td><?php echo $row['id'] ?></td>
		<td><?php echo $projcatname ?></td>
		<td><div align="center"><?php echo $row['days20'] ?></td>
		<td><div align="center"><?php echo $row['days30'] ?></td>
		<td><div align="center"><?php echo $row['days40'] ?></td>
		<td>
		<a href="plugins/SetDuedate/pages/duedate_delete.php?delete_id=<?php echo $row["id"]; ?>&table=<?php echo $cat_def_table; ?>"><?php echo lang_get( 'due_remove' ) ?></a>

		</tr>
		<?php
	}
?>




</table>
<form>
<?php
html_page_bottom1( __FILE__ );
