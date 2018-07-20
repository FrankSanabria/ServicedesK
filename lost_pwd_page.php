<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

	/**
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2014  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @author Marcello Scata' <marcelloscata at users.sourceforge.net> ITALY
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	# lost password feature disabled or reset password via email disabled -> stop here!
	if ( LDAP == config_get_global( 'login_method' ) ||
		OFF == config_get( 'lost_password_feature' ) ||
		OFF == config_get( 'send_reset_password' )  ||
	 	OFF == config_get( 'enable_email_notification' ) ) {
		trigger_error( ERROR_LOST_PASSWORD_NOT_ENABLED, ERROR );
	}

	# don't index lost password page
	html_robots_noindex();

	html_page_top1();
	html_page_top2a();

	echo "<br />";
?>
<br />
<body style="background: radial-gradient(#dde1e7, #303030)">
<div style="width:500px;margin:15% auto;background:#f7f7f7;box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);padding-bottom:5px">
	<form name="lost_password_form" method="post" action="lost_pwd.php">
	<?php echo form_security_field( 'lost_pwd' ) ?>
        <div style="height:30px;background-color:#303030;color:#fff;font-weight: 300;font-size: 1rem;line-height:30px;padding:5px">
		<?php echo lang_get( 'lost_password_title' );
			echo '<div style="font-size:10px;float:right;font-weight:bold">';
			echo '<a href="http:///www.sugeek.co/servicedesk" target="_blank" >' . '<img src="images/' . config_get( 'logo_white' ) . '"style="width:120px" alt="Su|GE3K ServiceDesk" />' . '</a>';
		 	echo '</div>'; 
		?>
	</div>
	<?php
		$t_allow_passwd = helper_call_custom_function( 'auth_can_change_password', array() );
		if ( $t_allow_passwd ) {
		?>
        <div align="center" style="padding-top:5px">
		<input type="text" name="username" size="32" maxlength="<?php echo DB_FIELD_SIZE_USERNAME;?>" placeholder=" <?php echo lang_get( 'username' ) ?>" required/>
		<input type="email" name="email"  size="32" maxlength="<?php echo DB_FIELD_SIZE_EMAIL;?>" placeholder="<?php echo lang_get( 'email' ) ?>" required/>
	</div>
	<div align="center" style="padding:5px">
		<input type="submit" class="button"  value="<?php echo lang_get( 'lost_password_link' ) ?>" />
	</div>
<?php
	echo '<br /><div align="center">';
        print_login_link();
        echo '&#160;';
        print_signup_link();
        echo '</div>';

?>
<?php
  } else {
?>
	<td colspan="2">
		<br />
		<?php echo lang_get( 'no_password_request' ) ?>
		<br /><br />
<?php
  }
?>

</form>
</div>

<?php
/**	echo '<br /><div align="center">';
*	print_login_link();
*	echo '&#160;';
*	print_signup_link();
*	echo '</div>';
*/
	if ( ON == config_get( 'use_javascript' ) ) {
?>
<!-- Autofocus JS -->
<?php if ( ON == config_get( 'use_javascript' ) ) { ?>
<script type="text/javascript" language="JavaScript">
	window.document.lost_password_form.username.focus();
</script>
<?php 
	}
}

//  html_page_bottom1a( __FILE__ );
