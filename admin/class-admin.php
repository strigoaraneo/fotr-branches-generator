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
        add_action('wp_ajax_cbg_get_menu_items', array($this, 'ajax_get_menu_items'));
        add_action('wp_ajax_cbg_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_cbg_get_program', array($this, 'ajax_get_program'));
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
            $meta['language'] = isset($branch_data['language']) ? $branch_data['language'] : 'english';
            $meta['branch_description'] = isset($branch_data['branch_description']) ? $branch_data['branch_description'] : '';
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
                        <th scope="row"><label for="language">Language</label></th>
                        <td>
                            <select name="language" id="language" required>
                                <option value="english" <?php selected(isset($meta['language']) ? $meta['language'] : 'english', 'english'); ?>>English</option>
                                <option value="yoruba" <?php selected(isset($meta['language']) ? $meta['language'] : 'english', 'yoruba'); ?>>Yoruba</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="branch_description">Branch Description</label></th>
                        <td>
                            <textarea name="branch_description" id="branch_description" class="large-text" rows="3" placeholder="Brief description shown under branch name in header"><?php echo isset($meta['branch_description']) ? esc_textarea($meta['branch_description']) : ''; ?></textarea>
                            <p class="description">Brief description shown under the branch name in the header section.</p>
                        </td>
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

                <?php if (!$edit_id): ?>
                <?php endif; ?>

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
        $language      = sanitize_text_field($_POST['language']);
        $about_us_text = isset($_POST['about_us_text']) ? wp_kses_post($_POST['about_us_text']) : '';
        $directions_info = isset($_POST['directions_info']) ? wp_kses_post($_POST['directions_info']) : '';
        $branch_description = isset($_POST['branch_description']) ? sanitize_text_field($_POST['branch_description']) : '';
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
                    'branch_name'         => $branch_name,
                    'address'             => $address,
                    'phone'               => $phone,
                    'email'               => $email,
                    'service_times'       => $service_times,
                    'lead_pastor'         => $lead_pastor,
                    'about_us_text'       => $about_us_text,
                    'directions_info'     => $directions_info,
                    'language'            => $language,
                    'branch_description'  => $branch_description,
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
                'branch_name'         => $branch_name,
                'address'             => $address,
                'phone'               => $phone,
                'email'               => $email,
                'service_times'       => $service_times,
                'lead_pastor'         => $lead_pastor,
                'page_id'             => $page_id,
                'about_us_text'       => $about_us_text,
                'directions_info'     => $directions_info,
                'language'            => $language,
                'branch_description'  => $branch_description,
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

            $menu_notice = $this->add_branch_to_menu($branch_id, $branch_name, $page_id, $language);

            $link = get_permalink($page_id);
            echo '<div class="notice notice-success is-dismissible"><p>Branch created successfully! ' . $menu_notice . ' <a href="' . esc_url($link) . '" target="_blank">View Page</a></p></div>';
        }
    }
    
    private function add_branch_to_menu($branch_id, $branch_name, $page_id, $language) {
        $selected_menu_id = intval(get_option('cbg_menu_' . $language . '_id', 0));
        $churches_menu_item_id = intval(get_option('cbg_churches_item_' . $language . '_id', 0));
        $mobile_menu_id = intval(get_option('cbg_mobile_menu_' . $language . '_id', 0));
        $mobile_churches_item_id = intval(get_option('cbg_mobile_churches_item_' . $language . '_id', 0));
        
        $branch_url = get_permalink($page_id);
        if (empty($branch_url) || $branch_url === home_url('/')) {
            return '<em>(Invalid page URL)</em>';
        }
        
        $messages = array();
        
        // Add to main menu
        if ($selected_menu_id > 0 && $churches_menu_item_id > 0) {
            $menu_exists = wp_get_nav_menu_object($selected_menu_id);
            if ($menu_exists) {
                $menu_items = wp_get_nav_menu_items($selected_menu_id);
                $churches_item_found = false;
                foreach ($menu_items as $item) {
                    if (intval($item->db_id) === $churches_menu_item_id) {
                        $churches_item_found = true;
                        break;
                    }
                }
                
                if ($churches_item_found) {
                    $already_exists = false;
                    foreach ($menu_items as $item) {
                        if ($item->url === $branch_url) {
                            $already_exists = true;
                            break;
                        }
                    }
                    
                    if (!$already_exists) {
                        $item_id = wp_update_nav_menu_item($selected_menu_id, 0, array(
                            'menu-item-object-id' => $branch_id,
                            'menu-item-object' => 'custom',
                            'menu-item-type' => 'custom',
                            'menu-item-title' => $branch_name . ' Branch',
                            'menu-item-url' => $branch_url,
                            'menu-item-status' => 'publish',
                            'menu-item-parent-id' => $churches_menu_item_id,
                            'menu-item-classes' => 'cbg-branch-item cbg-lang-' . $language,
                        ));
                        
                        if (!is_wp_error($item_id) && $item_id > 0) {
                            $messages[] = 'Added to ' . esc_html(ucfirst($language)) . ' menu';
                        }
                    } else {
                        $messages[] = 'Already in ' . esc_html(ucfirst($language)) . ' menu';
                    }
                }
            }
        }
        
        // Add to mobile menu
        if ($mobile_menu_id > 0 && $mobile_churches_item_id > 0) {
            $mobile_menu_exists = wp_get_nav_menu_object($mobile_menu_id);
            if ($mobile_menu_exists) {
                $mobile_menu_items = wp_get_nav_menu_items($mobile_menu_id);
                $mobile_churches_found = false;
                foreach ($mobile_menu_items as $item) {
                    if (intval($item->db_id) === $mobile_churches_item_id) {
                        $mobile_churches_found = true;
                        break;
                    }
                }
                
                if ($mobile_churches_found) {
                    $already_in_mobile = false;
                    foreach ($mobile_menu_items as $item) {
                        if ($item->url === $branch_url) {
                            $already_in_mobile = true;
                            break;
                        }
                    }
                    
                    if (!$already_in_mobile) {
                        $mobile_item_id = wp_update_nav_menu_item($mobile_menu_id, 0, array(
                            'menu-item-object-id' => $branch_id,
                            'menu-item-object' => 'custom',
                            'menu-item-type' => 'custom',
                            'menu-item-title' => $branch_name . ' Branch',
                            'menu-item-url' => $branch_url,
                            'menu-item-status' => 'publish',
                            'menu-item-parent-id' => $mobile_churches_item_id,
                            'menu-item-classes' => 'cbg-branch-item cbg-lang-' . $language . ' cbg-mobile',
                        ));
                        
                        if (!is_wp_error($mobile_item_id) && $mobile_item_id > 0) {
                            $messages[] = 'Added to ' . esc_html(ucfirst($language)) . ' mobile menu';
                        }
                    } else {
                        $messages[] = 'Already in ' . esc_html(ucfirst($language)) . ' mobile menu';
                    }
                }
            }
        }
        
        if (empty($messages)) {
            return '<em>(No menus configured)</em>';
        }
        
        return '<em>(' . implode(', ', $messages) . ')</em>';
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

        $edit_service_id = isset($_GET['edit_service']) ? intval($_GET['edit_service']) : 0;
        $edit_service = $edit_service_id ? $this->service_handler->get_service($edit_service_id) : null;

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
                <form method="post" class="cbg-service-form" id="cbg-service-form" style="background: #f1f1f1; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
                    <?php wp_nonce_field('cbg_save_service_nonce', 'cbg_nonce'); ?>
                    <input type="hidden" name="branch_id" value="<?php echo intval($branch_id); ?>">
                    <input type="hidden" name="edit_service_id" id="edit_service_id" value="<?php echo intval($edit_service_id); ?>">
                    
                    <h2 id="service-form-title"><?php echo $edit_service ? 'Edit Service' : 'Add Service'; ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="service_name">Service Name</label></th>
                            <td><input name="service_name" id="service_name" type="text" class="regular-text" placeholder="e.g. Sunday Service" required value="<?php echo $edit_service ? esc_attr($edit_service['service_name']) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_desc">Description</label></th>
                            <td><textarea name="service_desc" id="service_desc" class="large-text" placeholder="Service details"><?php echo $edit_service ? esc_textarea($edit_service['description']) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_day">Day of Week</label></th>
                            <td>
                                <select name="service_day" id="service_day">
                                    <option value="">Select a day</option>
                                    <option value="Monday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Monday'); ?>>Monday</option>
                                    <option value="Tuesday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Tuesday'); ?>>Tuesday</option>
                                    <option value="Wednesday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Wednesday'); ?>>Wednesday</option>
                                    <option value="Thursday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Thursday'); ?>>Thursday</option>
                                    <option value="Friday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Friday'); ?>>Friday</option>
                                    <option value="Saturday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Saturday'); ?>>Saturday</option>
                                    <option value="Sunday" <?php selected($edit_service ? $edit_service['day_of_week'] : '', 'Sunday'); ?>>Sunday</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_time">Time</label></th>
                            <td><input name="service_time" id="service_time" type="text" class="regular-text" placeholder="e.g. 9:00 AM" value="<?php echo $edit_service ? esc_attr($edit_service['time']) : ''; ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button($edit_service ? 'Update Service' : 'Add Service', 'primary', 'cbg_save_service'); ?>
                    <?php if ($edit_service): ?>
                        <a href="?page=church-branches-services&branch_id=<?php echo intval($branch_id); ?>" class="button">Cancel Edit</a>
                    <?php endif; ?>
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
                                        <a href="?page=church-branches-services&branch_id=<?php echo intval($branch_id); ?>&edit_service=<?php echo intval($service['id']); ?>" class="button button-small">Edit</a>
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
        $edit_id = intval($_POST['edit_service_id'] ?? 0);
        $service_name = sanitize_text_field($_POST['service_name']);
        $service_desc = wp_kses_post($_POST['service_desc'] ?? '');
        $service_day = sanitize_text_field($_POST['service_day'] ?? '');
        $service_time = sanitize_text_field($_POST['service_time'] ?? '');

        if ($edit_id > 0) {
            $result = $this->service_handler->update_service($edit_id, array(
                'service_name' => $service_name,
                'description'  => $service_desc,
                'day_of_week'  => $service_day,
                'time'         => $service_time,
            ));

            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>Error updating service.</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Service updated successfully!</p></div>';
            }
        } else {
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

        $edit_program_id = isset($_GET['edit_program']) ? intval($_GET['edit_program']) : 0;
        $edit_program = $edit_program_id ? $this->program_handler->get_program($edit_program_id) : null;

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
                <form method="post" class="cbg-program-form" id="cbg-program-form" style="background: #f1f1f1; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
                    <?php wp_nonce_field('cbg_save_program_nonce', 'cbg_nonce'); ?>
                    <input type="hidden" name="branch_id" value="<?php echo intval($branch_id); ?>">
                    <input type="hidden" name="edit_program_id" id="edit_program_id" value="<?php echo intval($edit_program_id); ?>">
                    
                    <h2 id="program-form-title"><?php echo $edit_program ? 'Edit Program' : 'Add Program'; ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="program_name">Program Name</label></th>
                            <td><input name="program_name" id="program_name" type="text" class="regular-text" placeholder="e.g. Bible Study" required value="<?php echo $edit_program ? esc_attr($edit_program['program_name']) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_desc">Description</label></th>
                            <td><textarea name="program_desc" id="program_desc" class="large-text" placeholder="Program details"><?php echo $edit_program ? esc_textarea($edit_program['description']) : ''; ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_type">Type</label></th>
                            <td>
                                <select name="program_type" id="program_type">
                                    <option value="weekly" <?php selected($edit_program ? $edit_program['program_type'] : '', 'weekly'); ?>>Weekly</option>
                                    <option value="monthly" <?php selected($edit_program ? $edit_program['program_type'] : '', 'monthly'); ?>>Monthly</option>
                                    <option value="special" <?php selected($edit_program ? $edit_program['program_type'] : '', 'special'); ?>>Special Event</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_day">Day of Week</label></th>
                            <td>
                                <select name="program_day" id="program_day">
                                    <option value="">Select a day</option>
                                    <option value="Monday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Monday'); ?>>Monday</option>
                                    <option value="Tuesday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Tuesday'); ?>>Tuesday</option>
                                    <option value="Wednesday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Wednesday'); ?>>Wednesday</option>
                                    <option value="Thursday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Thursday'); ?>>Thursday</option>
                                    <option value="Friday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Friday'); ?>>Friday</option>
                                    <option value="Saturday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Saturday'); ?>>Saturday</option>
                                    <option value="Sunday" <?php selected($edit_program ? $edit_program['day_of_week'] : '', 'Sunday'); ?>>Sunday</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_time">Time</label></th>
                            <td><input name="program_time" id="program_time" type="text" class="regular-text" placeholder="e.g. 6:00 PM" value="<?php echo $edit_program ? esc_attr($edit_program['time']) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="program_location">Location</label></th>
                            <td><input name="program_location" id="program_location" type="text" class="regular-text" placeholder="e.g. Main Hall" value="<?php echo $edit_program ? esc_attr($edit_program['location']) : ''; ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button($edit_program ? 'Update Program' : 'Add Program', 'primary', 'cbg_save_program'); ?>
                    <?php if ($edit_program): ?>
                        <a href="?page=church-branches-programs&branch_id=<?php echo intval($branch_id); ?>" class="button">Cancel Edit</a>
                    <?php endif; ?>
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
                                        <a href="?page=church-branches-programs&branch_id=<?php echo intval($branch_id); ?>&edit_program=<?php echo intval($program['id']); ?>" class="button button-small">Edit</a>
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
        $edit_id = intval($_POST['edit_program_id'] ?? 0);
        $program_name = sanitize_text_field($_POST['program_name']);
        $program_desc = wp_kses_post($_POST['program_desc'] ?? '');
        $program_type = sanitize_text_field($_POST['program_type']);
        $program_day = sanitize_text_field($_POST['program_day'] ?? '');
        $program_time = sanitize_text_field($_POST['program_time'] ?? '');
        $program_location = sanitize_text_field($_POST['program_location'] ?? '');

        if ($edit_id > 0) {
            $result = $this->program_handler->update_program($edit_id, array(
                'program_name' => $program_name,
                'description'  => $program_desc,
                'program_type' => $program_type,
                'day_of_week'  => $program_day,
                'time'         => $program_time,
                'location'     => $program_location,
            ));

            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>Error updating program.</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Program updated successfully!</p></div>';
            }
        } else {
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
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (isset($_POST['cbg_save_settings']) && wp_verify_nonce($_POST['cbg_settings_nonce'], 'cbg_save_settings_nonce')) {
            $this->process_settings_form();
        }

        $primary_color = get_option('cbg_primary_color', '#d4a625');
        $secondary_color = get_option('cbg_secondary_color', '#7b1f1f');
        $font_family = get_option('cbg_font_family', 'Inter, sans-serif');

        $menus = wp_get_nav_menus(array('orderby' => 'name'));
        
        $languages = array('english', 'yoruba');
        $language_labels = array('english' => 'English', 'yoruba' => 'Yoruba');
        
        $menu_settings = array();
        foreach ($languages as $lang) {
            $menu_settings[$lang] = array(
                'menu_id' => get_option('cbg_menu_' . $lang . '_id', 0),
                'churches_item_id' => get_option('cbg_churches_item_' . $lang . '_id', 0),
                'mobile_menu_id' => get_option('cbg_mobile_menu_' . $lang . '_id', 0),
                'mobile_churches_item_id' => get_option('cbg_mobile_churches_item_' . $lang . '_id', 0),
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" class="cbg-settings-form">
                <?php wp_nonce_field('cbg_save_settings_nonce', 'cbg_settings_nonce'); ?>
                
                <h2>Appearance</h2>
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
                    <tr>
                        <th scope="row"><label for="title_font_family">Title Font</label></th>
                        <td>
                            <select name="title_font_family" id="title_font_family" style="margin-right: 10px;">
                                <option value="Inter, sans-serif" <?php selected(get_option('cbg_title_font_family', 'Inter, sans-serif'), 'Inter, sans-serif'); ?>>Inter</option>
                                <option value="Arial, sans-serif" <?php selected(get_option('cbg_title_font_family', 'Inter, sans-serif'), 'Arial, sans-serif'); ?>>Arial</option>
                                <option value="Georgia, serif" <?php selected(get_option('cbg_title_font_family', 'Inter, sans-serif'), 'Georgia, serif'); ?>>Georgia</option>
                                <option value="'Trebuchet MS', sans-serif" <?php selected(get_option('cbg_title_font_family', 'Inter, sans-serif'), "'Trebuchet MS', sans-serif"); ?>>Trebuchet MS</option>
                                <option value="'Times New Roman', serif" <?php selected(get_option('cbg_title_font_family', 'Inter, sans-serif'), "'Times New Roman', serif"); ?>>Times New Roman</option>
                            </select>
                            <select name="title_font_weight" id="title_font_weight">
                                <option value="400" <?php selected(get_option('cbg_title_font_weight', '700'), '400'); ?>>Regular (400)</option>
                                <option value="500" <?php selected(get_option('cbg_title_font_weight', '700'), '500'); ?>>Medium (500)</option>
                                <option value="600" <?php selected(get_option('cbg_title_font_weight', '700'), '600'); ?>>Semibold (600)</option>
                                <option value="700" <?php selected(get_option('cbg_title_font_weight', '700'), '700'); ?>>Bold (700)</option>
                            </select>
                            <p class="description">Font for headings and titles.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="body_font_family">Body Font</label></th>
                        <td>
                            <select name="body_font_family" id="body_font_family" style="margin-right: 10px;">
                                <option value="Inter, sans-serif" <?php selected(get_option('cbg_body_font_family', 'Inter, sans-serif'), 'Inter, sans-serif'); ?>>Inter</option>
                                <option value="Arial, sans-serif" <?php selected(get_option('cbg_body_font_family', 'Inter, sans-serif'), 'Arial, sans-serif'); ?>>Arial</option>
                                <option value="Georgia, serif" <?php selected(get_option('cbg_body_font_family', 'Inter, sans-serif'), 'Georgia, serif'); ?>>Georgia</option>
                                <option value="'Trebuchet MS', sans-serif" <?php selected(get_option('cbg_body_font_family', 'Inter, sans-serif'), "'Trebuchet MS', sans-serif"); ?>>Trebuchet MS</option>
                                <option value="'Times New Roman', serif" <?php selected(get_option('cbg_body_font_family', 'Inter, sans-serif'), "'Times New Roman', serif"); ?>>Times New Roman</option>
                            </select>
                            <select name="body_font_weight" id="body_font_weight">
                                <option value="300" <?php selected(get_option('cbg_body_font_weight', '400'), '300'); ?>>Light (300)</option>
                                <option value="400" <?php selected(get_option('cbg_body_font_weight', '400'), '400'); ?>>Regular (400)</option>
                                <option value="500" <?php selected(get_option('cbg_body_font_weight', '400'), '500'); ?>>Medium (500)</option>
                                <option value="600" <?php selected(get_option('cbg_body_font_weight', '400'), '600'); ?>>Semibold (600)</option>
                            </select>
                            <p class="description">Font for body text.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="view_all_churches_url">"View All Churches" URL</label></th>
                        <td>
                            <input name="view_all_churches_url" id="view_all_churches_url" type="text" value="<?php echo esc_attr(get_option('cbg_view_all_churches_url', '/churches')); ?>" class="regular-text" placeholder="/churches">
                            <p class="description">The URL for the "View All Churches" button in the CTA section.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="default_branch_description">Default Branch Description</label></th>
                        <td>
                            <textarea name="default_branch_description" id="default_branch_description" class="large-text" rows="3" placeholder="The church is a vibrant community of believers..."><?php echo esc_textarea(get_option('cbg_default_branch_description', 'The church is a vibrant community of believers committed to spreading the gospel and serving the community with love and compassion.')); ?></textarea>
                            <p class="description">Default description shown in the branch header when no specific description is set. Use <code>{branch_name}</code> to insert the branch name dynamically.</p>
                        </td>
                    </tr>
                </table>

                <h2>Menu Settings by Language</h2>
                <p class="description">Configure which menu to use for each language. Create "Churches" dropdown items in <a href="<?php echo admin_url('nav-menus.php'); ?>" target="_blank">Appearance → Menus</a> first.</p>
                <table class="form-table">
                    <?php foreach ($languages as $lang): ?>
                        <tr>
                            <th scope="row" colspan="2"><strong><?php echo esc_html($language_labels[$lang]); ?></strong></th>
                        </tr>
                        <tr>
                            <th scope="row"><label for="menu_<?php echo $lang; ?>_id">Menu</label></th>
                            <td>
                                <select name="menu_<?php echo $lang; ?>_id" id="menu_<?php echo $lang; ?>_id" class="cbg-menu-select" data-language="<?php echo $lang; ?>">
                                    <option value="0">-- Select a Menu --</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($menu_settings[$lang]['menu_id'], $menu->term_id); ?>>
                                            <?php echo esc_html($menu->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="churches_item_<?php echo $lang; ?>_id">"Churches" Menu Item</label></th>
                            <td>
                                <select name="churches_item_<?php echo $lang; ?>_id" id="churches_item_<?php echo $lang; ?>_id" class="cbg-churches-select" data-language="<?php echo $lang; ?>">
                                    <option value="0">-- Select --</option>
                                    <?php
                                    if ($menu_settings[$lang]['menu_id'] > 0) {
                                        $menu_items = wp_get_nav_menu_items($menu_settings[$lang]['menu_id']);
                                        if ($menu_items) {
                                            foreach ($menu_items as $item) {
                                                if ($item->menu_item_parent == 0) {
                                                    echo '<option value="' . esc_attr($item->db_id) . '" ' . selected($menu_settings[$lang]['churches_item_id'], $item->db_id, false) . '>' . esc_html($item->title) . ' (ID: ' . $item->db_id . ')</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mobile_menu_<?php echo $lang; ?>_id">Mobile Menu</label></th>
                            <td>
                                <select name="mobile_menu_<?php echo $lang; ?>_id" id="mobile_menu_<?php echo $lang; ?>_id" class="cbg-mobile-menu-select" data-language="<?php echo $lang; ?>">
                                    <option value="0">-- Select a Menu --</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($menu_settings[$lang]['mobile_menu_id'], $menu->term_id); ?>>
                                            <?php echo esc_html($menu->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Branch will also be added to this menu.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mobile_churches_item_<?php echo $lang; ?>_id">Mobile "Churches" Menu Item</label></th>
                            <td>
                                <select name="mobile_churches_item_<?php echo $lang; ?>_id" id="mobile_churches_item_<?php echo $lang; ?>_id" class="cbg-mobile-churches-select" data-language="<?php echo $lang; ?>">
                                    <option value="0">-- Select --</option>
                                    <?php
                                    if ($menu_settings[$lang]['mobile_menu_id'] > 0) {
                                        $menu_items = wp_get_nav_menu_items($menu_settings[$lang]['mobile_menu_id']);
                                        if ($menu_items) {
                                            foreach ($menu_items as $item) {
                                                if ($item->menu_item_parent == 0) {
                                                    echo '<option value="' . esc_attr($item->db_id) . '" ' . selected($menu_settings[$lang]['mobile_churches_item_id'], $item->db_id, false) . '>' . esc_html($item->title) . ' (ID: ' . $item->db_id . ')</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php submit_button('Save Settings', 'primary', 'cbg_save_settings'); ?>
            </form>
            
            <script>
                jQuery(document).ready(function($) {
                    $('.cbg-menu-select').on('change', function() {
                        var lang = $(this).data('language');
                        var menu_id = $(this).val();
                        var $churchesSelect = $('#churches_item_' + lang + '_id');
                        
                        $churchesSelect.html('<option value="0">-- Select --</option>');
                        
                        if (menu_id > 0) {
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'cbg_get_menu_items',
                                    menu_id: menu_id,
                                    nonce: '<?php echo wp_create_nonce('cbg-menu-nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $churchesSelect.html('<option value="0">-- Select --</option>' + response.data);
                                    }
                                }
                            });
                        }
                    });
                    
                    $('.cbg-mobile-menu-select').on('change', function() {
                        var lang = $(this).data('language');
                        var menu_id = $(this).val();
                        var $churchesSelect = $('#mobile_churches_item_' + lang + '_id');
                        
                        $churchesSelect.html('<option value="0">-- Select --</option>');
                        
                        if (menu_id > 0) {
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'cbg_get_menu_items',
                                    menu_id: menu_id,
                                    nonce: '<?php echo wp_create_nonce('cbg-menu-nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $churchesSelect.html('<option value="0">-- Select --</option>' + response.data);
                                    }
                                }
                            });
                        }
                    });
                });
            </script>
        </div>
        <?php
    }

    private function process_settings_form() {
        update_option('cbg_primary_color', sanitize_hex_color($_POST['primary_color'] ?? '#d4a625'));
        update_option('cbg_secondary_color', sanitize_hex_color($_POST['secondary_color'] ?? '#7b1f1f'));
        update_option('cbg_title_font_family', sanitize_text_field($_POST['title_font_family'] ?? 'Inter, sans-serif'));
        update_option('cbg_title_font_weight', sanitize_text_field($_POST['title_font_weight'] ?? '700'));
        update_option('cbg_body_font_family', sanitize_text_field($_POST['body_font_family'] ?? 'Inter, sans-serif'));
        update_option('cbg_body_font_weight', sanitize_text_field($_POST['body_font_weight'] ?? '400'));
        update_option('cbg_view_all_churches_url', esc_url_raw($_POST['view_all_churches_url'] ?? '/churches'));
        update_option('cbg_default_branch_description', sanitize_text_field($_POST['default_branch_description'] ?? 'The church is a vibrant community of believers committed to spreading the gospel and serving the community with love and compassion.'));
        
        $languages = array('english', 'yoruba');
        foreach ($languages as $lang) {
            update_option('cbg_menu_' . $lang . '_id', intval($_POST['menu_' . $lang . '_id'] ?? 0));
            update_option('cbg_churches_item_' . $lang . '_id', intval($_POST['churches_item_' . $lang . '_id'] ?? 0));
            update_option('cbg_mobile_menu_' . $lang . '_id', intval($_POST['mobile_menu_' . $lang . '_id'] ?? 0));
            update_option('cbg_mobile_churches_item_' . $lang . '_id', intval($_POST['mobile_churches_item_' . $lang . '_id'] ?? 0));
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    public function ajax_get_menu_items() {
        check_ajax_referer('cbg-menu-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $menu_id = intval($_POST['menu_id']);
        if ($menu_id <= 0) {
            wp_send_json_error('Invalid menu ID');
        }
        
        $menu_items = wp_get_nav_menu_items($menu_id);
        $options = '';
        
        if ($menu_items) {
            foreach ($menu_items as $item) {
                if ($item->menu_item_parent == 0) {
                    $options .= '<option value="' . esc_attr($item->db_id) . '">' . esc_html($item->title) . ' (ID: ' . $item->db_id . ')</option>';
                }
            }
        }
        
        wp_send_json_success($options);
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

        // Remove branch from all menus
        $this->remove_branch_from_menus($branch_id, $page_id);

        // Delete branch
        $result = $this->branch_handler->delete_branch($branch_id);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to delete branch');
        }

        // Delete associated page
        wp_delete_post($page_id, true);

        wp_send_json_success('Branch deleted successfully');
    }

    private function remove_branch_from_menus($branch_id, $page_id) {
        $menus = wp_get_nav_menus(array('orderby' => 'name'));
        $branch_url = get_permalink($page_id);

        foreach ($menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            if (!$menu_items) continue;

            foreach ($menu_items as $item) {
                if (isset($item->classes) && in_array('cbg-branch-item', (array)$item->classes)) {
                    if (intval($item->object_id) === $branch_id || $item->url === $branch_url) {
                        wp_delete_post($item->db_id, true);
                    }
                }
            }
        }
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

    public function ajax_get_service() {
        check_ajax_referer('cbg-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $service_id = intval($_POST['service_id']);
        $service = $this->service_handler->get_service($service_id);

        if (!$service) {
            wp_send_json_error('Service not found');
        }

        wp_send_json_success($service);
    }

    public function ajax_get_program() {
        check_ajax_referer('cbg-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $program_id = intval($_POST['program_id']);
        $program = $this->program_handler->get_program($program_id);

        if (!$program) {
            wp_send_json_error('Program not found');
        }

        wp_send_json_success($program);
    }
}