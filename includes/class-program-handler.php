<?php
class Church_Branches_Generator_Program_Handler {
    private $wpdb;
    private $table_name;

    /**
     * This class is responsible for handling database operations related to programs.
     */

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'programs';
    }

    /**
     * Create a new program
     */
    public function create_program($data) {
        $defaults = array(
            'branch_id'    => 0,
            'program_name' => '',
            'description'  => '',
            'program_type' => 'weekly',
            'day_of_week'  => '',
            'time'         => '',
            'location'     => '',
            'program_order' => 0,
        );

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'branch_id'    => intval($data['branch_id']),
                'program_name' => sanitize_text_field($data['program_name']),
                'description'  => wp_kses_post($data['description']),
                'program_type' => sanitize_text_field($data['program_type']),
                'day_of_week'  => sanitize_text_field($data['day_of_week']),
                'time'         => sanitize_text_field($data['time']),
                'location'     => sanitize_text_field($data['location']),
                'program_order' => intval($data['program_order']),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Get a single program
     */
    public function get_program($id) {
        $id = intval($id);
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get all programs for a branch
     */
    public function get_programs_by_branch($branch_id, $type = null) {
        $branch_id = intval($branch_id);
        
        if ($type) {
            $type = sanitize_text_field($type);
            $sql = $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE branch_id = %d AND program_type = %s ORDER BY program_order ASC, id ASC",
                $branch_id,
                $type
            );
        } else {
            $sql = $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE branch_id = %d ORDER BY program_order ASC, id ASC",
                $branch_id
            );
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Update a program
     */
    public function update_program($id, $data) {
        $id = intval($id);
        $fields = array();
        $formats = array();

        if (isset($data['program_name'])) {
            $fields['program_name'] = sanitize_text_field($data['program_name']);
            $formats[] = '%s';
        }
        if (isset($data['description'])) {
            $fields['description'] = wp_kses_post($data['description']);
            $formats[] = '%s';
        }
        if (isset($data['program_type'])) {
            $fields['program_type'] = sanitize_text_field($data['program_type']);
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
        if (isset($data['location'])) {
            $fields['location'] = sanitize_text_field($data['location']);
            $formats[] = '%s';
        }
        if (isset($data['program_order'])) {
            $fields['program_order'] = intval($data['program_order']);
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
     * Delete a program
     */
    public function delete_program($id) {
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
     * Delete all programs for a branch
     */
    public function delete_programs_by_branch($branch_id) {
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
     * Reorder programs
     */
    public function update_program_order($id, $order) {
        $id = intval($id);
        $order = intval($order);

        $result = $this->wpdb->update(
            $this->table_name,
            array('program_order' => $order),
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
