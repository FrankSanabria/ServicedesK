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


    // Code based on the excel_xml_export.php from MantisBT
	require_once( 'core.php' );

	require_once( 'current_user_api.php' );
	require_once( 'bug_api.php' );
	require_once( 'string_api.php' );
	require_once( 'columns_api.php' );
	require_once( 'excel_api.php' );

	require( 'print_all_bug_options_inc.php' );
	
	auth_ensure_user_authenticated();

	$t_required_level = plugin_config_get('export_access_level_threshold');
	access_ensure_project_level( $t_required_level );
	
	$f_export = gpc_get_string( 'export', '' );

	helper_begin_long_process();
	
	# This is where we used to do the entire actual filter ourselves
	$t_page_number = gpc_get_int( 'page_number', 1 );
	$t_per_page = 100;
	$t_bug_count = null;
	$t_page_count = null;

	$result = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
	if ( $result === false ) {
		print_header_redirect( 'view_all_set.php?type=0&print=1' );
	}
	
	$t_export_title = excel_get_default_filename();
	
	header( 'Content-Type: application/zip; charset=UTF-8' );
	header( 'Pragma: public' );
	header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_export_title ) ) . '.zip"' ) ;

	$f_bug_arr = explode( ',', $f_export );
	
	$file = tempnam("tmp", "zip");
	$zip = new ZipArchive();
	$zip->open($file, ZIPARCHIVE::CREATE);

	$t_user_id = auth_get_current_user_id();
	
	$t_bug_file_table = db_get_table( 'mantis_bug_file_table' );
	
	$t_fields = config_get( 'bug_view_page_fields' );
	$t_fields = columns_filter_disabled( $t_fields );
	
	do
	{
		$t_more = true;
		$t_row_count = count( $result );
		
		$row_number = 0;

		for( $i = 0; $i < $t_row_count; $i++ ) {
			$t_row = $result[$i];
			$t_bug = null;

			if ( is_blank( $f_export ) || in_array( $t_row->id, $f_bug_arr ) ) {
			    
			    $t_issue_contents = '<html><head><style type="text/css">
			        body, h1, h2, td { font-size: 12px; } 
			        td { vertical-align: top; padding: 3px }
			        table, tr, td { border: 1px solid black }
			        table { border-collapse: collapse }
			    </style></head><body>';
			    
			    $t_bug = bug_get($t_row->id, true /* get extended */);
			    
			    // summary
			    $t_issue_contents .= "<h1>Bug #". $t_bug->id .": ".$t_bug->summary."</h1>";
			    // link
			    $t_issue_link = config_get_global( 'path' ) . string_get_bug_view_url( $t_bug->id );
			    $t_issue_contents .= "<p><a href=\"$t_issue_link\">$t_issue_link</a></p>";
			    
			    // description, steps to reproduce, additional info
			    $t_issue_contents .= '<p>' . lang_get( 'description' ) . ' : ' . $t_bug->description . ' </p>';
			    $t_issue_contents .= '<p>' . lang_get( 'steps_to_reproduce' ) . ' : ' . $t_bug->steps_to_reproduce . ' </p>';
			    $t_issue_contents .= '<p>' . lang_get( 'additional_information' ) . ' : ' . $t_bug->additional_information . ' </p>';
			    
			    // simple text fields
			    $t_issue_contents .= '<table>';
			    $t_issue_contents .= '<tr><td>' . lang_get('email_project') .'</td><td>'  . project_get_name( $t_bug->project_id ) .'</td></tr>';			                                
			    $t_issue_contents .= '<tr><td>' . lang_get('category') .'</td><td>'  . category_full_name( $t_bug->category_id ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('reporter') .'</td><td>'  . prepare_user_name( $t_bug->reporter_id ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('assigned_to') .'</td><td>'  . prepare_user_name( $t_bug->handler_id ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('priority') .'</td><td>'  . get_enum_element( 'priority', $t_bug->priority ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('severity') .'</td><td>'  . get_enum_element( 'severity', $t_bug->severity ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('reproducibility') .'</td><td>'  . get_enum_element( 'reproducibility', $t_bug->reproducibility ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('status') .'</td><td>'  . get_enum_element( 'status', $t_bug->status ) .'</td></tr>';
			    $t_issue_contents .= '<tr><td>' . lang_get('resolution') .'</td><td>'  . get_enum_element( 'resolution', $t_bug->resolution ) .'</td></tr>';
			    
			    // versions
			    $t_version_rows = version_get_all_rows( $t_bug->project_id );
		        $t_product_version_string  = string_display_line ( prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->version, $t_bug->project_id ), $t_version_rows ) );
		        $t_issue_contents .= '<tr><td>' . lang_get('product_version') .'</td><td>'  . $t_product_version_string .'</td></tr>';
		        
		        if ( access_has_bug_level( config_get( 'roadmap_view_threshold' ), $t_bug->id ) )  {
		            $t_target_version_string  = string_display_line ( prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->target_version, $t_bug->project_id ), $t_version_rows ) );
		            $t_issue_contents .= '<tr><td>' . lang_get('target_version') .'</td><td>'  . $t_target_version_string .'</td></tr>';
		        }
		        
		        $t_fixed_in_version_string  = string_display_line ( prepare_version_string( $t_bug->project_id, version_get_id( $t_bug->fixed_in_version, $t_bug->project_id ), $t_version_rows ) );
		        $t_issue_contents .= '<tr><td>' . lang_get('fixed_in_version') .'</td><td>'  . $t_fixed_in_version_string .'</td></tr>';
			    
		        // profile
			    if ( config_get( 'enable_profiles' ) ) {
			        $t_issue_contents .= '<tr><td>' . lang_get('platform') .'</td><td>'  . $t_bug->platform .'</td></tr>';
			        $t_issue_contents .= '<tr><td>' . lang_get('os') .'</td><td>'  .  $t_bug->os  .'</td></tr>';
			        $t_issue_contents .= '<tr><td>' . lang_get('os_version') .'</td><td>'  . $t_bug->os_version .'</td></tr>';
			    }
			    
			    // custom fields
			    $t_related_custom_field_ids = custom_field_get_linked_ids( $t_bug->project_id );
			    
			    foreach( $t_related_custom_field_ids as $t_id ) {
			        if ( !custom_field_has_read_access( $t_id, $t_bug->id ) )
			            continue;
			    
			        $t_def = custom_field_get_definition( $t_id );
			        
			        $t_issue_contents .= '<tr><td>' . lang_get_defaulted( $t_def['name'] ) .'</td><td>'  . string_custom_field_value( $t_def, $t_id, $t_bug->id ) .'</td></tr>';
			    }
			    
			    $t_issue_contents .= '</table>';
			    
			    // relationships
			    $t_relationships = relationship_get_all_src( $t_bug->id, false );
			    if ( count ( $t_relationships ) > 0 ) {
			        
			        $t_issue_contents .= '<h2>'. lang_get( 'bug_relationships' ) . '</h2>';
			        
			        $t_issue_contents .= '<table>';
			        foreach ( $t_relationships as $t_relationship ) {
			            $t_issue_contents .= '<tr><td>' . relationship_get_description_src_side ( $t_relationship->type ) .'</td><td>'  . $t_relationship->dest_bug_id . ' - ' . bug_get_field($t_relationship->dest_bug_id,'summary') .'</td></tr>';
			        }
			        $t_issue_contents .= '</table>';
			    }
			    
			    // bug notes
			    
			    $t_bugnotes = bugnote_get_all_visible_bugnotes( $t_bug->id , 'DESC', 0);
			    
			    if ( count ( $t_bugnotes ) > 0 ) {
			        
			        $t_issue_contents .= '<h2>'. lang_get( 'bug_notes_title' ) . '</h2>';
			        
			        $t_normal_date_format = config_get( 'normal_date_format' );
			        
			        $t_issue_contents .= '<table>';
			        
			        foreach ( $t_bugnotes as $t_bugnote ) {
			            
			            $t_issue_contents .= '<tr><td>' . prepare_user_name($t_bugnote->reporter_id) . '<br >'.
			                  date( $t_normal_date_format, $t_bugnote->date_submitted ) . '</td><td>'  . string_display_links($t_bugnote->note) .'</td></tr>';
			        }
			        
			        $t_issue_contents .= '</table>';
			    }
			    
			    $t_issue_contents .= "</body></html>";
			    
			    $zip->addFromString($t_row->id .'/bug.doc', $t_issue_contents);
			    
			    if ( file_can_download_bug_attachments( $t_row->id, $t_user_id ) ) {
    			    $t_attachments = bug_get_attachments( $t_row->id );
    			    if ( count ( $t_attachments ) > 0 ) {
    			        foreach ( $t_attachments as $t_attachment ) {
    			            
    			            $t_file_contents;
    			            
    			            switch ( config_get( 'file_upload_method' ) ) {
    			                
    			                case DISK:
    			                    $t_local_disk_file = file_normalize_attachment_path( $t_attachment['diskfile'], $t_project_id );
    			                    $t_file_contents = file_get_contents($t_local_disk_file);
    			                    break;
    			                    
    			                case FTP:
    			                    $t_local_disk_file = file_normalize_attachment_path( $t_attachment['diskfile'], $t_project_id );
    			                    
    			                    if ( !file_exists( $t_local_disk_file ) ) {
    			                        $ftp = file_ftp_connect();
    			                        file_ftp_get ( $ftp, $t_local_disk_file, $t_attachment['diskfile'] );
    			                        file_ftp_disconnect( $ftp );
    			                    }
    			                    $t_file_contents = file_get_contents($t_local_disk_file);
    			                    break;
    			                    
    			                case DATABASE:
    			                    
    			                    $t_content_query =  "SELECT content FROM $t_bug_file_table WHERE id=" . db_param();
    			                    $t_content_result = db_query_bound( $t_content_query, Array( $t_attachment['id'] ) );
    			                    $t_content_row = db_fetch_array( $t_content_result );
    			                    $t_file_contents = $t_content_row['content'];
    			                    break;
    			                  
    			            }
    			            
    			            $zip->addFromString($t_row->id.'/attachments/' . $t_attachment['filename'], $t_file_contents);
    			        }
    			    }
			    }
			} #in_array
		} #for loop

		// If got a full page, then attempt for the next one.
		// @@@ Note that since we are not using a transaction, there is a risk that we get a duplicate record or we miss
		// one due to a submit or update that happens in parallel.
		if ( $t_row_count == $t_per_page ) {
			$t_page_number++;
			$t_bug_count = null;
			$t_page_count = null;

			$result = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
			if ( $result === false ) {
				$t_more = false;
			}
		} else {
			$t_more = false;
		}
	} while ( $t_more );
	
	$zip->close();
	
	readfile( $file );

	unlink ( $file );