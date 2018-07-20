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

html_page_top( lang_get( 'summary_link' ) );
print_manage_menu();

require_once 'common_includes.php';

// Which reports to show
$confWhichReportsToShow = '';

foreach ( $reports_arr as $k => $v ) {
    if ( in_array( $k, $reportsToShow ) ) { $checked = " checked "; } else { $checked = " "; }
    $confWhichReportsToShow .= "<label><input " . $checked . " type='checkbox' name='whichreports[]' value='" . $k . "'> " . $v . "</label><br />";
}

// Number of rows in tables
$confNoResultsInTables = '';

foreach ( $maxResultsInTables_arr as $key => $val ) {

    ( $val == $maxResultsInTables ) ? $checked = " checked " : $checked = '';
    ( !$val ) ? $showval = lang_get( 'plugin_MantisStats_no_limit' ) : $showval = $val;

    $confNoResultsInTables .= "<label><input " . $checked . " type='radio' name='numrows' value='" . $val . "' /> " . $showval . "</label><br />";

}

// Run-time of reports
$confRunTime = '';

foreach ( $showRuntime_arr as $key => $val ) {

    ( $val == $showRuntime ) ? $checked = " checked " : $checked = '';
    ( !$val ) ? $showval = lang_get( 'plugin_MantisStats_runtime_hide' ) : $showval = lang_get( 'plugin_MantisStats_runtime_show' );

    $confRunTime .= "<label><input " . $checked . " type='radio' name='runtime' value='" . $val . "' /> " . $showval . "</label><br />";

}


// Start date input filter
$confStartDate = '';

foreach ( $startDateInputFilter_arr as $key => $val ) {

    ( $key == $startDateInputFilter ) ? $checked = " checked " : $checked = '';

    $confStartDate .= "<label><input " . $checked . " type='radio' name='startdate' value='" . $key . "' /> " . $val . "</label><br />";

}

?>

<br />

        <div align="center">

            <form action="<?php echo plugin_page( 'config_admin_update' ) ?>" method="post">
            <?php echo form_security_field( 'config_admin' ) ?>
            <table class="width75" cellspacing="1">
            <tr>
                <td colspan="2" class="form-title">

<?php echo plugin_lang_get( 'configuration' ); ?>

                </td>
            </tr>

            <tr class="row-1">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_reports' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_which_report'); ?></strong>
                    <p />
                    <?php echo $confWhichReportsToShow; ?>
                    <p />
                </td>
            </tr>

            <tr class="row-2">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_data_tables' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_nrows_intables' ); ?></strong>

                    <p />
                    <?php echo $confNoResultsInTables; ?>
                    <p />
                </td>
            </tr>

            <tr class="row-1">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_runtime' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_runtime_sh' ); ?></strong>

                    <p />
                    <?php echo $confRunTime; ?>
                    <p />
                </td>
            </tr>

            <tr class="row-2">
                <td class="category" width="25%"><?php echo lang_get( 'plugin_MantisStats_start_date_conf1' ); ?></td>
                <td>
                    <strong><?php echo lang_get( 'plugin_MantisStats_start_date_conf2' ); ?></strong>

                    <p />
                    <?php echo $confStartDate; ?>
                    <p />
                </td>
            </tr>

            <tr>
                <td class="left">&nbsp;</td>
                <td><input type="submit" class="button" value="<?php echo lang_get( 'plugin_MantisStats_save_config' ); ?>" /></td>
            </tr>

            </table>

            </form>

        </div>

	</td>
</tr>
</table>

<?php
	html_page_bottom();
?>
