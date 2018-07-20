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
	 * Check login then redirect to main_page.php or to login_page.php
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2014  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	$t_allow_perm_login = ( ON == config_get( 'allow_permanent_cookie' ) );

	$f_username		= gpc_get_string( 'username', '' );
	$f_password		= gpc_get_string( 'password', '' );
	$f_perm_login	= $t_allow_perm_login && gpc_get_bool( 'perm_login' );
	$t_return		= string_url( string_sanitize_url( gpc_get_string( 'return', config_get( 'default_home_page' ) ) ) );
	$f_from			= gpc_get_string( 'from', '' );
	$f_secure_session = gpc_get_bool( 'secure_session', false );
        $f_captcha              = gpc_get_string( 'captcha', '' );


	$f_username = auth_prepare_username($f_username);
	$f_password = auth_prepare_password($f_password);
     $f_captcha = utf8_strtolower( trim( $f_captcha ) );

        # Retrieve captcha key now, as session might get cleared by logout
       $t_form_key = session_get_int( CAPTCHA_KEY, null );

	if( ON == config_get( 'signup_use_captcha' ) && get_gd_version() > 0  &&
                               helper_call_custom_function( 'auth_can_change_password', array() ) ) {
             # captcha image requires GD library and related option to ON
             $t_key = utf8_strtolower( utf8_substr( md5( config_get( 'password_confirm_hash_magic_string' ) . $t_form_key ), 1, 10) );

               if ( $t_key != $f_captcha ) {
                      trigger_error( ERROR_SIGNUP_NOT_MATCHING_CAPTCHA, ERROR );
             }

                # Clear captcha cache
            session_delete( CAPTCHA_IMG );
   }


	gpc_set_cookie( config_get_global( 'cookie_prefix' ) . '_secure_session', $f_secure_session ? '1' : '0' );

	if ( auth_attempt_login( $f_username, $f_password, $f_perm_login ) ) {
		session_set( 'secure_session', $f_secure_session );

		$t_redirect_url = 'login_cookie_test.php?return=' . $t_return;

	} else {
		$t_redirect_url = 'login_page.php?return=' . $t_return .
			'&error=1&username=' . urlencode( $f_username ) .
			'&secure_session=' . ( $f_secure_session ? 1 : 0 );
		if( $t_allow_perm_login ) {
			$t_redirect_url .= '&perm_login=' . ( $f_perm_login ? 1 : 0 );
		}

		if ( HTTP_AUTH == config_get( 'login_method' ) ) {
			auth_http_prompt();
			exit;
		}
	}

	print_header_redirect( $t_redirect_url );
