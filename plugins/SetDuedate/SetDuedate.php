<?php

class SetDuedatePlugin extends MantisPlugin {

	function register() {
		$this->name = 'SetDuedate';    
		$this->description = 'Ability to set duedate upon registering';    
		$this->page = 'config';           
		$this->version = '3.30';     
		$this->requires = array( 'MantisCore' => '1.2.0', );
		$this->author = 'Cas Nuy / Istvan Baktai';        
		$this->contact = '';       
		$this->url = '';           
	}

	function config() {
		return array(
			'duedate_days_default'	   => 14,
			'duedate_days_priority_10' => 28,		//default due date for priority NONE
			'duedate_days_priority_20' => 21,		//default due date for priority LOW
			'duedate_days_priority_30' => 6,		//default due date for priority NORMAL
			'duedate_days_priority_40' => 3,		//default due date for priority HIGH
			'duedate_days_priority_50' => 2,		//default due date for priority URGENT
			'duedate_days_priority_60' => 1,		//default due date for priority IMMEDIATE
			'duedate_overrule'		   => OFF,		//System  will overrule (@ each update evaluated)
			);
	}


	function init() { 
		plugin_event_hook( 'EVENT_REPORT_BUG_DATA', 'setDate' );
		plugin_event_hook( 'EVENT_UPDATE_BUG', 'ChangeDate' );
	}

 	function ChangeDate($p_event,$p_bug_data, $p_bug_id) {
		if (substr($_SERVER['HTTP_REFERER'],-19) <> 'bug_update_page.php'){
			return;
		}
		$overrule	= config_get( 'plugin_SetDuedate_duedate_overrule' );
		if ( OFF == $overrule ) {
			return;
		}
		$days=0;
		// first check if there is a definition on category level
		$cat=$p_bug_data->category_id;
		$cat_def_table	= plugin_table('defined');
		$query = "select * from $cat_def_table where id=$cat";
		$result= db_query_bound($query);
		while ($row1= db_fetch_array($result)){
				$days  = $row1['days'. $p_bug_data->priority];
		}
		// if nothing found, we use the default Priority based calculation
		if ($days == 0){
			$priorities = array(10, 20, 30, 40, 50,60);
			if (in_array($p_bug_data->priority, $priorities)) {
				$days = plugin_config_get('duedate_days_priority_' . $p_bug_data->priority);
			} else {
				$days = plugin_config_get('duedate_days_default');
			}
		}
		$bookdate=$p_bug_data->date_submitted;
		$date_info  = getdate( $bookdate );
		if (($date_info["hours"]>20)  and ($date_info["hours"]<=23) and ($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0)) {
                                $bookdate += (11+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
                        elseif(($date_info["hours"]>=0) and ($date_info["hours"]<11) and ($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0)) {
                                $bookdate += (11-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
                        elseif (($date_info["hours"]>=20) and ($date_info["hours"]<=23) and ($date_info["wday"] == 5)) {
                                $bookdate += (59+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
                        elseif (($date_info["wday"] == 6)) {
                                $bookdate += (35+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
                        elseif (($date_info["wday"] == 0)) {
                                $bookdate += (11+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
		$i = 1;
		while ($i <= $days) {
			$bookdate += 3600; // Add a hour.
			$date_info  = getdate( $bookdate );
                        if (($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0) and ((($date_info["hours"] == 20) and ($date_info["minutes"]>0)) or ($date_info["hours"] == 21)) )  {
                                                $bookdate += (11+24-$date_info["hours"])*3600;
                                                continue;
                                        }
                                        elseif (($date_info["wday"] == 5) and ((($date_info["hours"] == 20) and ($date_info["minutes"]>0)) or ($date_info["hours"] == 21)))  {
                                                $bookdate += (59+24-$date_info["hours"])*3600;
                                                continue;
					}
			$i++;
		}
		$p_bug_data->due_date = $bookdate;
		return $p_bug_data;
	}


	function setDate($p_event,$p_bug_data) {
		if ($p_bug_data->due_date == date_get_null() or $p_bug_data->due_date < time())
			{
			$days=0;
			// first check if there is a definition on category level
			$cat=$p_bug_data->category_id;
			$cat_def_table	= plugin_table('defined');
			$query = "select * from $cat_def_table where id=$cat";
			$result= db_query_bound($query);
			while ($row1= db_fetch_array($result)){
					$days  = $row1['days'. $p_bug_data->priority];
			}
			// if nothing found, we use the default Priority based calculation
			if ($days == 0){
				$priorities = array(10, 20, 30, 40, 50,60);
				if (in_array($p_bug_data->priority, $priorities)) {
					$days = plugin_config_get('duedate_days_priority_' . $p_bug_data->priority);
				} else {
					$days = plugin_config_get('duedate_days_default');
				}
			}
			$bookdate= time() ;
			$date_info  = getdate( $bookdate );
                        if (($date_info["hours"]>20)  and ($date_info["hours"]<=23) and ($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0)) {
				$bookdate += (11+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
			}
                        elseif(($date_info["hours"]>=0) and ($date_info["hours"]<11) and ($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0)) {
                        	$bookdate += (11-$date_info["hours"])*3600-$date_info["minutes"]*60; 
			}
                        elseif (($date_info["hours"]>=20) and ($date_info["hours"]<=23) and ($date_info["wday"] == 5)) {
                                $bookdate += (59+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
			elseif (($date_info["wday"] == 6)) {
                                $bookdate += (35+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
			elseif (($date_info["wday"] == 0)) {
                                $bookdate += (11+24-$date_info["hours"])*3600-$date_info["minutes"]*60;
                        }
			$i = 1;
			while ($i <= $days) {
				$bookdate += 3600; // Add a day.
				$date_info  = getdate( $bookdate );
                        		if (($date_info["wday"] != 5) and ($date_info["wday"] != 6) and ($date_info["wday"] != 0) and ((($date_info["hours"] == 20) and ($date_info["minutes"]>0)) or ($date_info["hours"] == 21)) )  {
		                                $bookdate += (11+24-$date_info["hours"])*3600;
                		                continue;
                        		}
					elseif (($date_info["wday"] == 5) and ((($date_info["hours"] == 20) and ($date_info["minutes"]>0)) or ($date_info["hours"] == 21)))  {
                                		$bookdate += (59+24-$date_info["hours"])*3600;
		                                continue;				
					}	
				$i++;
			}
			$p_bug_data->due_date = $bookdate;
		return $p_bug_data;
		}
	}
	
	function schema() {
		return array(
			array( "CreateTableSQL", array( plugin_table("defined"), "
				id			I		NOTNULL UNSIGNED  PRIMARY,
				days10		I		NOTNULL UNSIGNED,
				days20		I		NOTNULL UNSIGNED,
				days30		I		NOTNULL UNSIGNED,
				days40		I		NOTNULL UNSIGNED,
				days50		I		NOTNULL UNSIGNED,
				days60		I		NOTNULL UNSIGNED
				"  ),  ),
		);
	} 	

}
