<?php
class Church_Branches_Generator_Shortcodes {

    public function __construct() {
        add_shortcode('church_branch', array($this, 'render_branch_shortcode'));
    }

    public function render_branch_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'church_branch');

        $branch_id = intval($atts['id']);
        if ($branch_id <= 0) {
            return '<p>Invalid branch ID.</p>';
        }

        $branch_handler = new Church_Branches_Generator_Branch_Handler();
        $branch = $branch_handler->get_branch($branch_id);

        if (!$branch) {
            return '<p>Branch not found.</p>';
        }

        return $this->get_branch_html($branch);
    }

    private function get_branch_html($branch) {
        global $wpdb;
        $prefix = $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX;

        $location_img = CHURCH_BRANCHES_GENERATOR_PLUGIN_URL . 'public/images/ion_location-outline.png';
        $phone_img = CHURCH_BRANCHES_GENERATOR_PLUGIN_URL . 'public/images/mingcute_phone-fill.png';

        $branch_name = esc_html($branch['branch_name']);
        $address = esc_html($branch['address']);
        $phone = esc_html($branch['phone']);
        $email = esc_html($branch['email']);
        $service_times = esc_html($branch['service_times']);
        $lead_pastor = esc_html($branch['lead_pastor']);
        $about_us_text = wp_kses_post($branch['about_us_text']);
        $directions_info = wp_kses_post($branch['directions_info']);

        // Get hero image URL from post meta
        $hero_img_url = '';
        if ($branch['page_id']) {
            $img_id = get_post_meta($branch['page_id'], '_br_hero_id', true);
            if ($img_id) {
                $hero_img_url = wp_get_attachment_url($img_id);
            }
        }
        if (!$hero_img_url) {
            $hero_img_url = 'https://womenofconnections.org/testingsite/wp-content/uploads/2026/03/318b640e08877549ff0fd0796e94431e9cdf7f77-scaled.jpg';
        }

        // Fetch services for this branch
        $services_table = $prefix . 'services';
        $services = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$services_table} WHERE branch_id = %d ORDER BY service_order ASC, id ASC", $branch['id']),
            ARRAY_A
        );

        // Fetch programs for this branch
        $programs_table = $prefix . 'programs';
        $programs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$programs_table} WHERE branch_id = %d ORDER BY program_order ASC, id ASC", $branch['id']),
            ARRAY_A
        );

        // Build services HTML
        $services_html = '';
        if (!empty($services)) {
            foreach ($services as $service) {
                $services_html .= '<div class="service-row">';
                $services_html .= '<h4>' . esc_html($service['service_name']) . '</h4>';
                if (!empty($service['day_of_week']) || !empty($service['time'])) {
                    $services_html .= '<p class="service-day-time">';
                    if (!empty($service['day_of_week'])) {
                        $services_html .= 'Every ' . esc_html($service['day_of_week']);
                    }
                    if (!empty($service['time'])) {
                        $services_html .= ' – ' . esc_html($service['time']);
                    }
                    $services_html .= '</p>';
                }
                if (!empty($service['description'])) {
                    $services_html .= '<p>' . esc_html($service['description']) . '</p>';
                }
                $services_html .= '</div>';
            }
        } else {
            $services_html = '<p>No services scheduled yet.</p>';
        }

        // Build programs HTML
        $programs_html = '';
        if (!empty($programs)) {
            $programs_by_type = array();
            foreach ($programs as $program) {
                $type = $program['program_type'];
                if (!isset($programs_by_type[$type])) {
                    $programs_by_type[$type] = array();
                }
                $programs_by_type[$type][] = $program;
            }

            foreach ($programs_by_type as $type => $type_programs) {
                $programs_html .= '<div class="program-type">';
                $programs_html .= '<h3>' . esc_html(ucfirst($type) . ' Programs') . '</h3>';
                foreach ($type_programs as $program) {
                    $programs_html .= '<div class="program-row">';
                    $programs_html .= '<h4>' . esc_html($program['program_name']) . '</h4>';
                    $programs_html .= '<p>' . esc_html($program['description']) . '</p>';
                    if (!empty($program['day_of_week']) || !empty($program['time'])) {
                        $programs_html .= '<p class="program-time">';
                        if (!empty($program['day_of_week'])) {
                            $programs_html .= esc_html($program['day_of_week']);
                        }
                        if (!empty($program['time'])) {
                            $programs_html .= ' – ' . esc_html($program['time']);
                        }
                        $programs_html .= '</p>';
                    }
                    if (!empty($program['location'])) {
                        $programs_html .= '<p class="program-location">' . esc_html($program['location']) . '</p>';
                    }
                    $programs_html .= '</div>';
                }
                $programs_html .= '</div>';
            }
        } else {
            $programs_html = '<p>No programs scheduled yet.</p>';
        }

        // Build directions popup HTML
        $directions_html = '<div id="branch-directions-popup" class="branch-directions-popup popup-hidden">';
        $directions_html .= '<div class="directions-popup-content">';
        $directions_html .= '<button class="directions-popup-close" onclick="document.getElementById(\'branch-directions-popup\').classList.toggle(\'popup-hidden\');">×</button>';
        $directions_html .= '<h2>Directions to ' . $branch_name . '</h2>';
        $directions_html .= '<div class="directions-map">';
        $directions_html .= '<iframe src="https://www.google.com/maps?q=' . urlencode($address) . '&output=embed" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
        $directions_html .= '</div>';
        $directions_html .= '<div class="directions-boxes-container">';
        $directions_html .= '<div class="travel-tips-box">';
        $directions_html .= '<h3>Travel Tips</h3>';
        $directions_html .= '<ul>';
        $directions_html .= '<li>Arrive 15-20 minutes early to find parking and seating</li>';
        $directions_html .= '<li>Parking is available on church premises</li>';
        $directions_html .= '<li>Public transportation routes available nearby</li>';
        $directions_html .= '<li>Call us if you need assistance finding the location</li>';
        $directions_html .= '</ul>';
        $directions_html .= '</div>';
        $directions_html .= '<div class="nearby-landmarks-box">';
        $directions_html .= '<h3>Nearby Landmarks</h3>';
        $directions_html .= '<ul>';
        $directions_html .= '<li>Look for the church building with a distinctive cross</li>';
        $directions_html .= '<li>Located near major road intersections</li>';
        $directions_html .= '<li>Well-signposted church entrance</li>';
        $directions_html .= '</ul>';
        $directions_html .= '</div>';
        $directions_html .= '</div>';
        if (!empty($directions_info)) {
            $directions_html .= '<div class="directions-info">';
            $directions_html .= $directions_info;
            $directions_html .= '</div>';
        }
        $directions_html .= '</div>';
        $directions_html .= '</div>';

        $hero_bg_style = "background:\n    linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)),\n    url('{$hero_img_url}')\n      no-repeat center/cover;";

        $html = <<<HTML
<main class="branch-page-wrapper">
    <section class="branch-hero" role="banner" style="{$hero_bg_style}">
        <div class="hero-content">
            <h1>{$branch_name} State Branch</h1>
            <p>
                The {$branch_name} State branch is a vibrant community of believers
                committed to spreading the gospel and serving the community with
                love and compassion.
            </p>
        </div>
    </section>

    <section class="branch-content">
        <div class="branch-grid">
            <article class="branch-card" aria-label="Contact Information">
                <div class="branch-info">
                    <h2 class="section-title">Contact Information</h2>
                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-item-icon"><img src="{$location_img}" alt="Location icon" /></div>
                            <div>
                                <h4>Address</h4>
                                <p>{$address}</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-icon"><img src="{$phone_img}" alt="Phone icon" /></div>
                            <div>
                                <h4>Phone</h4>
                                <p><a href="tel:{$phone}">{$phone}</a></p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-icon"><img src="{$location_img}" alt="Location icon" /></div>
                            <div>
                                <h4>Email</h4>
                                <p><a href="mailto:{$email}">{$email}</a></p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-icon"><img src="{$location_img}" alt="Location icon" /></div>
                            <div>
                                <h4>Service Times</h4>
                                <p>{$service_times}</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-icon"><img src="{$location_img}" alt="Location icon" /></div>
                            <div>
                                <h4>Lead Pastor</h4>
                                <p>{$lead_pastor}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="get-directions-btn">
                    <a href="#" class="btn btn-primary" onclick="document.getElementById('branch-directions-popup').style.display='block'; return false;">Get Directions</a>
                </div>
            </article>

            <article class="branch-card" aria-label="About and Services">
                <h2 class="section-title">About Our Branch</h2>
                {$about_us_text}

                <div class="services-card">
                    <h3>Weekly Services &amp; Activities</h3>
                    {$services_html}
                </div>

                <div class="programs-card">
                    <h3>Programs</h3>
                    {$programs_html}
                </div>
            </article>
        </div>
    </section>

    <section class="cta-section" aria-label="Visit Us This Sunday">
        <h2>Visit Us This Sunday</h2>
        <p>We can't wait to welcome you into our church family!</p>
        <div class="cta-buttons">
            <a href="#" class="btn btn-primary">View all Churches</a>
            <a href="#" class="btn btn-outline" onclick="document.getElementById('branch-directions-popup').style.display='block'; return false;">Get Directions</a>
        </div>
    </section>

    {$directions_html}
</main>
HTML;

        return $html;
    }
}
