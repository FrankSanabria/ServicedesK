<?php
require_once( 'core.php' );
require_once( 'core/bug_api.php' );
form_security_validate( 'forward_issue' );
$_name=$_FILES['attach']['name'];
$_type=$_FILES['attach']['type'];
$_size=$_FILES['attach']['size'];
$_temp=$_FILES['attach']['tmp_name']; 
$f_bug_id	= gpc_get_int( 'bug_id' );
$t_sender_id	= auth_get_current_user_id();
$t_sender	= user_get_name( $t_sender_id );
$eol            =  PHP_EOL;
// REMEMBER CONFIGURE SPF IN DNS WITH THIS IP SERVER
$t_from_mail	= "contacto@sugeek.co";
$t_from_name	= config_get ( 'from_name' );
$f_to		= gpc_get_string( 'forward_address' );
$f_cc		= gpc_get_string( 'forward_address_cc' );
$f_cco		= gpc_get_string( 'forward_address_cco' );
$t_header 	= 'From:' . $t_from_name . '<' . $t_from_mail . '>' . $eol;
$t_header	.= 'Cc:' . $f_cc . $eol;
$t_header	.= 'Bcc:' . $f_cco . $eol;
$T_header	.= 'X-Priority: 1 (Highest)' . $eol;
$t_header	.= 'X-MSMail-Priority: High' . $eol;
$t_header 	.= 'Importance: High' . $eol;
$t_subject 	= email_build_subject( $f_bug_id );
$f_note		= gpc_get_string( 'body' );
$f_body		= '<div style="width:100%;padding:5px">';
$f_body		.= '<img src="' . $g_path . 'images/Logo.png" /> </div>';
$f_body		.= '<br />' . "<center><b>¡¡¡ IMPORTANTE !!!<br /></b>Por favor responda a este mail para realizar un seguimiento adecuado a su solicitud </center><br />";
$f_body		.= "<br />" . "<b>" . lang_get ( 'plugin_forward_bud_id' ) . "</b>" . " ". $f_bug_id . "<br />" ;
$f_body		.= "<b>Cordial Saludo,</b><br />" ;
// $f_body		.= "<b>" . lang_get ( 'plugin_forward_support_by' ) . " </b>" . $t_sender . "<br /><br />" ;
$f_body		.= $f_note;
$f_body 	.= "<br />--<br /><br />";
$f_body		.= "<p> Atentamente,</p>" . "<br /><br />" . $t_sender . "<br />" . $t_from_name;
$f_body		.= lang_get ( 'plugin_forward_legal' );
$num = md5(time());

if( strcmp($_name, "") ) //FILES EXISTS
{
$fp = fopen($_temp, "rb");
$file = fread($fp, $_size);
$file = chunk_split(base64_encode($file));

$headers = $t_header;
$headers .= "MIME-Version: 1.0" . $eol;
$headers .= "Content-Type: multipart/mixed; boundary=\"".$num."\"" . $eol . $eol;

$t_content = "--".$num.$eol;
$t_content .= "Content-Type: text/html; charset=UTF-8" . $eol;
$t_content .= "Content-Transfer-Encoding: 8bit" . $eol.$eol;
$t_content .= $f_body.$eol.$eol;
$t_content .= "--".$num.$eol;
$t_content .= "Content-Type:".$_type.";name=\"".$_name."\"".$eol;
$t_content .= "Content-Transfer-Encoding: base64" . $eol;
$t_content .= "Content-Disposition: attachment; filename=\"".$_name."\"" . $eol . $eol;
$t_content .= $file.$eol;
$t_content .= "--".$num."--";

file_add($f_bug_id, gpc_get_file('attach'), 'bug');
form_security_purge( 'bug_file_add' );


}else { //FILES NO EXISTS
$headers = $t_header;
$headers .= "MIME-Version: 1.0" . $eol;
$headers .= "Content-Type: text/html; charset=UTF-8".$eol;
$headers .= "Content-Transfer-Encoding: 8bit".$eol;
$t_content = $f_body;
}

mail($f_to, $t_subject, $t_content, $headers, "-f" . $t_from_mail);
form_security_purge( 'forward_issue' );
html_page_top( null, string_get_bug_view_url( $f_bug_id ) );

?>

<br />

<div align="center">
<?php
echo lang_get( 'operation_successful' ).'<br />';
echo lang_get( 'plugin_forward_info_send' ) . " " . $f_to . " ; " . $f_cc . " ; " . $f_cco . "<br />";
print_bracket_link( string_get_bug_view_url( $f_bug_id ), lang_get( 'proceed' ) );

// Add bug note

bugnote_add($f_bug_id, "<b>" . lang_get( 'plugin_forward_notesend' ) . " " . gpc_get_string( 'forward_address' ) . " CC: " . gpc_get_string( 'forward_address_cc' ) . " CCO: " . gpc_get_string( 'forward_address_cco' ) . "</b>" . "<br />" . $f_note);

?>
</div>
<?php
html_page_bottom();
