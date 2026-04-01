<?php
  /**
   * Plugin Name: Church Branches and Programs Generator
   * Description: Create, edit, and manage pages for each branch location. Configure events and programs for each branch, with ease. Created for the Foundation of the Rock Church.
   * Version: 1.1.0
   * Author: David Cai, add your names here
   * License: GPL-2.0+
   * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
   * Text Domain: church-branches-generator
   * Domain Path: /languages
   */
  
  // If this file is called directly, abort.
  if (!defined('WPINC')) {
      die;
  }
  
   define('CHURCH_BRANCHES_GENERATOR_VERSION', '1.0.0');
   define('CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
   define('CHURCH_BRANCHES_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

   if (!defined('CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX')) {
       define('CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX', 'fotr_church_');
   }
  
  
  function activate_church_branches_generator() {
      require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-activator.php';
      Church_Branches_Generator_Activator::activate();
  }
  register_activation_hook(__FILE__, 'activate_church_branches_generator');
  
  
  
  function deactivate_church_branches_generator() {
      require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-deactivator.php';
      Church_Branches_Generator_Deactivator::deactivate();
  }
  register_deactivation_hook(__FILE__, 'deactivate_church_branches_generator');
  
  
   require CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-plugin.php';
   require CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-branch-handler.php';
   require CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-shortcodes.php';
   
   function run_church_branches_generator() {
       $plugin = new Church_Branches_Generator_Plugin();
       $plugin->run();
   }
   run_church_branches_generator();
  

  class Branch_Creator {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    // Add the menu item to the dashboard
    public function add_admin_menu() {
        add_menu_page(
            'Create New Branch',  // Page Title
            'Branch Creator',     // Menu Title
            'manage_options',     // Capability
            'branch-creator',     // Menu Slug
            array( $this, 'render_admin_page' ), // Callback function
            'dashicons-admin-page', // Icon
            100 // Position
        );
    }

    // Render the Admin Page Form
    public function render_admin_page() {
        // Check if the form was submitted
        if ( isset( $_POST['bc_submit_branch'] ) && isset( $_POST['bc_nonce_field'] ) ) {
            // Verify Nonce for security
            if ( wp_verify_nonce( $_POST['bc_nonce_field'], 'bc_create_branch_nonce' ) ) {
                $this->process_branch_creation();
            } else {
                echo '<div class="notice notice-error"><p>Security check failed. Try again later.</p></div>';
            }
        }

        ?>

        <div class="wrap">
            <h1>Create New Branch Page</h1>
            <p>Fill out the details below to generate a new page for a branch location.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'bc_create_branch_nonce', 'bc_nonce_field' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="branch_name">Branch Name <italic>(Branch name only, do not add State Branch)</italic><b>(e.g. Lagos)</b></label></th>
                        <td><input name="branch_name" type="text" label id="branch_name" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="address">Address (e.g. 123 Lagos Street)</label></th>
                        <td><input name="address" type="text" id="address" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone">Phone (e.g. 123-456-7890)</label></th>
                        <td><input name="phone" type="text" id="phone" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email (e.g. example@email.com)</label></th>
                        <td><input name="email" type="email" id="email" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_times">Service Times</label></th>
                        <td><input name="service_times" type="text" id="service_times" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lead_pastor">Lead Pastor</label></th>
                        <td><input name="lead_pastor" type="text" id="lead_pastor" value="" class="regular-text" required></td>
                    </tr>
                </table>
                
                <?php submit_button( 'Create Branch Page', 'primary', 'bc_submit_branch' ); ?>
            </form>
        </div>
        <?php
    }

    // Process the form and create the page
    private function process_branch_creation() {
        // Sanitize Inputs
        $branch_name   = sanitize_text_field( $_POST['branch_name'] );
        $address       = sanitize_text_field( $_POST['address'] );
        $phone         = sanitize_text_field( $_POST['phone'] );
        $email         = sanitize_email( $_POST['email'] );
        $service_times = sanitize_text_field( $_POST['service_times'] );
        $lead_pastor   = sanitize_text_field( $_POST['lead_pastor'] );

        // Check if branch already exists
        $branch_handler = new Church_Branches_Generator_Branch_Handler();
        if ( $branch_handler->branch_exists( $branch_name ) ) {
            echo '<div class="notice notice-error"><p>A branch with this name already exists.</p></div>';
            return;
        }

        // Create WordPress page with shortcode placeholder
        $parent_page = get_page_by_path( 'website' );
        $parent_id = $parent_page ? $parent_page->ID : 0;

        $page_data = array(
            'post_title'    => $branch_name,
            'post_content'  => '[church_branch id="{branch_id}"]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id(),
            'post_parent'   => $parent_id,
            'post_name'     => sanitize_title( $branch_name ) . '-branch'
        );

        // Insert page first (we need page_id for the branch record)
        $page_id = wp_insert_post( $page_data );

        if ( is_wp_error( $page_id ) ) {
            echo '<div class="notice notice-error"><p>There was an error creating the page. Please try again.</p></div>';
            return;
        }

        // Save branch data to database
        $branch_id = $branch_handler->create_branch( array(
            'branch_name'   => $branch_name,
            'address'       => $address,
            'phone'         => $phone,
            'email'         => $email,
            'service_times' => $service_times,
            'lead_pastor'   => $lead_pastor,
            'page_id'       => $page_id,
        ) );

        if ( is_wp_error( $branch_id ) ) {
            // Clean up the page if branch creation failed
            wp_delete_post( $page_id, true );
            echo '<div class="notice notice-error"><p>There was an error saving branch data. Please try again.</p></div>';
            return;
        }

        // Update page content with actual branch ID
        wp_update_post( array(
            'ID'           => $page_id,
            'post_content' => '[church_branch id="' . $branch_id . '"]',
        ) );

        // Success Message
        $link = get_permalink( $page_id );
        echo '<div class="notice notice-success is-dismissible">';
        echo "<p>Branch created successfully! <a href='{$link}' target='_blank'>View Page</a></p>";
        echo '</div>';
    }
}

// Initialize the Plugin
new Branch_Creator();

