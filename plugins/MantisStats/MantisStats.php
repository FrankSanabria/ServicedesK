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


class MantisStatsPlugin extends MantisPlugin {

    // Plugin definition
	function register() {
		$this->name         = lang_get( 'plugin_MantisStats_title' );
		$this->description  = lang_get ( 'plugin_MantisStats_description' );
		$this->page         = 'config_admin';

		$this->version      = '2.2.0';
		$this->requires     = array('MantisCore' => '1.2.0');

		$this->author       = 'Avetis Avagyan';
		$this->contact      = 'plugin.support@mantisstats.org';
		$this->url          = 'https://www.mantisstats.org';
	}

    // Plugin configuration
	function config() {
		return array(
            'menu_location'     => 'EVENT_MENU_SUMMARY', // 'EVENT_MENU_SUMMARY' to be under Summary; 'EVENT_MENU_MAIN' to be menu item itself
            'access_threshold'  => DEVELOPER
		);
	}

    // Add menu item
	function showreport_menu() {
            if ( access_has_global_level( plugin_config_get( 'access_threshold' ) ) ) {
			return array( '<a href="' . plugin_page( 'start_page' ) . '">' . lang_get( 'plugin_MantisStats_title' ) . '</a>' );
		}
	}

    // Schema definition
    function schema() {
        return array(

            array( 'CreateTableSQL', array( plugin_table( 'chart_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                chart_config        I       DEFAULT NULL UNSIGNED
                " )
            ),
            array( 'CreateTableSQL', array( plugin_table( 'tblrows_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                tblrows_config      I       DEFAULT NULL UNSIGNED
                " )
            ),
            array( 'CreateTableSQL', array( plugin_table( 'misc_config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id             I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                report_id           I       DEFAULT NULL UNSIGNED,
                admin               I       DEFAULT NULL UNSIGNED,
                value_misc_config   I       DEFAULT NULL UNSIGNED,
                type_misc_config    I       DEFAULT NULL UNSIGNED
                " )
            ),

            array( 'DropTableSQL', array( plugin_table( 'chart_config' ) ) ),
            array( 'DropTableSQL', array( plugin_table( 'tblrows_config' ) ) ),
            array( 'DropTableSQL', array( plugin_table( 'misc_config' ) ) ),

            array( 'CreateTableSQL', array( plugin_table( 'config' ), "
                id                  I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                config_name         C(255)  NOTNULL,
                config_int_value    I       DEFAULT NULL,
                config_char_value   XL      DEFAULT NULL,
                config_extra_value  C(255)  DEFAULT NULL,
                report_id           I       DEFAULT NULL UNSIGNED,
                project_id          I       DEFAULT NULL UNSIGNED,
                user_id             I       DEFAULT NULL UNSIGNED,
                is_default          I       NOTNULL UNSIGNED
                " )
            ),

        );
    }

    // Plugin hooks
    function hooks() {
        $tmp = self::config();
        return array(
            $tmp['menu_location'] => 'showreport_menu',
            'EVENT_LAYOUT_RESOURCES'    => 'resources',
        );
    }

    // Loading needed styles and javascripts
    function resources() {
        if ( is_page_name( 'plugin.php' ) ) {
            return
                "
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'main.css?v2.0.0' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'datatables-min.css' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'fancyselect.css' ) . "'>
                    <link rel='stylesheet' type='text/css' href='" . plugin_file( 'jquery-ui-min.css' ) . "'>

                    <script src='" . plugin_file( 'jquery-min.js' ) . "'></script>
                    <script src='" . plugin_file( 'jquery-ui-min.js' ) . "'></script>
                    <script src='" . plugin_file( 'datatables-min.js' ) . "'></script>
                    <script src='" . plugin_file( 'fancyselect.js' ) . "'></script>
                    <script src='" . plugin_file( 'fusioncharts.js' ) . "'></script>
                    <script src='" . plugin_file( 'fusioncharts_theme_fint.js' ) . "'></script>
                ";
        }
    }

}

?>
