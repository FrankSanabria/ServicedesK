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
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );


// 'resolved' options
$resolved_options   = array( 1, 2 );
$resolved_option    = $resolved_options[0];

if ( isset( $_GET['resolution_date_options'] ) and !empty( $_GET['resolution_date_options'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_GET['resolution_date_options'] ) ) {
            $resolved_option = $v;
            $_SESSION['resolved_option'] = $v;
            break;
        }
    }
} elseif ( isset( $_SESSION['resolved_option'] ) and !empty( $_SESSION['resolved_option'] ) ) {
    foreach ( $resolved_options as $k => $v) {
        if ( $v == strip_tags( $_SESSION['resolved_option'] ) ) {
            $resolved_option = $v;
            break;
        }
    }
} else { $resolved_option = $resolved_options[0]; }


// start and finish dates and times
$db_datetimes = $granularity_items = array();

$db_datetimes['start']  = strtotime( cleanDates( 'date-from', $dateFrom ) . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );


// [ daily | weekly | monthly | yearly ] granularities
if ( $selectedGranularity == 2 ) {          // Weekly
    $date_format    = 'oW';
    $incr_str       = ' weeks';
} elseif ( $selectedGranularity == 3 ) {    // Monthly
    $date_format = 'Ym';
    $incr_str       = ' months';
} elseif ( $selectedGranularity == 4 ) {    // Yearly
    $date_format = 'Y';
    $incr_str       = ' years';
} else {                                    // If granilarity is Daily
    $date_format = 'Y-m-d';
    $incr_str       = ' days';
}


// Preparing data array
$i = 0;

$incrTimestamp = $db_datetimes['start'];

while ( $incrTimestamp <= $db_datetimes['finish'] ) {
    $i++;
    $granularity_items[] = date( $date_format, $incrTimestamp );
    $incrTimestamp = strtotime( date( "Ymd", $db_datetimes['start'] ) . " + " . $i . $incr_str); // not "o-m-d"?
}

$dateConditionForResolved = " AND h.date_modified >= " . db_prepare_string( $db_datetimes['start'] ) . " AND h.date_modified <= " . db_prepare_string( $db_datetimes['finish'] );
if ( $resolved_option == 1 ) {
    $dateConditionForResolved = " AND h.date_modified >= " . db_prepare_string( $db_datetimes['start'] ) . " AND h.date_modified <= " . db_prepare_string( $db_datetimes['finish'] ) . " AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . " AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] );
}

$query = "
    SELECT date_submitted as the_date
    FROM $mantis_bug_table
    WHERE date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
    AND date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
    AND $specific_where
    ";
$result = db_query( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['opened'][$the_date] ) ) {
            $db_data['opened'][$the_date]++;
        } else {
            $db_data['opened'][$the_date] = 1;
        }
    }
    $totals['opened'] = sizeof( $result );
} else { $db_data['opened'] = array(); $totals['opened'] = 0; }

unset ( $result );

$query = "
    SELECT max(h.date_modified) as the_date, mbt.id
    FROM $mantis_bug_table mbt
    LEFT JOIN $mantis_bug_history_table h
    ON mbt.id = h.bug_id
    AND h.type = " . NORMAL_TYPE . "
    AND h.field_name = 'status'
    WHERE mbt.status >= $resolved_status_threshold
    AND h.old_value < '$resolved_status_threshold'
    AND h.new_value >= '$resolved_status_threshold'
    $dateConditionForResolved
    AND $specific_where
    GROUP BY mbt.id
    ";
$result = db_query( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['resolved'][$the_date] ) ) {
            $db_data['resolved'][$the_date]++;
        } else {
            $db_data['resolved'][$the_date] = 1;
        }
    }
    $totals['resolved'] = sizeof( $result );
} else { $db_data['resolved'] = array(); $totals['resolved'] = 0; }

unset ( $result );


// making granularity drop-down
rsort( $granularity_items );

$granularityOptionsDropDown = "<strong>" . lang_get( 'plugin_MantisStats_granularity' ) . "</strong>&nbsp;&nbsp;<select name='granularity' id='granularity'>";

foreach( $granularities as $key => $val ) {
    if ( $selectedGranularity == $key ) { $selectedFormValue = " selected "; } else { $selectedFormValue = ''; }
    $granularityOptionsDropDown .= "<option " . $selectedFormValue . " value='" . $key . "'>" . $val . "</option>";
}

$granularityOptionsDropDown .= "</select>";


// build table header
$data_table_print = "
<table class='display' id='onetbl' style='display:none'>
    <thead>
    <tr class='tblheader'>
        <td width='100%'>" . lang_get( 'plugin_MantisStats_date' ) . "</td>
        <td class='dt-right'>" . lang_get( 'opened' ) . "</td>
        <td class='dt-right'>" . lang_get( 'resolved' ) . "</td>
        <td class='dt-right'>" . lang_get( 'balance' ) . "</td>
    </tr>
    </thead>
    <tbody>";


// build table body
$i = 0;

foreach ( $granularity_items as $key => $val ) {

    $i++;

	if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $data_table_print .= "<tr><td>" . $show_date . "</td>";

	if ( isset( $db_data['opened'] ) and array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
	$data_table_print .= "<td class='dt-right'>" . $show_count . "</td>";

    if ( isset( $db_data['resolved'] ) and array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $data_table_print .= "<td class='dt-right'>" . $show_count . "</td>";

	$balance = @$db_data['opened'][$val] - @$db_data['resolved'][$val];
	if ( $balance > 0 ) { $style = "negative"; $plus = '+'; } else { $style = "positive"; $plus = ''; }

	$data_table_print .=  "<td class='dt-right " . $style . "'>" . $plus . $balance . "</td></tr>";

}

$data_table_print .= "</tbody></table>";


// chart
$chart_data = array ('categories' => '', 'opened' => '', 'resolved' => '');
$granularity_items = array_reverse( $granularity_items );

foreach ( $granularity_items as $key => $val ) {

    if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    $chart_data['categories'] .= "<category label='" . $show_date . "' />";
    if ( isset( $db_data['opened'] ) and array_key_exists( $val, $db_data['opened'] ) ) { $show_count = $db_data['opened'][$val]; } else { $show_count = 0; }
    $chart_data['opened'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
    if ( isset( $db_data['resolved'] ) and array_key_exists( $val, $db_data['resolved'] ) ) { $show_count = $db_data['resolved'][$val]; } else { $show_count = 0; }
    $chart_data['resolved'] .= "<set tooltext='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . ": " . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
}

$chart_data_print  = "<categories>" . $chart_data['categories'] . "</categories>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'opened' ), ENT_QUOTES ) . "' color='c80130'>" . $chart_data['opened'] . "</dataset>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'resolved' ), ENT_QUOTES ) . "' color='009933'>" . $chart_data['resolved'] . "</dataset>";

?>

<script type="text/javascript">
FusionCharts.ready(function () {
    var myChart = new FusionCharts({
      "type": "ScrollColumn2D",
      "renderAt": "chartContainer",
      "width": $( window ).width()/1.25,
      "height": "350",
      "dataFormat": "xml",
      "dataSource": "<chart rotatelabels='1' palettecolors='#0075c2' bgcolor='#ffffff' showborder='0' showcanvasborder='0' useplotgradientcolor='0' plotborderalpha='10' placevaluesinside='1' valuefontcolor='#ffffff' showaxislines='1' axislinealpha='25' divlinealpha='10' aligncaptionwithcanvas='0' showalternatevgridcolor='0' captionfontsize='14' subcaptionfontsize='14' subcaptionfontbold='0' tooltipcolor='#ffffff' tooltipborderthickness='0' tooltipbgcolor='#000000' tooltipbgalpha='80' tooltipborderradius='2' tooltippadding='5'><?php echo $chart_data_print; ?></chart>"
    });

  myChart.render();
});
</script>

<script>
    $(document).ready( function () {
        $('#onetbl').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [ 0, 'desc' ],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "desc", "asc" ] },
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#onetbl').show();

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
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe_op_re' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="MantisStats/trends_by_open_resolved" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <br />
                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                    <p />
                    <div id="options">
                        <?php echo $granularityOptionsDropDown; ?>
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
                </div>

                <p class="space20Before" />

                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op1" <?php if ( $resolved_option == 1 ) { echo "checked"; } ?> value="1">
                <label for="op1" class="inl"><?php echo lang_get( 'plugin_MantisStats_res_radio_opt1' ); ?></label>
                <p />
                <input type="radio" class="resolution_date_options" name="resolution_date_options" id="op2" <?php if ( $resolved_option == 2 ) { echo "checked"; } ?> value="2">
                <label for="op2" class="inl"><?php echo lang_get( 'plugin_MantisStats_res_radio_opt2' ); ?></label>

            </form>
        </div>


        <div class="chartBox space50Before" />
            <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_vs_res_chrt' ); ?></strong>
            <p />
		    <div id="chartContainer" style="display:inline;"><?php echo lang_get( 'plugin_MantisStats_open_vs_res_chrt' ); ?></div>
        </div>


        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_vs_res' ); ?></strong>
        <p />
        <?php echo $data_table_print; ?>


        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ) . "<br />"; } ?>
        
        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

        <?php html_page_bottom();?>
</div>
