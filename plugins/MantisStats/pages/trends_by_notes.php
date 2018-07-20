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

$query = "
    SELECT mbnt.date_submitted as the_date
    FROM $mantis_bugnote_table mbnt
    LEFT JOIN $mantis_bug_table mbt
    ON mbnt.bug_id = mbt.id
    WHERE mbnt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
    AND mbnt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
    AND $specific_where
    ";
$result = db_query( $query );

if ( sizeof( $result ) > 0 ) {
    foreach ( $result as $row ) {
        $the_date = date( $date_format, $row['the_date'] );
        if ( isset( $db_data['notes'][$the_date] ) ) {
            $db_data['notes'][$the_date]++;
        } else {
            $db_data['notes'][$the_date] = 1;
        }
    }
} else { $db_data['notes'] = array(); }

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
        <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_notes' ) . "</td>
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

	if ( isset( $db_data['notes'] ) and array_key_exists( $val, $db_data['notes'] ) ) { $show_count = $db_data['notes'][$val]; } else { $show_count = 0; }

    $data_table_print .= "<tr><td>" . $show_date . "</td>";
	$data_table_print .= "<td class='dt-right'>" . number_format( $show_count ) . "</td></tr>";

}

$data_table_print .= "</tbody></table>";


// chart
$chart_data = array ('categories' => '', 'notes' => '');
$granularity_items = array_reverse( $granularity_items );

foreach ( $granularity_items as $key => $val ) {

    if ( $selectedGranularity == 2 )      { $show_date = substr($val, 0, 4) . " " . lang_get('plugin_MantisStats_week_short') . " " . substr($val, 4); } // Weekly
    elseif ( $selectedGranularity == 3 )  { $show_date = substr($val, 0, 4) . "." . substr($val, 4); } // Monthly
    else { $show_date = $val; }

    if ( isset( $db_data['notes'] ) and array_key_exists( $val, $db_data['notes'] ) ) { $show_count = $db_data['notes'][$val]; } else { $show_count = 0; }

    $chart_data['categories'] .= "<category label='" . $show_date . "' />";
    $chart_data['notes'] .= "<set tooltext='" . number_format( $show_count ) . " [" . $show_date . "]' value='" . $show_count . "' />";
}

$chart_data_print  = "<categories>" . $chart_data['categories'] . "</categories>";
$chart_data_print .= "<dataset seriesName='" . htmlspecialchars( lang_get( 'plugin_MantisStats_notes' ), ENT_QUOTES ) . "'>" . $chart_data['notes'] . "</dataset>";

?>

<script type="text/javascript">
FusionCharts.ready(function () {
    var myChart = new FusionCharts({
      "type": "ScrollColumn2D",
      "renderAt": "chartContainer",
      "width": $( window ).width()/1.25,
      "height": "350",
      "dataFormat": "xml",
      "dataSource": "<chart rotatelabels='1' showlegend='0' palettecolors='#0075c2' bgcolor='#ffffff' showborder='0' showcanvasborder='0' useplotgradientcolor='0' plotborderalpha='10' placevaluesinside='1' valuefontcolor='#ffffff' showaxislines='1' axislinealpha='25' divlinealpha='10' aligncaptionwithcanvas='0' showalternatevgridcolor='0' captionfontsize='14' subcaptionfontsize='14' subcaptionfontbold='0' tooltipcolor='#ffffff' tooltipborderthickness='0' tooltipbgcolor='#000000' tooltipbgalpha='80' tooltipborderradius='2' tooltippadding='5'><?php echo $chart_data_print; ?></chart>"
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
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="MantisStats/trends_by_notes" />
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
            </form>
        </div>


        <div class="chartBox space50Before" />
            <strong>&raquo; <?php echo lang_get( 'notes' ); ?></strong>
            <p />
		    <div id="chartContainer" style="display:inline;"><?php echo lang_get( 'notes' ); ?></div>
        </div>


        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'notes' ); ?></strong>
        <p />
        <?php echo $data_table_print; ?>


        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ) . "<br />"; } ?>
        
        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

        <?php html_page_bottom();?>
</div>
