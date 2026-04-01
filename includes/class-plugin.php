<?php
  class Church_Branches_Generator_Plugin {
      protected $loader;
      protected $plugin_name;
      protected $version;

    public function __construct() {
        $this->version = CHURCH_BRANCHES_GENERATOR_VERSION;
        $this->plugin_name = 'church-branches-generator';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        new Church_Branches_Generator_Shortcodes();
    }

    private function load_dependencies() {
        require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-loader.php';
        require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'admin/class-admin.php';
        require_once CHURCH_BRANCHES_GENERATOR_PLUGIN_DIR . 'public/class-public.php';
        $this->loader = new Church_Branches_Generator_Loader();
    }

    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'church-branches-generator',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    
    private function define_admin_hooks() {
        $plugin_admin = new Church_Branches_Generator_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }
    

    
    private function define_public_hooks() {
        $plugin_public = new Church_Branches_Generator_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }
    

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
