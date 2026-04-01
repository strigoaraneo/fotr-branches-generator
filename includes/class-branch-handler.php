<?php
class Church_Branches_Generator_Branch_Handler {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'branches';
    }

    public function create_branch($data) {
        $defaults = array(
            'branch_name'     => '',
            'address'         => '',
            'phone'           => '',
            'email'           => '',
            'service_times'   => '',
            'lead_pastor'     => '',
            'page_id'         => 0,
            'about_us_text'   => '',
            'directions_info' => '',
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'branch_name'     => sanitize_text_field($data['branch_name']),
                'address'         => sanitize_text_field($data['address']),
                'phone'           => sanitize_text_field($data['phone']),
                'email'           => sanitize_email($data['email']),
                'service_times'   => sanitize_text_field($data['service_times']),
                'lead_pastor'     => sanitize_text_field($data['lead_pastor']),
                'page_id'         => intval($data['page_id']),
                'about_us_text'   => wp_kses_post($data['about_us_text']),
                'directions_info' => wp_kses_post($data['directions_info']),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    public function get_branch($id) {
        $id = intval($id);
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    public function get_branch_by_page_id($page_id) {
        $page_id = intval($page_id);
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE page_id = %d", $page_id);
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    public function get_all_branches() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY branch_name ASC", ARRAY_A);
    }

    public function update_branch($id, $data) {
        $id = intval($id);

        $fields = array();
        $formats = array();

        if (isset($data['branch_name'])) {
            $fields['branch_name'] = sanitize_text_field($data['branch_name']);
            $formats[] = '%s';
        }
        if (isset($data['address'])) {
            $fields['address'] = sanitize_text_field($data['address']);
            $formats[] = '%s';
        }
        if (isset($data['phone'])) {
            $fields['phone'] = sanitize_text_field($data['phone']);
            $formats[] = '%s';
        }
        if (isset($data['email'])) {
            $fields['email'] = sanitize_email($data['email']);
            $formats[] = '%s';
        }
        if (isset($data['service_times'])) {
            $fields['service_times'] = sanitize_text_field($data['service_times']);
            $formats[] = '%s';
        }
        if (isset($data['lead_pastor'])) {
            $fields['lead_pastor'] = sanitize_text_field($data['lead_pastor']);
            $formats[] = '%s';
        }
        if (isset($data['page_id'])) {
            $fields['page_id'] = intval($data['page_id']);
            $formats[] = '%d';
        }
        if (isset($data['about_us_text'])) {
            $fields['about_us_text'] = wp_kses_post($data['about_us_text']);
            $formats[] = '%s';
        }
        if (isset($data['directions_info'])) {
            $fields['directions_info'] = wp_kses_post($data['directions_info']);
            $formats[] = '%s';
        }

        if (empty($fields)) {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $fields,
            array('id' => $id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', $this->wpdb->last_error);
        }

        return true;
    }

    public function delete_branch($id) {
        $id = intval($id);
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_delete_error', $this->wpdb->last_error);
        }

        return true;
    }

    public function branch_exists($branch_name) {
        $sql = $this->wpdb->prepare("SELECT id FROM {$this->table_name} WHERE branch_name = %s", $branch_name);
        return $this->wpdb->get_var($sql);
    }
}
