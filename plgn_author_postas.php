<?php
/*
Plugin Name: Author Post As
Description: Allows author to post as a selected subset of other users
Version: 0.1
Author: James Doc
Author URI: http://jamesdoc.com
*/

/* Disallow direct access to the plugin file */
defined('ABSPATH') || die('Sorry, but you cannot access this page directly.');

if (!class_exists('author_postas')) {
    class author_postas {

        public function __construct() {
            
            add_action( 'show_user_profile', array( $this,'apa_user_edit_can_post_as' ));
            add_action( 'edit_user_profile', array( $this,'apa_user_edit_can_post_as' ));
            
            add_action( 'personal_options_update', array( $this,'apa_save_post_as_data' ));
            add_action( 'edit_user_profile_update', array( $this,'apa_save_post_as_data' ));
            
            add_action( 'add_meta_boxes', array( $this, 'apa_add_postas_meta_box' ) );

        }
		
		
		// Allow admin to select what the user can and can't edit
        public function apa_user_edit_can_post_as( $user ) { 
			
			// Make sure user has permissions to manage the options            
            if ( !current_user_can( 'manage_options' ) ) { return false; }
            
            // Make sure user is not trying to edit themselves
            global $current_user;
            get_currentuserinfo();
            if ($current_user->ID == $user->ID) { return false; }
            
            // Select a list of all blog users
            $all_users = get_users(array('orderby'=>'nicename', 'exclude' => array($user->ID)));
            
            // Select current postas
            $apa_current_postas = get_user_meta($user->ID, 'apa_author_postas', true );
            
            $select = '';
            foreach($all_users as $single_user){
            	if($apa_current_postas && in_array($single_user->ID, $apa_current_postas)) { $checked = 'checked'; } else { $checked = ''; }
                $select .= '<p><label><input name="apa_author_postas[]" type="checkbox" value="' . $single_user->ID . '" '. $checked . ' />' . $single_user->display_name . '</label></p>' . "\n";
            }
            
            echo '<h3>Post As</h3>
            
            <table class="form-table">
                <tr>
                    <th>
                    	<label for="apa_author_postas">User can post as...</label>
                    </th>
                    
                    <td>
                    	<div class="categorydiv"><div class="tabs-panel">'.$select.'</div></div>
                    </td>
                </tr>
            </table>';
        }
        
        // Save options to usermeta
        public function apa_save_post_as_data( $user_id ) {
        	
            // Make sure user has permissions to manage the options  
            if ( !current_user_can( 'manage_options') ) { return false; }
			
			$user = new WP_User($user_id);
			
			// If checkboxes checked then add permissions
			if (isset($_POST['apa_author_postas'])){
				$user->add_cap('list_users');
				$user->add_cap('edit_others_posts');
				$user->add_cap('edit_private_posts');
	            update_user_meta( $user_id, 'apa_author_postas', $_POST['apa_author_postas'] );
            // If checkboxes not checked then make sure that the user has no permissions
            } else {
	            delete_user_meta( $user_id, 'apa_author_postas' );
                $user->remove_cap('list_users');
                $user->remove_cap('edit_others_posts');
				$user->remove_cap('edit_private_posts');
            }
            
        }
        
        
        
        // Add a meta box on the post page
        public function apa_add_postas_meta_box(){

            global $current_user;
            get_currentuserinfo();

            //get author categories
            $postas_users = get_user_meta($current_user->ID, 'apa_author_postas', true);
            
            remove_meta_box( 'authordiv','post','normal' ); // Author Metabox
            
            if (!empty($postas_users) && count($postas_users) > 0){
                
                //add user specific categories
                add_meta_box( 
                    'author_postas',
                    'Post as author',
                    array( &$this, 'apa_postas_meta_box' ),
                    'post',
                    'side',
                    'default'
                );
            }
        }


        
        public function apa_postas_meta_box($post){
            global $current_user;
            get_currentuserinfo();
            $users = get_user_meta($current_user->ID, 'apa_author_postas', true);
            $users = get_users(array('orderby'=>'nicename', 'include' => $users));
            
            echo '<select name="post_author_override">';
            	
            	echo '<option value="' . $current_user->ID . '">' . $current_user->display_name . '</option>';
            	
            foreach($users as $user){
            	if($post->post_author == $user->ID){ $selected = 'selected'; } else { $selected = ''; }
	            echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->display_name . '</option>' . "\n";
            }
            
            echo '</select>';
        }


    }
}

// We can haz admin?
if (is_admin()) {
    $ac = new author_postas();
}