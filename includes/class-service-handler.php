<?php
class Church_Branches_Generator_Service_Handler {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'services';
    }

    /**
     * Create a new service
     */
    public function create_service($data) {
        $defaults = array(
            'branch_id'    => 0,
            'service_name' => '',
            'description'  => '',
            'day_of_week'  => '',
            'time'         => '',
            'service_order' => 0,
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'branch_id'    => intval($data['branch_id']),
                'service_name' => sanitize_text_field($data['service_name']),
                'description'  => wp_kses_post($data['description']),
                'day_of_week'  => sanitize_text_field($data['day_of_week']),
                'time'         => sanitize_text_field($data['time']),
                'service_order' => intval($data['service_order']),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Get a single service
     */
    public function get_service($id) {
        $id = intval($id);
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get all services for a branch
     */
    public function get_services_by_branch($branch_id) {
        $branch_id = intval($branch_id);
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE branch_id = %d ORDER BY service_order ASC, id ASC",
            $branch_id
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Update a service
     */
    public function update_service($id, $data) {
        $id = intval($id);
        $fields = array();
        $formats = array();

        if (isset($data['service_name'])) {
            $fields['service_name'] = sanitize_text_field($data['service_name']);
            $formats[] = '%s';
        }
        if (isset($data['description'])) {
            $fields['description'] = wp_kses_post($data['description']);
            $formats[] = '%s';
        }
        if (isset($data['day_of_week'])) {
            $fields['day_of_week'] = sanitize_text_field($data['day_of_week']);
            $formats[] = '%s';
        }
        if (isset($data['time'])) {
            $fields['time'] = sanitize_text_field($data['time']);
            $formats[] = '%s';
        }
        if (isset($data['service_order'])) {
            $fields['service_order'] = intval($data['service_order']);
            $formats[] = '%d';
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

    /**
     * Delete a service
     */
    public function delete_service($id) {
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

    /**
     * Delete all services for a branch
     */
    public function delete_services_by_branch($branch_id) {
        $branch_id = intval($branch_id);
        $result = $this->wpdb->delete(
            $this->table_name,
            array('branch_id' => $branch_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_delete_error', $this->wpdb->last_error);
        }

        return true;
    }

    /**
     * Reorder services
     */
    public function update_service_order($id, $order) {
        $id = intval($id);
        $order = intval($order);

        $result = $this->wpdb->update(
            $this->table_name,
            array('service_order' => $order),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', $this->wpdb->last_error);
        }

        return true;
    }
}
