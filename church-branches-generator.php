<?php
  /**
   * Plugin Name: Church Branches and Programs Generator
   * Description: Create, edit, and manage pages for each branch location. Includes placeholders and Media Library integration.
   * Version: 1.7.0
   * Author: David Cai, Pavansrivatsa Meka
   * License: GPL-2.0+
   */
  
  if (!defined('WPINC')) { die; }
  
  define('CHURCH_BRANCHES_GENERATOR_VERSION', '1.7.0');
  define('CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
  define('CHURCH_BRANCHES_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
  
  require CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-plugin.php';
  function run_church_branches_generator() {
      $plugin = new Church_Branches_Generator_Plugin();
      $plugin->run();
  }
  run_church_branches_generator();
  
  class Branch_Creator {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_scripts' ) );
    }

    public function enqueue_media_scripts($hook) {
        if ( 'toplevel_page_branch-creator' !== $hook ) { return; }
        wp_enqueue_media();
    }

    public function add_admin_menu() {
        add_menu_page('Branch Creator', 'Branch Creator', 'manage_options', 'branch-creator', array( $this, 'render_admin_page' ), 'dashicons-admin-page', 100);
        add_submenu_page('branch-creator', 'All Branches', 'All Branches', 'manage_options', 'branch-list', array($this, 'render_branch_list'));
    }

    public function render_branch_list() {
        $pages = get_pages(array('meta_key' => '_is_church_branch', 'meta_value' => 'yes'));
        echo '<div class="wrap"><h1>Manage Branches</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Branch</th><th>Actions</th></tr></thead><tbody>';
        foreach($pages as $page) {
            $edit_url = admin_url('admin.php?page=branch-creator&edit_id=' . $page->ID);
            echo "<tr><td><strong>{$page->post_title}</strong></td><td><a href='{$edit_url}'>Edit Details</a> | <a href='".get_permalink($page->ID)."' target='_blank'>View</a></td></tr>";
        }
        echo '</tbody></table></div>';
    }

    public function render_admin_page() {
        $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
        $meta = [];
        if($edit_id) {
            $meta['name'] = get_the_title($edit_id);
            $meta['address'] = get_post_meta($edit_id, '_br_address', true);
            $meta['phone'] = get_post_meta($edit_id, '_br_phone', true);
            $meta['email'] = get_post_meta($edit_id, '_br_email', true);
            $meta['times'] = get_post_meta($edit_id, '_br_times', true);
            $meta['pastor'] = get_post_meta($edit_id, '_br_pastor', true);
            $meta['acts'] = get_post_meta($edit_id, '_br_activities', true);
            $meta['img_id'] = get_post_meta($edit_id, '_br_hero_id', true);
            $meta['img_url'] = wp_get_attachment_url($meta['img_id']);
        }

        if ( isset( $_POST['bc_submit_branch'] ) && wp_verify_nonce( $_POST['bc_nonce_field'], 'bc_create_branch_nonce' ) ) {
            $this->process_branch_creation($edit_id);
        }
        ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? 'Edit' : 'Create New'; ?> Branch</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'bc_create_branch_nonce', 'bc_nonce_field' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Branch Name</th>
                        <td><input name="branch_name" type="text" value="<?php echo @$meta['name']; ?>" class="regular-text" placeholder="e.g. Lagos" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Hero Image</th>
                        <td>
                            <div id="branch_image_preview" style="margin-bottom:10px;">
                                <?php if(!empty($meta['img_url'])): ?>
                                    <img src="<?php echo $meta['img_url']; ?>" style="max-width:200px; height:auto; display:block; border:1px solid #ccc;">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="branch_image_id" id="branch_image_id" value="<?php echo @$meta['img_id']; ?>">
                            <button type="button" class="button" id="upload_image_button">Select Image from Media Library</button>
                            <button type="button" class="button" id="remove_image_button" style="<?php echo empty($meta['img_url']) ? 'display:none;' : ''; ?>">Remove</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Address</th>
                        <td><input name="address" type="text" value="<?php echo @$meta['address']; ?>" class="regular-text" placeholder="e.g. 123 Lagos Street, Ikeja" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Phone</th>
                        <td><input name="phone" type="text" value="<?php echo @$meta['phone']; ?>" class="regular-text" placeholder="e.g. +234 800 000 0000" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Email</th>
                        <td><input name="email" type="email" value="<?php echo @$meta['email']; ?>" class="regular-text" placeholder="e.g. lagos@church.com" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Service Times (Brief)</th>
                        <td><input name="service_times" type="text" value="<?php echo @$meta['times']; ?>" class="regular-text" placeholder="e.g. Sundays at 9:00 AM" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Lead Pastor</th>
                        <td><input name="lead_pastor" type="text" value="<?php echo @$meta['pastor']; ?>" class="regular-text" placeholder="e.g. Pastor John Doe" required></td>
                    </tr>
                </table>

                <hr>
                <h3>Weekly Services & Activities</h3>
                <div id="services-container">
                    <?php 
                    $acts = !empty($meta['acts']) ? $meta['acts'] : [['t'=>'','d'=>'']];
                    foreach($acts as $act): ?>
                    <div class="service-group" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; background: #fff;">
                        <input name="service_titles[]" type="text" placeholder="Activity Title (e.g. Bible Study)" value="<?php echo $act['t']; ?>" class="regular-text" style="display:block; margin-bottom:5px;">
                        <input name="service_descs[]" type="text" placeholder="Description (e.g. Tuesdays at 6:00 PM)" value="<?php echo $act['d']; ?>" class="regular-text" style="display:block;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-service" class="button">+ Add Another Activity</button>
                <?php submit_button( $edit_id ? 'Update Branch' : 'Create Branch', 'primary', 'bc_submit_branch' ); ?>
            </form>
        </div>

        <script>
            document.getElementById('add-service').addEventListener('click', function() {
                var container = document.getElementById('services-container');
                var div = document.createElement('div');
                div.className = 'service-group';
                div.style = "margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; background: #fff;";
                div.innerHTML = '<input name="service_titles[]" type="text" placeholder="Activity Title" class="regular-text" style="display:block; margin-bottom:5px;"><input name="service_descs[]" type="text" placeholder="Description" class="regular-text" style="display:block;">';
                container.appendChild(div);
            });

            jQuery(document).ready(function($){
                var mediaUploader;
                $('#upload_image_button').click(function(e) {
                    e.preventDefault();
                    if (mediaUploader) { mediaUploader.open(); return; }
                    mediaUploader = wp.media({
                        title: 'Select Branch Hero Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#branch_image_id').val(attachment.id);
                        $('#branch_image_preview').html('<img src="' + attachment.url + '" style="max-width:200px; height:auto; display:block; border:1px solid #ccc;">');
                        $('#remove_image_button').show();
                    });
                    mediaUploader.open();
                });
                $('#remove_image_button').click(function(){
                    $('#branch_image_id').val('');
                    $('#branch_image_preview').empty();
                    $(this).hide();
                });
            });
        </script>
        <?php
    }

    private function process_branch_creation($edit_id = 0) {
        $branch_name   = sanitize_text_field( $_POST['branch_name'] );
        $address       = sanitize_text_field( $_POST['address'] );
        $phone         = sanitize_text_field( $_POST['phone'] );
        $email         = sanitize_email( $_POST['email'] );
        $service_times = sanitize_text_field( $_POST['service_times'] );
        $lead_pastor   = sanitize_text_field( $_POST['lead_pastor'] );
        $attachment_id = intval( $_POST['branch_image_id'] );
        $hero_image_url = wp_get_attachment_url($attachment_id);

        $services_html = '';
        $meta_activities = [];
        if (isset($_POST['service_titles'])) {
            for ($i = 0; $i < count($_POST['service_titles']); $i++) {
                $stitle = sanitize_text_field($_POST['service_titles'][$i]);
                $sdesc  = sanitize_text_field($_POST['service_descs'][$i]);
                if (!empty($stitle)) {
                    $services_html .= "<div class='service-row'><h4>{$stitle}</h4><p>{$sdesc}</p></div>";
                    $meta_activities[] = ['t' => $stitle, 'd' => $sdesc];
                }
            }
        }

        $url = CHURCH_BRANCHES_GENERATOR_PLUGIN_URL . 'public/images/';
        $location_img = $url . 'ion_location-outline.png';
        $phone_img    = $url . 'mingcute_phone-fill.png';
        $email_img    = $url . 'ic_baseline-email.png';
        $clock_img    = $url . 'ic_baseline-access-time.png';
        $pastor_img   = $url . 'ic_baseline-person.png';

        $page_content = <<<HTML
        <style>.branch-hero { background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('{$hero_image_url}'); background-size: cover; background-position: center; }</style>
        <main class="branch-page-wrapper">
          <section class="branch-hero" role="banner"><div class="hero-content"><h1>{$branch_name} State Branch</h1><p>The {$branch_name} State branch is a vibrant community of believers committed to spreading the gospel and serving the community with love and compassion.</p></div></section>
          <section class="branch-content"><div class="branch-grid">
              <article class="branch-card"><div class="branch-info"><h2 class="section-title">Contact Information</h2><div class="info-list">
                <div class="info-item"><div class="info-item-icon"><img src="{$location_img}" /></div><div><h4>Address</h4><p>{$address}</p></div></div>
                <div class="info-item"><div class="info-item-icon"><img src="{$phone_img}" /></div><div><h4>Phone</h4><p><a href="tel:{$phone}">{$phone}</a></p></div></div>
                <div class="info-item"><div class="info-item-icon"><img src="{$email_img}" /></div><div><h4>Email</h4><p><a href="mailto:{$email}">{$email}</a></p></div></div>
                <div class="info-item"><div class="info-item-icon"><img src="{$clock_img}" /></div><div><h4>Service Times</h4><p>{$service_times}</p></div></div>
                <div class="info-item"><div class="info-item-icon"><img src="{$pastor_img}" /></div><div><h4>Lead Pastor</h4><p>{$lead_pastor}</p></div></div>
              </div></div></article>
              <article class="branch-card" aria-label="About and Services">
                <h2 class="section-title">About Our Branch</h2>
                <p class="about-text">Welcome to {$branch_name} State Branch! We are a vibrant community of believers dedicated to worship, fellowship, and service. Our church is committed to creating an environment where everyone can experience the love of God and grow in their faith.</p>
                <div class="services-card"><h3>Weekly Services & Activities</h3>{$services_html}</div>
              </article>
          </div></section>
          <section class="cta-section">
            <h2>Visit Us This Sunday</h2>
            <p>We can't wait to welcome you into our church family!</p>
            <div class="cta-buttons"><a href="#" class="btn btn-primary">View all Churches</a></div>
          </section>
        </main>
HTML;

        $page_data = array(
            'ID'           => $edit_id,
            'post_title'   => $branch_name,
            'post_content' => $page_content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => sanitize_title($branch_name) . '-branch',
            'meta_input'   => array(
                '_wp_page_template' => 'elementor_canvas',
                '_is_church_branch' => 'yes',
                '_br_address'       => $address,
                '_br_phone'         => $phone,
                '_br_email'         => $email,
                '_br_times'         => $service_times,
                '_br_pastor'        => $lead_pastor,
                '_br_activities'    => $meta_activities,
                '_br_hero_id'       => $attachment_id 
            )
        );

        wp_insert_post($page_data);
        echo "<div class='notice notice-success'><p>Branch details updated!</p></div>";
    }
}
new Branch_Creator();