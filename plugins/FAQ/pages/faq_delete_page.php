<?php
require( "faq_api.php" );
require( "css_faq.php" );
html_page_top1();
if (OFF == plugin_config_get('faq_view_window') ){
  html_page_top2();
}
$f_id = gpc_get_int( 'f_id' );


    # Retrieve faq item data and prefix with v_
        $row = faq_select_query( $f_id );
        if ( $row ) {
        extract( $row, EXTR_PREFIX_ALL, "v" );
    }

        $v_question = string_attribute( $v_question );
       	$v_answere	= string_textarea( $v_answere );

?>

<p>

<div align="center" style="margin:40px auto">
	<?php print_hr( $g_hr_size, $g_hr_width ) ?>
	<?php echo plugin_lang_get( 'delete_faq_sure_msg' ) . "<br>" ?>
	<form method="post" action="<?php echo $g_faq_delete ?>">
	       <p> <span style="color:#303030" class="faq-question"><?php echo $v_question ?></span></p>
		<input type="hidden" size="3" name="f_id" value="<?php echo $f_id ?>"> <br />
		<a class="button" style="padding-left:20px;padding-right:20px" href="javascript:history.back()">NO</a>
		<input type="submit" value="<?php echo plugin_lang_get( 'delete_faq_item_button' ) ?>">
	</form>

</div>

<?php
html_page_bottom1();
