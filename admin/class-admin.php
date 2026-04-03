<?php
class Church_Branches_Generator_Admin {
    private $plugin_name;
    private $version;
    private $branch_handler;
    private $service_handler;
    private $program_handler;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->branch_handler = new Church_Branches_Generator_Branch_Handler();
        $this->service_handler = new Church_Branches_Generator_Service_Handler();
        $this->program_handler = new Church_Branches_Generator_Program_Handler();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cbg_delete_branch', array($this, 'ajax_delete_branch'));
        add_action('wp_ajax_cbg_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_cbg_delete_program', array($this, 'ajax_delete_program'));
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, 'church-branches') === false) {
            return;
        }
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin-style.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'church-branches') === false) {
            return;
        }
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin-script.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'cbgAdmin', array(
            'nonce' => wp_create_nonce('cbg-admin-nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Church Branches',
            'Church Branches',
            'manage_options',
            'church-branches',
            array($this, 'render_dashboard'),
            'dashicons-building',
            25
        );

        add_submenu_page(
            'church-branches',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'church-branches',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'church-branches',
            'Create Branch',
            'Create Branch',
            'manage_options',
            'church-branches-create',
            array($this, 'render_create_branch')
        );

        add_submenu_page(
            'church-branches',
            'All Branches',
            'All Branches',
            'manage_options',
            'church-branches-list',
            array($this, 'render_branches_list')
        );

        add_submenu_page(
            'church-branches',
            'Services',
            'Services',
            'manage_options',
            'church-branches-services',
            array($this, 'render_services_list')
        );

        add_submenu_page(
            'church-branches',
            'Programs',
            'Programs',
            'manage_options',
            'church-branches-programs',
            array($this, 'render_programs_list')
        );

        add_submenu_page(
            'church-branches',
            'Settings',
            'Settings',
            'manage_options',
            'church-branches-settings',
            array($this, 'render_settings')
        );
    }

    public function render_dashboard() {
        $total_branches = count($this->branch_handler->get_all_branches());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="dashboard-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                    <h3>Total Branches</h3>
                    <p style="font-size: 2em; color: #0073aa; margin: 0;"><?php echo $total_branches; ?></p>
                    <a href="?page=church-branches-list" class="button button-secondary">Manage</a>
                </div>
                <div class="dashboard-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                    <h3>Create New Branch</h3>
                    <p>Add a new branch to your network</p>
                    <a href="?page=church-branches-create" class="button button-primary">Create Branch</a>
                </div>
                <div class="dashboard-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                    <h3>Manage Programs</h3>
                    <p>Add programs and activities</p>
                    <a href="?page=church-branches-programs" class="button button-secondary">Manage Programs</a>
                </div>
                <div class="dashboard-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                    <h3>Plugin Settings</h3>
                    <p>Configure plugin options</p>
                    <a href="?page=church-branches-settings" class="button button-secondary">Settings</a>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_create_branch() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
        $meta = array();
        $branch_data = null;

        if ($edit_id) {
            // Get branch data from page
            $post = get_post($edit_id);
            if (!$post) {
                echo '<div class="notice notice-error"><p>Branch not found.</p></div>';
                return;
            }
            
            $branch_data = $this->branch_handler->get_branch_by_page_id($edit_id);
            if (!$branch_data) {
                echo '<div class="notice notice-error"><p>Branch data not found.</p></div>';
                return;
            }

            $meta['name'] = $post->post_title;
            $meta['address'] = $branch_data['address'];
            $meta['phone'] = $branch_data['phone'];
            $meta['email'] = $branch_data['email'];
            $meta['times'] = $branch_data['service_times'];
            $meta['pastor'] = $branch_data['lead_pastor'];
            $meta['about_us'] = $branch_data['about_us_text'];
            $meta['directions_info'] = $branch_data['directions_info'];
            $meta['branch_id'] = $branch_data['id'];
            $meta['img_id'] = get_post_meta($edit_id, '_br_hero_id', true);
            $meta['img_url'] = wp_get_attachment_url($meta['img_id']);
        }

        if (isset($_POST['cbg_submit_branch']) && wp_verify_nonce($_POST['cbg_nonce_field'], 'cbg_create_branch_nonce')) {
            $this->process_branch_form($edit_id);
        }

        ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? 'Edit' : 'Create New'; ?> Branch</h1>
            <form method="post" action="" class="cbg-branch-form">
                <?php wp_nonce_field('cbg_create_branch_nonce', 'cbg_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="branch_name">Branch Name</label></th>
                        <td><input name="branch_name" id="branch_name" type="text" value="<?php echo isset($meta['name']) ? esc_attr($meta['name']) : ''; ?>" class="regular-text" placeholder="e.g. Lagos" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="address">Address</label></th>
                        <td><input name="address" id="address" type="text" value="<?php echo isset($meta['address']) ? esc_attr($meta['address']) : ''; ?>" class="regular-text" placeholder="e.g. 123 Lagos Street, Ikeja" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone">Phone</label></th>
                        <td><input name="phone" id="phone" type="text" value="<?php echo isset($meta['phone']) ? esc_attr($meta['phone']) : ''; ?>" class="regular-text" placeholder="e.g. +234 800 000 0000" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email</label></th>
                        <td><input name="email" id="email" type="email" value="<?php echo isset($meta['email']) ? esc_attr($meta['email']) : ''; ?>" class="regular-text" placeholder="e.g. lagos@church.com" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_times">Service Times (Brief)</label></th>
                        <td><input name="service_times" id="service_times" type="text" value="<?php echo isset($meta['times']) ? esc_attr($meta['times']) : ''; ?>" class="regular-text" placeholder="e.g. Sundays at 9:00 AM" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lead_pastor">Lead Pastor</label></th>
                        <td><input name="lead_pastor" id="lead_pastor" type="text" value="<?php echo isset($meta['pastor']) ? esc_attr($meta['pastor']) : ''; ?>" class="regular-text" placeholder="e.g. Pastor John Doe" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hero_image">Hero Image</label></th>
                        <td>
                            <div id="branch_image_preview" style="margin-bottom:10px;">
                                <?php if(!empty($meta['img_url'])): ?>
                                    <img src="<?php echo esc_url($meta['img_url']); ?>" style="max-width:200px; height:auto; display:block; border:1px solid #ccc;">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="branch_image_id" id="branch_image_id" value="<?php echo isset($meta['img_id']) ? intval($meta['img_id']) : 0; ?>">
                            <button type="button" class="button" id="upload_image_button">Select Image from Media Library</button>
                            <button type="button" class="button" id="remove_image_button" style="<?php echo empty($meta['img_url']) ? 'display:none;' : ''; ?>">Remove</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="about_us">About Us Text</label></th>
                        <td>
                            <?php
                            wp_editor(isset($meta['about_us']) ? $meta['about_us'] : '', 'about_us', array(
                                'textarea_name' => 'about_us_text',
                                'media_buttons' => true,
                                'teeny' => true,
                                'quicktags' => true,
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="directions_info">Directions Info</label></th>
                        <td>
                            <?php
                            wp_editor(isset($meta['directions_info']) ? $meta['directions_info'] : '', 'directions_info', array(
                                'textarea_name' => 'directions_info',
                                'media_buttons' => false,
                                'teeny' => true,
                            ));
                            ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button($edit_id ? 'Update Branch' : 'Create Branch', 'primary', 'cbg_submit_branch'); ?>
            </form>
        </div>
        <?php
        wp_enqueue_media();
    }

    private function process_branch_form($edit_id = 0) {
        $branch_name   = sanitize_text_field($_POST['branch_name']);
        $address       = sanitize_text_field($_POST['address']);
        $phone         = sanitize_text_field($_POST['phone']);
        $email         = sanitize_email($_POST['email']);
        $service_times = sanitize_text_field($_POST['service_times']);
        $lead_pastor   = sanitize_text_field($_POST['lead_pastor']);
        $about_us_text = isset($_POST['about_us_text']) ? wp_kses_post($_POST['about_us_text']) : '';
        $directions_info = isset($_POST['directions_info']) ? wp_kses_post($_POST['directions_info']) : '';
        $attachment_id = intval($_POST['branch_image_id'] ?? 0);

        // Check if branch already exists (for new branches)
        if (!$edit_id && $this->branch_handler->branch_exists($branch_name)) {
            echo '<div class="notice notice-error"><p>A branch with this name already exists.</p></div>';
            return;
        }

        if ($edit_id) {
            // Update existing branch
            $page_data = array(
                'ID'           => $edit_id,
                'post_title'   => $branch_name,
                'post_name'    => sanitize_title($branch_name) . '-branch',
            );

            $updated_page = wp_update_post($page_data);

            if (is_wp_error($updated_page)) {
                echo '<div class="notice notice-error"><p>Error updating page.</p></div>';
                return;
            }

            $branch_data = $this->branch_handler->get_branch_by_page_id($edit_id);
            if ($branch_data) {
                $result = $this->branch_handler->update_branch($branch_data['id'], array(
                    'branch_name'   => $branch_name,
                    'address'       => $address,
                    'phone'         => $phone,
                    'email'         => $email,
                    'service_times' => $service_times,
                    'lead_pastor'   => $lead_pastor,
                    'about_us_text' => $about_us_text,
                    'directions_info' => $directions_info,
                ));

                if (is_wp_error($result)) {
                    echo '<div class="notice notice-error"><p>Error updating branch data.</p></div>';
                    return;
                }
            }

            update_post_meta($edit_id, '_br_hero_id', $attachment_id);

            echo '<div class="notice notice-success is-dismissible"><p>Branch updated successfully!</p></div>';
        } else {
            // Create new branch
            $parent_page = get_page_by_path('website');
            $parent_id = $parent_page ? $parent_page->ID : 0;

            $page_data = array(
                'post_title'   => $branch_name,
                'post_content' => '[church_branch id="0"]', // Placeholder
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id(),
                'post_parent'  => $parent_id,
                'post_name'    => sanitize_title($branch_name) . '-branch',
                'meta_input'   => array(
                    '_is_church_branch' => 'yes',
                    '_br_hero_id'       => $attachment_id,
                )
            );

            $page_id = wp_insert_post($page_data);

            if (is_wp_error($page_id)) {
                echo '<div class="notice notice-error"><p>Error creating page.</p></div>';
                return;
            }

            // Create branch data in database
            $branch_id = $this->branch_handler->create_branch(array(
                'branch_name'     => $branch_name,
                'address'         => $address,
                'phone'           => $phone,
                'email'           => $email,
                'service_times'   => $service_times,
                'lead_pastor'     => $lead_pastor,
                'page_id'         => $page_id,
                'about_us_text'   => $about_us_text,
                'directions_info' => $directions_info,
            ));

            if (is_wp_error($branch_id)) {
                wp_delete_post($page_id, true);
                echo '<div class="notice notice-error"><p>Error saving branch data.</p></div>';
                return;
            }

            // Update page content with actual branch ID
            wp_update_post(array(
                'ID'           => $page_id,
                'post_content' => '[church_branch id="' . $branch_id . '"]',
            ));

            $link = get_permalink($page_id);
            echo '<div class="notice notice-success is-dismissible"><p>Branch created successfully! <a href="' . esc_url($link) . '" target="_blank">View Page</a></p></div>';
        }
    }

    public function render_branches_list() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $branches = $this->branch_handler->get_all_branches();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Branch Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($branches)): ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><strong><?php echo esc_html($branch['branch_name']); ?></strong></td>
                                <td><?php echo esc_html($branch['address']); ?></td>
                                <td><?php echo esc_html($branch['phone']); ?></td>
                                <td>
                                    <a href="?page=church-branches-create&edit_id=<?php echo intval($branch['page_id']); ?>" class="button button-small">Edit</a>
                                    <a href="?page=church-branches-programs&branch_id=<?php echo intval($branch['id']); ?>" class="button button-small">Programs</a>
                                    <a href="?page=church-branches-services&branch_id=<?php echo intval($branch['id']); ?>" class="button button-small">Services</a>
                                    <a href="<?php echo esc_url(get_permalink($branch['page_id'])); ?>" class="button button-small" target="_blank">View</a>
                                    <button class="button button-small button-link-delete cbg-delete-branch" data-branch-id="<?php echo intval($branch['id']); ?>" data-page-id="<?php echo intval($branch['page_id']); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No branches found. <a href="?page=church-branches-create">Create one now</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_services_list() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
        $branches = $this->branch_handler->get_all_branches();

        if (!$branch_id && !empty($branches)) {
            $branch_id = $branches[0]['id'];
        }

        $services = $branch_id ? $this->service_handler->get_services_by_branch($branch_id) : array();

        if (isset($_POST['cbg_save_service']) && wp_verify_nonce($_POST['cbg_nonce'], 'cbg_save_service_nonce')) {
            $this->process_service_form($branch_id);
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div style="margin-bottom: 20px;">
                <label for="branch_select">Select Branch:</label>
                <select id="branch_select" onchange="window.location = '?page=church-branches-services&branch_id=' + this.value;">
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo intval($branch['id']); ?>" <?php selected($branch_id, $branch['id']); ?>><?php echo esc_html($branch['branch_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($branch_id): ?>
                <form method="post" class="cbg-service-form" style="background: #f1f1f1; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
                    <?php wp_nonce_field('cbg_save_service_nonce', 'cbg_nonce'); ?>
                    <input type="hidden" name="branch_id" value="<?php echo intval($branch_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="service_name">Service Name</label></th>
                            <td><input name="service_name" id="service_name" type="text" class="regular-text" placeholder="e.g. Sunday Service" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_desc">Description</label></th>
                            <td><textarea name="service_desc" id="service_desc" class="large-text" placeholder="Service details"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_day">Day of Week</label></th>
                            <td>
                                <select name="service_day" id="service_day">
                                    <option value="">Select a day</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_time">Time</label></th>
                            <td><input name="service_time" id="service_time" type="text" class="regular-text" placeholder="e.g. 9:00 AM"></td>
                        </tr>
                    </table>
                    <?php submit_button('Add Service', 'primary', 'cbg_save_service'); ?>
                </form>

                <h2>Services for this Branch</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($service['service_name']); ?></strong></td>
                                    <td><?php echo wp_kses_post($service['description']); ?></td>
                                    <td><?php echo esc_html($service['day_of_week']); ?></td>
                                    <td><?php echo esc_html($service['time']); ?></td>
                                    <td>
                                        <button class="button button-small button-link-delete cbg-delete-service" data-service-id="<?php echo intval($service['id']); ?>" data-branch-id="<?php echo intval($branch_id); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No services found for this branch.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function process_service_form($branch_id) {
        $service_name = sanitize_text_field($_POST['service_name']);
        $service_desc = wp_kses_post($_POST['service_desc'] ?? '');
        $service_day = sanitize_text_field($_POST['service_day'] ?? '');
        $service_time = sanitize_text_field($_POST['service_time'] ?? '');

        $result = $this->service_handler->create_service(array(
            'branch_id'    => $branch_id,
            'service_name' => $service_name,
            'description'  => $service_desc,
            'day_of_week'  => $service_day,
            'time'         => $service_time,
        ));

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>Error creating service.</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Service added successfully!</p></div>';
        }
    }

    public function render_programs_list() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
        $branches = $this->branch_handler->get_all_branches();

        if (!$branch_id && !empty($branches)) {
            $branch_id = $branches[0]['id'];
        }

        $programs = $branch_id ? $this->program_handler->get_programs_by_branch($branch_id) : array();

        if (isset($_POST['cbg_save_program']) && wp_verify_nonce($_POST['cbg_nonce'], 'cbg_save_program_nonce')) {
            $this->process_program_form($branch_id);
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div style="margin-bottom: 20px;">
                <label for="branch_select">Select Branch:</label>
                <select id="branch_select" onchange="window.location = '?page=church-branches-programs&branch_id=' + this.value;">
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo intval($branch['id']); ?>" <?php selected($branch_id, $branch['id']); ?>><?php echo esc_html($branch['branch_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($branch_id): ?>
                <form method="post" class="cbg-program-form" style="background: #f1f1f1; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
                    <?php wp_nonce_field('cbg_save_program_nonce', 'cbg_nonce'); ?>
                    <input type="hidden" name="branch_id" value="<?php echo intval($branch_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="program_name">Program Name</label></th>
                            <td><input name="program_name" id="program_name" type="text" class="regular-text" placeholder="e.g. Bible Study" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_desc">Description</label></th>
                            <td><textarea name="program_desc" id="program_desc" class="large-text" placeholder="Program details"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_type">Type</label></th>
                            <td>
                                <select name="program_type" id="program_type">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="special">Special Event</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_day">Day of Week</label></th>
                            <td>
                                <select name="program_day" id="program_day">
                                    <option value="">Select a day</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_time">Time</label></th>
                            <td><input name="program_time" id="program_time" type="text" class="regular-text" placeholder="e.g. 6:00 PM"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_location">Location</label></th>
                            <td><input name="program_location" id="program_location" type="text" class="regular-text" placeholder="e.g. Main Hall"></td>
                        </tr>
                    </table>
                    <?php submit_button('Add Program', 'primary', 'cbg_save_program'); ?>
                </form>

                <h2>Programs for this Branch</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Program Name</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($programs)): ?>
                            <?php foreach ($programs as $program): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($program['program_name']); ?></strong></td>
                                    <td><?php echo wp_kses_post($program['description']); ?></td>
                                    <td><?php echo esc_html(ucfirst($program['program_type'])); ?></td>
                                    <td><?php echo esc_html($program['day_of_week']); ?></td>
                                    <td><?php echo esc_html($program['time']); ?></td>
                                    <td><?php echo esc_html($program['location']); ?></td>
                                    <td>
                                        <button class="button button-small button-link-delete cbg-delete-program" data-program-id="<?php echo intval($program['id']); ?>" data-branch-id="<?php echo intval($branch_id); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No programs found for this branch.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function process_program_form($branch_id) {
        $program_name = sanitize_text_field($_POST['program_name']);
        $program_desc = wp_kses_post($_POST['program_desc'] ?? '');
        $program_type = sanitize_text_field($_POST['program_type']);
        $program_day = sanitize_text_field($_POST['program_day'] ?? '');
        $program_time = sanitize_text_field($_POST['program_time'] ?? '');
        $program_location = sanitize_text_field($_POST['program_location'] ?? '');

        $result = $this->program_handler->create_program(array(
            'branch_id'    => $branch_id,
            'program_name' => $program_name,
            'description'  => $program_desc,
            'program_type' => $program_type,
            'day_of_week'  => $program_day,
            'time'         => $program_time,
            'location'     => $program_location,
        ));

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>Error creating program.</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Program added successfully!</p></div>';
        }
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (isset($_POST['cbg_save_settings']) && wp_verify_nonce($_POST['cbg_settings_nonce'], 'cbg_save_settings_nonce')) {
            $this->process_settings_form();
        }

        $primary_color = get_option('cbg_primary_color', '#0073aa');
        $secondary_color = get_option('cbg_secondary_color', '#005a87');
        $font_family = get_option('cbg_font_family', 'Arial, sans-serif');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" class="cbg-settings-form">
                <?php wp_nonce_field('cbg_save_settings_nonce', 'cbg_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="primary_color">Primary Color</label></th>
                        <td>
                            <input name="primary_color" id="primary_color" type="color" value="<?php echo esc_attr($primary_color); ?>" class="color-field">
                            <p class="description">Used for main buttons and highlights</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="secondary_color">Secondary Color</label></th>
                        <td>
                            <input name="secondary_color" id="secondary_color" type="color" value="<?php echo esc_attr($secondary_color); ?>" class="color-field">
                            <p class="description">Used for secondary elements</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="font_family">Font Family</label></th>
                        <td>
                            <select name="font_family" id="font_family">
                                <option value="Arial, sans-serif" <?php selected($font_family, 'Arial, sans-serif'); ?>>Arial</option>
                                <option value="Georgia, serif" <?php selected($font_family, 'Georgia, serif'); ?>>Georgia</option>
                                <option value="'Trebuchet MS', sans-serif" <?php selected($font_family, "'Trebuchet MS', sans-serif"); ?>>Trebuchet MS</option>
                                <option value="'Times New Roman', serif" <?php selected($font_family, "'Times New Roman', serif"); ?>>Times New Roman</option>
                                <option value="'Courier New', monospace" <?php selected($font_family, "'Courier New', monospace"); ?>>Courier New</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings', 'primary', 'cbg_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    private function process_settings_form() {
        update_option('cbg_primary_color', sanitize_hex_color($_POST['primary_color'] ?? '#0073aa'));
        update_option('cbg_secondary_color', sanitize_hex_color($_POST['secondary_color'] ?? '#005a87'));
        update_option('cbg_font_family', sanitize_text_field($_POST['font_family'] ?? 'Arial, sans-serif'));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    public function ajax_delete_branch() {
        check_ajax_referer('cbg-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $branch_id = intval($_POST['branch_id']);
        $page_id = intval($_POST['page_id']);

        // Delete associated programs and services
        $this->program_handler->delete_programs_by_branch($branch_id);
        $this->service_handler->delete_services_by_branch($branch_id);

        // Delete branch
        $result = $this->branch_handler->delete_branch($branch_id);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to delete branch');
        }

        // Delete associated page
        wp_delete_post($page_id, true);

        wp_send_json_success('Branch deleted successfully');
    }

    public function ajax_delete_service() {
        check_ajax_referer('cbg-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $service_id = intval($_POST['service_id']);

        $result = $this->service_handler->delete_service($service_id);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to delete service');
        }

        wp_send_json_success('Service deleted successfully');
    }

    public function ajax_delete_program() {
        check_ajax_referer('cbg-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $program_id = intval($_POST['program_id']);

        $result = $this->program_handler->delete_program($program_id);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to delete program');
        }

        wp_send_json_success('Program deleted successfully');
    }
}