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


html_page_top( lang_get( 'summary_link' ) );
echo "<br />";
if ( plugin_config_get( 'menu_location' ) == 'EVENT_MENU_SUMMARY' ) { print_summary_menu( 'summary_page.php' ); }

require_once 'common_includes.php';


$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$mantis_bug_table           = db_get_table( 'mantis_bug_table' );
$mantis_bugnote_table       = db_get_table( 'mantis_bugnote_table' );
$mantis_user_table          = db_get_table( 'mantis_user_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );
$count_states               = count_states();


// if show_realname config is OFF then use usernames if user has enough permission for it
if ( config_get( 'show_realname' ) != 'OFF' ) {
    $user_names = user_names( 'realname' );
} else {
    $user_names = user_names( 'username' );
}


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates( 'date-from', $dateFrom ) . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );


// get data
$issues_fetch_from_db = array();

$query = "
        SELECT mut.id, count(*) AS the_count, mbt.status
        FROM $mantis_bugnote_table mbnt
        LEFT JOIN $mantis_bug_table mbt ON mbnt.bug_id = mbt.id
        LEFT JOIN $mantis_user_table mut ON mbnt.reporter_id = mut.id
        WHERE $specific_where
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        GROUP BY mut.id, mbt.status
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $issues_fetch_from_db[$row['id']][$row['status']] = $row['the_count'];
}

unset ( $result );


// build tables and charts
function tables_and_charts ( $type, $output ) {

	global $resolved_status_threshold, $status_values, $status_enum_string, $issues_fetch_from_db, $user_names, $count_states, $maxResultsInTables;

	$data_table = $data_table_totals = array();

	foreach ( $issues_fetch_from_db as $key => $val ) {

        if ( !isset( $data_table_totals[$key] ) ) { $data_table_totals[$key] = 0; }

        foreach ( $status_values as $k => $v ) {
            if ( ( $v < $resolved_status_threshold and $type == "open" ) || ( $v >= $resolved_status_threshold and $type == "resolved" ) ) {
                if ( isset( $issues_fetch_from_db[$key][$v] ) ) {
            		$data_table[$key][$v] = $issues_fetch_from_db[$key][$v];
                    $data_table_totals[$key] = $data_table_totals[$key] + $issues_fetch_from_db[$key][$v];
                } else {
                    $data_table[$key][$v] = 0;
                }
            }
        }
    }


    // build table header
    $data_table_print = "
    <table class='display' id='$type' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_MantisStats_user_name' ) . "</td>";

    foreach ( $status_values as $key => $val ) {
        if ( ( $type == 'open' and $val >= $resolved_status_threshold ) || ( $type == 'resolved' and $val < $resolved_status_threshold ) ) { continue; }
        $data_table_print .= "<td class='dt-right nowrap'>" . MantisEnum::getLabel( $status_enum_string, $val ) . "</td>";
    }

    $data_table_print .= "
            <td class='dt-right'>" . lang_get( 'plugin_MantisStats_total' ) . "</td>
        </tr>
        </thead>
        <tbody>";


    // build table body
    $i = 0;

    arsort( $data_table_totals );

    foreach ( $data_table_totals as $key => $val ) {

        $i++;
        if ( $val == 0 || ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) ) { break; }

		if ( !isset( $user_names[$key] ) ) { $user_names[$key] = 'N/A'; }
        
        $data_table_print .= "
        <tr>
            <td>" . $user_names[$key] . "</td>";

        foreach ( $status_values as $k => $v ) {
            if ( ( $type == 'open' and $v >= $resolved_status_threshold ) || ( $type == 'resolved' and $v < $resolved_status_threshold ) ) { continue; }
            $data_table_print .= "
            <td class='dt-right'>" . number_format( $data_table[$key][$v] ) . "</td>";
        }
        $data_table_print .= "
            <td class='dt-right'>" . number_format( $val ) . "</td>
        </tr>";
	}


    // build end of the table
    $data_table_print .= "
    </tbody></table>
    ";


    // build charts
    $container = "chartContainer" . ucfirst( $type );
    $chart_data_print = <<<EOT

<script type="text/javascript">
FusionCharts.ready(function () {
    var myChart = new FusionCharts({
      "type": "bar2d",
      "renderAt": "$container",
      "width": $( window ).width()/2.5,
      "height": "350",
      "dataFormat": "xml",
      "dataSource": "<chart palettecolors='#0075c2' bgcolor='#ffffff' showborder='0' showcanvasborder='0' useplotgradientcolor='0' plotborderalpha='10' placevaluesinside='1' valuefontcolor='#ffffff' showaxislines='1' axislinealpha='25' divlinealpha='10' aligncaptionwithcanvas='0' showalternatevgridcolor='0' captionfontsize='14' subcaptionfontsize='14' subcaptionfontbold='0' tooltipcolor='#ffffff' tooltipborderthickness='0' tooltipbgcolor='#000000' tooltipbgalpha='80' tooltipborderradius='2' tooltippadding='5'>
EOT;

    $i = 0;

    foreach ( $data_table_totals as $key => $val ) {

        $i++;
        if ( $val == 0 or $i > MAX_LINES_IN_BAR_CHARTS ) { break; }

        if ( !isset( $user_names[$key] ) ) { $user_names[$key] = 'N/A'; }
        
        $chart_data_print .= "<set label='" . htmlspecialchars( $user_names[$key], ENT_QUOTES ) . "' value='" . $val . "' toolText='" . number_format( $val ) . " [" . htmlspecialchars( $user_names[$key], ENT_QUOTES ) . "]' />";

    }

    $chart_data_print .= <<<EOT
    </chart>"
    });

  myChart.render();
});
</script>
EOT;

    // returns
	if ( $output == 'chart' ) { echo $chart_data_print; }
    else                      { return $data_table_print; }

}

tables_and_charts( "open", "chart" );
tables_and_charts( "resolved", "chart" );

?>

<script>
    $(document).ready( function () {
        $('#open').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ <?php echo $count_states['open'] + 1; ?>, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
            <?php
                $i = 0;
                while ( $i <= $count_states['open'] ) {
                    echo '{ "asSorting": [ "desc", "asc" ] },';
                    $i++;
                }
            ?>
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#open').show();

        $('#resolved').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ <?php echo $count_states['resolved'] + 1; ?>, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
            <?php
                $i = 0;
                while ( $i <= $count_states['resolved'] ) {
                    echo '{ "asSorting": [ "desc", "asc" ] },';
                    $i++;
                }
            ?>
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#resolved').show();

    } );

    $(function() {
        $( "#from" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
    $(function() {
        $( "#to" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
</script>

                
<div id="wrapper">

        <div id="logo">
        </div>

        <?php echo $whichReport; ?>

        <p />

        <div id="titleText">
            <div id="scope"><?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />


        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="MantisStats/people_by_notes" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <br />
                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
                </div>
            </form>
        </div>


        <div class="chartBox space50Before" />
            <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_iss_chrt' ); ?></strong>
            <p />
		    <div id="chartContainerOpen" style="display:inline;"><?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></div>
        </div>

        <div class="chartBox space40Before" />
            <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_resolved_iss_chrt' ); ?></strong>
            <p />
		    <div id="chartContainerResolved" style="display:inline;"><?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></div>
        </div>


        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></strong>
        <p />
        <?php echo tables_and_charts( "open", "table" ); ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo tables_and_charts( "resolved", "table" ); ?>


        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ) . "<br />"; } ?>
        
        <strong>&raquo;</strong> <?php printf( lang_get( 'plugin_MantisStats_charts_maxdisp' ), MAX_LINES_IN_BAR_CHARTS ); ?> <?php if ( $maxResultsInTables ) { printf( lang_get( 'plugin_MantisStats_tables_maxdisp' ), $maxResultsInTables ); } ?>

        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

        <?php html_page_bottom();?>
</div>
