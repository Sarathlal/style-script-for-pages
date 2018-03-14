<?php
if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/*
Plugin Name: Style & Script for Pages
Plugin URI: https://wordpress.org/plugins/page-style-script/
Description: Allow to add custom style & script for each pages in all public post types to customize look & feel in front end. Also global style and script options are available.
Author: Sarathlal N
Version: 1.0
Author URI: https://sarathlal.com/
*/

/**
 * The Plugin Class
 */
class DuduStyleAndScript
{
    public function __construct()
    {
        add_action('init', array($this, 'write_log'));
        add_action('admin_init', array($this, 'newSettings'));
        add_action('admin_menu', array($this, 'createOptionsPage'));
        add_action('add_meta_boxes', array($this, 'addMetaBox'));
        add_action('save_post', array($this, 'saveMeta'));
        add_action('wp_head', array($this, 'pageStyleToWPhead'), 999);
        add_action('wp_footer', array($this, 'pageScriptToWPfooter'), 999);
    }

    function write_log ( $log )  {
  		if ( true === WP_DEBUG ) {
  			if ( is_array( $log ) || is_object( $log ) ) {
  				error_log( print_r( $log, true ) );
  			} else {
  				error_log( $log );
  			}
  		}
  	}


    //Register new settings
    public function newSettings()
    {
        register_setting('styleandscript_group', 'styleandscript_user_role', array($this, 'user_role_validation'));
        register_setting('styleandscript_group', 'styleandscript_disable_page_style', array($this, 'validation_disable_metabox'));
        register_setting('styleandscript_group', 'styleandscript_disable_page_script', array($this, 'validation_disable_metabox'));
        register_setting('styleandscript_group', 'styleandscript_disable_post_type', array($this, 'post_type_validation'));
        register_setting('styleandscript_group', 'styleandscript_global_style', array($this, 'sanitize_html'));
        register_setting('styleandscript_group', 'styleandscript_global_script', array($this, 'sanitize_html'));
    }

    //Create New Option Page
    public function createOptionsPage()
    {
        add_options_page('Style & Script Settings', 'Style & Script', 'manage_options', 'style-script', array($this, 'option_page_content'));
    }

    //Render option Page content
    public function option_page_content()
    {
        ?>
	<div class="wrap">
	  <h1>Style & Script Settings</h1>
	  <form id="submit-data" method="post" action="options.php">
		<?php settings_fields('styleandscript_group'); ?>

		  <table class="form-table">
			<tbody>
			  <?php $user_role = get_option('styleandscript_user_role');
			if (!$user_role) {
				$user_role = "administrator";
			} ?>
				<tr>
				  <th scope="row">
					<label for="">
					  <?php _e('User Group Permission', 'styleandscript'); ?>
					</label>
				  </th>
				  <td>
					<select id='styleandscript_user_role' name='styleandscript_user_role'>
					  <?php wp_dropdown_roles($user_role); ?>
					</select>
					<p class="description">
					  <?php _e('Members with this role can only add page wise style & script.', 'styleandscript'); ?>
					</p>
				  </td>
				</tr>

				<?php $disable_post_type = get_option('styleandscript_disable_post_type');
			$disable_post_type = (array) $disable_post_type; ?>
				  <tr id="disable_post_type">
					<th scope="row">
					  <label for="">
						<?php _e('Disable Style & Script meta box for Post Type', 'styleandscript'); ?>
					  </label>
					</th>
					<td>
					  <?php $args = array('public'   => true );
			$post_types = get_post_types($args);
			foreach ($post_types  as $post_type) {
				?>
						<input type="checkbox" id="disable_post_type_<?php echo $post_type; ?>" name="styleandscript_disable_post_type[]" value="<?php echo $post_type; ?>" <?php echo in_array($post_type, $disable_post_type) ? 'checked' : ''; ?>>
						<label for="disable_post_type_<?php echo $post_type; ?>">
						  <?php echo ucfirst($post_type); ?>
						</label>
						<br>
						<?php
			} ?>
						  <p class="description">
							<?php _e('If disabled, Style & Script meta box  are not avilable for this post type.', 'styleandscript'); ?>
						  </p>
					</td>
				  </tr>

				  <tr>
					<th scope="row">
					  <label for="">
						<?php _e('Disable Style meta box', 'styleandscript'); ?>
					  </label>
					</th>
					<td>
					  <input type="checkbox" id="styleandscript_disable_page_style" name="styleandscript_disable_page_style" value="1" <?php checked( '1', get_option( 'styleandscript_disable_page_style')); ?>>
					  <p class="description">
						<?php _e('If disabled, style meta box not avilable in backend for all post type', 'styleandscript'); ?>
					  </p>
					</td>
				  </tr>

				  <tr>
					<th scope="row">
					  <label for="">
						<?php _e('Disable Script meta box', 'styleandscript'); ?>
					  </label>
					</th>
					<td>
					  <input type="checkbox" id="styleandscript_disable_page_script" name="styleandscript_disable_page_script" value="1" <?php checked( '1', get_option( 'styleandscript_disable_page_script')); ?>>
					  <p class="description">
						<?php _e('If disabled, script meta box not avilable in backend for all post type.', 'styleandscript'); ?>
					  </p>
					</td>
				  </tr>

				  <?php $global_style = get_option('styleandscript_global_style');
          $global_style =  $this->decode_html($global_style);
          ?>
					<tr>
					  <th scope="row">
						<label for="">
						  <?php _e('Style for All Pages', 'styleandscript'); ?>
						</label>
					  </th>
					  <td>
						<textarea name="styleandscript_global_style" id="styleandscript_global_style" style="width:100%;height:12rem" placeholder="<?php _e('Add style without <style>...</style> tags', 'styleandscript'); ?>"><?php
						echo $global_style;
						?></textarea>
						<p class="description">
						  <?php _e('This style will be added in all page header.', 'styleandscript'); ?>
						</p>
					  </td>
					</tr>

					<?php $global_script = get_option('styleandscript_global_script');
          $global_script =  $this->decode_html($global_script);
           ?>
					  <tr>
						<th scope="row">
						  <label for="">
							<?php _e('Script for All Pages', 'styleandscript'); ?>
						  </label>
						</th>
						<td>
						  <textarea name="styleandscript_global_script" id="styleandscript_global_script" style="width:100%;height:12rem" placeholder="<?php _e('Add script without <script>...</script> tags', 'styleandscript'); ?>"><?php
						  echo $global_script;
						  ?></textarea>
						  <p class="description">
							<?php _e('This script will be added in all page footer.', 'styleandscript'); ?>
						  </p>
						</td>
					  </tr>
			</tbody>
		  </table>
		  <?php submit_button(); ?>
	  </form>
	</div>
  <?php
    }

    /**
     * Adds the meta box container.
     */
    public function addMetaBox()
    {
        $args = array('public'   => true );
        $post_types = get_post_types($args);
        $disable_style_metabox = get_option('styleandscript_disable_page_style');
        $disable_script_metabox = get_option('styleandscript_disable_page_script');
        $disable_post_type = get_option('styleandscript_disable_post_type');
        $disable_post_type = (array) $disable_post_type;
        foreach ($post_types  as $post_type) {
            if (!(in_array($post_type, $disable_post_type))) {
                if (!$disable_style_metabox) {
                    add_meta_box('_page_style_meta', __('Style', 'styleandscript'), array($this,'page_style_meta_callback'), $post_type);
                }
                if (!$disable_script_metabox) {
                    add_meta_box('_page_script_meta', __('Script', 'styleandscript'), array($this,'page_script_meta_callback'), $post_type);
                }
            }
        }
    }

    /**
     * Outputs the content of the style meta box
     */
    public function page_style_meta_callback($post)
    {
        wp_nonce_field(basename(__FILE__), 'styleandscript_nonce');
        $styleandscript_stored_data = get_post_meta($post->ID);
        $editable = ($this->check_user_permission() == true) ? "true" : "false";
        if ($editable) {
            echo '<p>';
            echo '<textarea name="page-style" id="page-style" style="width: 100%; height: 6em;" >';
            if (isset($styleandscript_stored_data['pagestyle'])) {
              $data = $styleandscript_stored_data['pagestyle'][0];
              $pagestyle =  $this->decode_html($data);
                echo $pagestyle;
            }
            echo '</textarea>';
            echo '<label for="page-style" class="prfx-row-title">';
            _e("Add Style for this page without tags", 'styleandscript');
            echo '</label>';
            echo '</p>';
        }
    }

    /**
     * Outputs the content of the script meta box
     */
    public function page_script_meta_callback($post)
    {
        wp_nonce_field(basename(__FILE__), 'styleandscript_nonce');
        $styleandscript_stored_data = get_post_meta($post->ID);
        $editable = ($this->check_user_permission() == true) ? "true" : "false";
        if ($editable) {
            echo '<p>';
            echo '<textarea name="page-script" id="page-script" style="width: 100%; height: 6em;" >';
            if (isset($styleandscript_stored_data['pagescript'])) {
                $data = $styleandscript_stored_data['pagescript'][0];
                $pagescript =  $this->decode_html($data);
                echo $pagescript;
            }
            echo '</textarea>';
            echo '<label for="page-style" class="prfx-row-title">';
            _e("Add Script for this page without tags", 'styleandscript');
            echo '</label>';
            echo '</p>';
        }
    }

    /**
     * Save the data
     */
    public function saveMeta($post_id)
    {
        // Checks save status
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($_POST[ 'styleandscript_nonce' ]) && wp_verify_nonce($_POST[ 'styleandscript_nonce' ], basename(__FILE__))) ? 'true' : 'false';

        // Exits script depending on save status
        if ($is_autosave || $is_revision || !$is_valid_nonce) {
            return;
        }

        // Checks for input and saves if needed
        if (isset($_POST[ 'page-style' ])) {
            $data = $_POST[ 'page-style' ];
            $page_style =  $this->sanitize_html($data);
            update_post_meta($post_id, 'pagestyle', $page_style);
        }

        if (isset($_POST[ 'page-script' ])) {
            $data = $_POST[ 'page-script' ];
            $page_script =  $this->sanitize_html($data);
            update_post_meta($post_id, 'pagescript', $page_script);
        }
    }

    //Add the styles on head
    public function pageStyleToWPhead()
    {
        global $post;
        // Retrieves the stored value from the database
        $pagestyle = get_post_meta($post->ID, 'pagestyle', true);

        //Retrieve global style
        $global_style = get_option('styleandscript_global_style');
        $global_style = trim($global_style);

        // Checks and displays the retrieved value
        if (!empty($pagestyle) or !empty($global_style)) {
            echo "<!-- Style & script start here -->";
            echo '<style>';
            echo $global_style;
            echo $pagestyle;
            echo '</style>';
            echo "<!-- Style & script end here -->";
        }
    }

    //Add scripts on footer
    public function pageScriptToWPfooter()
    {
        global $post;
        // Retrieves the stored value from the database
        $pagescript = get_post_meta($post->ID, 'pagescript', true);

        //Retrieve global script
        $global_script = get_option('styleandscript_global_script');
        $global_script = trim($global_script);

        // Checks and displays the retrieved value
        if (!empty($pagescript) or !empty($global_script)) {
            echo "<!-- Style & script start here -->";
            echo '<script type="text/javascript">';
            echo $pagescript;
            echo $global_script;
            echo '</script>';
            echo "<!-- Style & script end here -->";
        }
    }

    //Check current user role & give permission
    public function check_user_permission()
    {
        global $current_user;
        $current_user = wp_get_current_user();
        $current_user_role = $current_user->roles ? $current_user->roles[0] : false;
        //Get plugin user role setting
        $styleandscript_options = get_option('styleandscript_options');
        $setting_value = $styleandscript_options['user_role'];
        if (($setting_value == $current_user_role) or (current_user_can('administrator'))) {
            return true;
        } else {
            return false;
        }
    }

    //User role validation
    function user_role_validation( $data ) {
      $validated = wp_roles()->is_role( $data );
      if (!$validated) {
          $type = 'error';
          $message = __( 'User Group was invalid', 'styleandscript' );
          add_settings_error( 'styleandscript_user_role', esc_attr( 'settings_updated' ), $message, $type );
      } else {
        return $data;
      }
    }

    //Post type validation
    function post_type_validation($data){
      $args = array('public'   => true );
      $post_types = get_post_types($args);
      $validated = $this->in_array_all($data, $post_types);
      if (!$validated) {
          $type = 'error';
          $message = __( 'Post type was invalid', 'styleandscript' );
          add_settings_error( 'disable_post_type', esc_attr( 'settings_updated' ), $message, $type );
      } else {
        return $data;
      }
    }


    function in_array_all($needles, $haystack) {
       return !array_diff($needles, $haystack);
    }

    //validation on Disable metabox option
    function validation_disable_metabox($data) {
      $values = array(1, 0);
      $validated = in_array($data, $values);
      if (!$validated) {
          $type = 'error';
          $message = __( 'Invalid Data', 'styleandscript' );
          add_settings_error( 'Null', esc_attr( 'settings_updated' ), $message, $type );
      } else {
        return $data;
      }
    }

    //Sanitize & validate global styles
    function sanitize_html($data){
      $data = wp_kses_post( $data );
      $data  = htmlentities($data);
      //$data = base64_encode( $data );
      return $data;
    }

    //Decode html
    function decode_html($data){
      $data = html_entity_decode($data);
      //$data = base64_decode($data);
      return $data;
    }


}

$StyleAndScript = new DuduStyleAndScript;
