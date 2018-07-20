<?php

# MantisStats - a statistics plugin for MantisBT
#
# Copyright (c) MantisStats.Org
#
# MantisStats is available in two versions: LIGHT and PRO.
#
# MantisStats LIGHT version is free for use (freeware software).
# MantisStats PRO version is available for fee.
# MantisStats LIGHT and PRO versions are NOT open-source software.
#
# A copy of License was delivered to you during the software download. See LICENSE file.
#
# MantisStats is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See License for more
# details.
#
# https://www.mantisstats.org

if( !defined( 'MANTIS_VERSION' ) ) { exit(); }


auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

require_once 'common_includes.php';

if ( FALSE == form_security_validate('config_admin') ) { exit; };

// runtime setting
if ( isset( $_POST['startdate'] ) ) {
    $t_startdate = (int) $_POST['startdate'];

    if ( array_key_exists( $t_startdate, $startDateInputFilter_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'start_date_input_filter'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('start_date_input_filter', '" . db_prepare_string( $t_startdate ) . "', 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_startdate  . " where config_name = 'start_date_input_filter'";
            db_query( $query );

        }
    }
}


// runtime setting
if ( isset( $_POST['runtime'] ) ) {
    $t_runtime = (int) $_POST['runtime'];

    if ( in_array( $t_runtime, $showRuntime_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'show_runtime'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('show_runtime', '" . db_prepare_string( $t_runtime ) . "', 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_runtime  . " where config_name = 'show_runtime'";
            db_query( $query );

        }
    }
}


// number of rows
if ( isset( $_POST['numrows'] ) ) {
    $t_numrows = (int) $_POST['numrows'];

    if ( in_array( $t_numrows, $maxResultsInTables_arr ) ) {

        $query  = "select config_int_value from " . plugin_table( 'config' ) . " where config_name = 'no_rows_intables'";
        $result = db_query( $query );

        if ( db_num_rows( $result ) == 0 ) {

            $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_int_value, is_default) values ('no_rows_intables', " . $t_numrows . ", 1)";
            db_query( $query );

        } elseif ( db_num_rows( $result ) == 1 ) {

            $query ="update " . plugin_table( 'config' ) . " set config_int_value = " . $t_numrows . " where config_name = 'no_rows_intables'";
            db_query( $query );

        }
    }
}


// which reports
if ( isset( $_POST['whichreports']) and !empty($_POST['whichreports'] ) ) {

    $t_whichreports = $_POST['whichreports'];

    $addToQuery = '';

    foreach ( $t_whichreports as $key => $val ) {
        if ( array_key_exists( $val, $reports_arr ) ) { $addToQuery = $addToQuery . $val . ","; }
    }

    $addToQuery = rtrim( $addToQuery, "," );

    $query  = "select config_char_value from " . plugin_table( 'config' ) . " where config_name = 'which_reports'";
    $result = db_query( $query );

    if ( db_num_rows( $result ) == 0 ) {

        $query = "insert into " . plugin_table( 'config' ) . " (config_name, config_char_value, is_default) values ('which_reports', '" . db_prepare_string( $addToQuery ) . "', 1)";
        db_query( $query );

    } elseif ( db_num_rows( $result ) == 1 ) {

        $query ="update " . plugin_table( 'config' ) . " set config_char_value = '" . db_prepare_string( $addToQuery ) . "' where config_name = 'which_reports'";
        db_query( $query );

    }
}

?>

<br />
<div align="center">

<?php
$t_redirect_url = 'plugin.php?page=MantisStats/config_admin';

html_page_top( null, $t_redirect_url );
echo '<br />';
echo lang_get( 'operation_successful' ) . '<br />';

print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>

</div>

<?php html_page_bottom(); ?>
