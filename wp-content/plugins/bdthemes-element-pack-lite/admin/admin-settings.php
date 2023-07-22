<?php

use ElementPack\Notices;
use ElementPack\Utils;
use ElementPack\Admin\ModuleService;
use ElementPack\Base\Element_Pack_Base;
use Elementor\Modules\Usage\Module;
use Elementor\Tracker;

/**
 * Element Pack Admin Settings Class
 */

class ElementPack_Admin_Settings {

    public static $modules_list  = null;
    public static $modules_names = null;

    public static $modules_list_only_widgets  = null;
    public static $modules_names_only_widgets = null;

    public static $modules_list_only_3rdparty  = null;
    public static $modules_names_only_3rdparty = null;

    const PAGE_ID = 'element_pack_options';

    private $settings_api;

    public  $responseObj;
    public  $showMessage  = false;
    private $is_activated = false;

    function __construct() {
        $this->settings_api = new ElementPack_Settings_API;


        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'admin_menu'], 201);


        if (isset($_GET['notice']) && $_GET['notice'] == 'v4') {
            add_action('admin_notices', [$this, 'v4_activate_notice'], 10, 3);
        }

        if (!Tracker::is_allow_track()) {
            add_action('admin_notices', [$this, 'allow_tracker_activate_notice'], 10, 3);
        }
    }

    /**
     * Get used widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */
    public static function get_used_widgets() {

        $used_widgets = array();

        if (class_exists('Elementor\Modules\Usage\Module')) {

            $module     = Module::instance();
            $elements   = $module->get_formatted_usage('raw');
            $ep_widgets = self::get_ep_widgets_names();

            if (is_array($elements) || is_object($elements)) {

                foreach ($elements as $post_type => $data) {
                    foreach ($data['elements'] as $element => $count) {
                        if (in_array($element, $ep_widgets, true)) {
                            if (isset($used_widgets[$element])) {
                                $used_widgets[$element] += $count;
                            } else {
                                $used_widgets[$element] = $count;
                            }
                        }
                    }
                }
            }
        }

        return $used_widgets;
    }

    /**
     * Get used separate widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_used_only_widgets() {

        $used_widgets = array();

        if (class_exists('Elementor\Modules\Usage\Module')) {

            $module     = Module::instance();
            $elements   = $module->get_formatted_usage('raw');
            $ep_widgets = self::get_ep_only_widgets();

            if (is_array($elements) || is_object($elements)) {

                foreach ($elements as $post_type => $data) {
                    foreach ($data['elements'] as $element => $count) {
                        if (in_array($element, $ep_widgets, true)) {
                            if (isset($used_widgets[$element])) {
                                $used_widgets[$element] += $count;
                            } else {
                                $used_widgets[$element] = $count;
                            }
                        }
                    }
                }
            }
        }

        return $used_widgets;
    }

    /**
     * Get used only separate 3rdParty widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_used_only_3rdparty() {

        $used_widgets = array();

        if (class_exists('Elementor\Modules\Usage\Module')) {

            $module     = Module::instance();
            $elements   = $module->get_formatted_usage('raw');
            $ep_widgets = self::get_ep_only_3rdparty_names();

            if (is_array($elements) || is_object($elements)) {

                foreach ($elements as $post_type => $data) {
                    foreach ($data['elements'] as $element => $count) {
                        if (in_array($element, $ep_widgets, true)) {
                            if (isset($used_widgets[$element])) {
                                $used_widgets[$element] += $count;
                            } else {
                                $used_widgets[$element] = $count;
                            }
                        }
                    }
                }
            }
        }

        return $used_widgets;
    }

    /**
     * Get unused widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_unused_widgets() {

        if (!current_user_can('install_plugins')) {
            die();
        }

        $ep_widgets = self::get_ep_widgets_names();

        $used_widgets = self::get_used_widgets();

        $unused_widgets = array_diff($ep_widgets, array_keys($used_widgets));

        return $unused_widgets;
    }

    /**
     * Get unused separate widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_unused_only_widgets() {

        if (!current_user_can('install_plugins')) {
            die();
        }

        $ep_widgets = self::get_ep_only_widgets();

        $used_widgets = self::get_used_only_widgets();

        $unused_widgets = array_diff($ep_widgets, array_keys($used_widgets));

        return $unused_widgets;
    }

    /**
     * Get unused separate 3rdparty widgets.
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_unused_only_3rdparty() {

        if (!current_user_can('install_plugins')) {
            die();
        }

        $ep_widgets = self::get_ep_only_3rdparty_names();

        $used_widgets = self::get_used_only_3rdparty();

        $unused_widgets = array_diff($ep_widgets, array_keys($used_widgets));

        return $unused_widgets;
    }

    /**
     * Get widgets name
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_ep_widgets_names() {
        $names = self::$modules_names;

        if (null === $names) {
            $names = array_map(
                function ($item) {
                    return isset($item['name']) ? 'bdt-' . str_replace('_', '-', $item['name']) : 'none';
                },
                self::$modules_list
            );
        }

        return $names;
    }

    /**
     * Get separate widgets name
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_ep_only_widgets() {
        $names = self::$modules_names_only_widgets;

        if (null === $names) {
            $names = array_map(
                function ($item) {
                    return isset($item['name']) ? 'bdt-' . str_replace('_', '-', $item['name']) : 'none';
                },
                self::$modules_list_only_widgets
            );
        }

        return $names;
    }

    /**
     * Get separate 3rdParty widgets name
     *
     * @access public
     * @return array
     * @since 6.0.0
     *
     */

    public static function get_ep_only_3rdparty_names() {
        $names = self::$modules_names_only_3rdparty;

        if (null === $names) {
            $names = array_map(
                function ($item) {
                    return isset($item['name']) ? 'bdt-' . str_replace('_', '-', $item['name']) : 'none';
                },
                self::$modules_list_only_3rdparty
            );
        }

        return $names;
    }

    /**
     * Get URL with page id
     *
     * @access public
     *
     */

    public static function get_url() {
        return admin_url('admin.php?page=' . self::PAGE_ID);
    }

    /**
     * Init settings API
     *
     * @access public
     *
     */

    public function admin_init() {

        //set the settings
        $this->settings_api->set_sections($this->get_settings_sections());
        $this->settings_api->set_fields($this->element_pack_admin_settings());

        //initialize settings
        $this->settings_api->admin_init();
    }

    /**
     * Add Plugin Menus
     *
     * @access public
     *
     */

    public function admin_menu() {
        add_menu_page(
            BDTEP_TITLE . ' ' . esc_html__('Dashboard', 'bdthemes-element-pack'),
            BDTEP_TITLE,
            'manage_options',
            self::PAGE_ID,
            [$this, 'plugin_page'],
            $this->element_pack_icon(),
            58
        );

        add_submenu_page(
            self::PAGE_ID,
            BDTEP_TITLE,
            esc_html__('Core Widgets', 'bdthemes-element-pack'),
            'manage_options',
            self::PAGE_ID . '#element_pack_active_modules',
            [$this, 'display_page']
        );

        add_submenu_page(
            self::PAGE_ID,
            BDTEP_TITLE,
            esc_html__('3rd Party Widgets', 'bdthemes-element-pack'),
            'manage_options',
            self::PAGE_ID . '#element_pack_third_party_widget',
            [$this, 'display_page']
        );

        add_submenu_page(
            self::PAGE_ID,
            BDTEP_TITLE,
            esc_html__('Extensions', 'bdthemes-element-pack'),
            'manage_options',
            self::PAGE_ID . '#element_pack_elementor_extend',
            [$this, 'display_page']
        );

        add_submenu_page(
            self::PAGE_ID,
            BDTEP_TITLE,
            esc_html__('API Settings', 'bdthemes-element-pack'),
            'manage_options',
            self::PAGE_ID . '#element_pack_api_settings',
            [$this, 'display_page']
        );

        if (!defined('BDTEP_LO')) {

            add_submenu_page(
                self::PAGE_ID,
                BDTEP_TITLE,
                esc_html__('Other Settings', 'bdthemes-element-pack'),
                'manage_options',
                self::PAGE_ID . '#element_pack_other_settings',
                [$this, 'display_page']
            );
        }
    }

    /**
     * Get SVG Icons of Element Pack
     *
     * @access public
     * @return string
     */

    public function element_pack_icon() {
        return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMy4wLjIsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHdpZHRoPSIyMzAuN3B4IiBoZWlnaHQ9IjI1NC44MXB4IiB2aWV3Qm94PSIwIDAgMjMwLjcgMjU0LjgxIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAyMzAuNyAyNTQuODE7Ig0KCSB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+DQoJLnN0MHtmaWxsOiNGRkZGRkY7fQ0KPC9zdHlsZT4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik02MS4wOSwyMjkuMThIMjguOTVjLTMuMTcsMC01Ljc1LTIuNTctNS43NS01Ljc1bDAtMTkyLjA3YzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDMyLjE0DQoJYzMuMTcsMCw1Ljc1LDIuNTcsNS43NSw1Ljc1djE5Mi4wN0M2Ni44MywyMjYuNjEsNjQuMjYsMjI5LjE4LDYxLjA5LDIyOS4xOHoiLz4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0yMDcuNSwzMS4zN3YzMi4xNGMwLDMuMTctMi41Nyw1Ljc1LTUuNzUsNS43NUg5MC4wNGMtMy4xNywwLTUuNzUtMi41Ny01Ljc1LTUuNzVWMzEuMzcNCgljMC0zLjE3LDIuNTctNS43NSw1Ljc1LTUuNzVoMTExLjcyQzIwNC45MywyNS42MiwyMDcuNSwyOC4yLDIwNy41LDMxLjM3eiIvPg0KPHBhdGggY2xhc3M9InN0MCIgZD0iTTIwNy41LDExMS4zM3YzMi4xNGMwLDMuMTctMi41Nyw1Ljc1LTUuNzUsNS43NUg5MC4wNGMtMy4xNywwLTUuNzUtMi41Ny01Ljc1LTUuNzV2LTMyLjE0DQoJYzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDExMS43MkMyMDQuOTMsMTA1LjU5LDIwNy41LDEwOC4xNiwyMDcuNSwxMTEuMzN6Ii8+DQo8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMjA3LjUsMTkxLjN2MzIuMTRjMCwzLjE3LTIuNTcsNS43NS01Ljc1LDUuNzVIOTAuMDRjLTMuMTcsMC01Ljc1LTIuNTctNS43NS01Ljc1VjE5MS4zDQoJYzAtMy4xNywyLjU3LTUuNzUsNS43NS01Ljc1aDExMS43MkMyMDQuOTMsMTg1LjU1LDIwNy41LDE4OC4xMywyMDcuNSwxOTEuM3oiLz4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNjkuNjIsMjUuNjJoMzIuMTRjMy4xNywwLDUuNzUsMi41Nyw1Ljc1LDUuNzV2MTEyLjFjMCwzLjE3LTIuNTcsNS43NS01Ljc1LDUuNzVoLTMyLjE0DQoJYy0zLjE3LDAtNS43NS0yLjU3LTUuNzUtNS43NVYzMS4zN0MxNjMuODcsMjguMiwxNjYuNDQsMjUuNjIsMTY5LjYyLDI1LjYyeiIvPg0KPC9zdmc+DQo=';
    }

    /**
     * Get SVG Icons of Element Pack
     *
     * @access public
     * @return array
     */

    public function get_settings_sections() {
        $sections = [
            [
                'id'    => 'element_pack_active_modules',
                'title' => esc_html__('Core Widgets', 'bdthemes-element-pack')
            ],
            [
                'id'    => 'element_pack_third_party_widget',
                'title' => esc_html__('3rd Party Widgets', 'bdthemes-element-pack')
            ],
            [
                'id'    => 'element_pack_elementor_extend',
                'title' => esc_html__('Extensions', 'bdthemes-element-pack')
            ],
            [
                'id'    => 'element_pack_api_settings',
                'title' => esc_html__('API Settings', 'bdthemes-element-pack'),
            ],
            [
                'id'    => 'element_pack_other_settings',
                'title' => esc_html__('Other Settings', 'bdthemes-element-pack'),
            ],
        ];

        return $sections;
    }

    /**
     * Merge Admin Settings
     *
     * @access protected
     * @return array
     */

    protected function element_pack_admin_settings() {

        return ModuleService::get_widget_settings(function ($settings) {
            $settings_fields    = $settings['settings_fields'];

            self::$modules_list = array_merge($settings_fields['element_pack_active_modules'], $settings_fields['element_pack_third_party_widget']);
            self::$modules_list_only_widgets  = $settings_fields['element_pack_active_modules'];
            self::$modules_list_only_3rdparty = $settings_fields['element_pack_third_party_widget'];

            return $settings_fields;
        });
    }

    /**
     * Get Welcome Panel
     *
     * @access public
     * @return void
     */

    public function element_pack_welcome() {
?>

        <div class="ep-dashboard-panel" data-bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

            <div class="bdt-grid" data-bdt-grid data-bdt-height-match="target: > div > .bdt-card">
                <div class="bdt-width-1-2@m bdt-width-1-4@l">
                    <div class="ep-widget-status bdt-card bdt-card-body">

                        <?php
                        $used_widgets    = count(self::get_used_widgets());
                        $un_used_widgets = count(self::get_unused_widgets());
                        ?>


                        <div class="ep-count-canvas-wrap bdt-flex bdt-flex-between">
                            <div class="ep-count-wrap">
                                <h1 class="ep-feature-title">All Widgets</h1>
                                <div class="ep-widget-count">Used: <b><?php echo $used_widgets; ?></b></div>
                                <div class="ep-widget-count">Unused: <b><?php echo $un_used_widgets; ?></b></div>
                                <div class="ep-widget-count">Total:
                                    <b><?php echo $used_widgets + $un_used_widgets; ?></b>
                                </div>
                            </div>

                            <div class="ep-canvas-wrap">
                                <canvas id="bdt-db-total-status" style="height: 120px; width: 120px;" data-label="Total Widgets Status - (<?php echo $used_widgets + $un_used_widgets; ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_widgets) . ',' . esc_attr($un_used_widgets); ?>" data-bg="#FFD166, #fff4d9" data-bg-hover="#0673e1, #e71522"></canvas>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="bdt-width-1-2@m bdt-width-1-4@l">
                    <div class="ep-widget-status bdt-card bdt-card-body">

                        <?php
                        $used_only_widgets   = count(self::get_used_only_widgets());
                        $unused_only_widgets = count(self::get_unused_only_widgets());
                        ?>


                        <div class="ep-count-canvas-wrap bdt-flex bdt-flex-between">
                            <div class="ep-count-wrap">
                                <h1 class="ep-feature-title">Core</h1>
                                <div class="ep-widget-count">Used: <b><?php echo $used_only_widgets; ?></b></div>
                                <div class="ep-widget-count">Unused: <b><?php echo $unused_only_widgets; ?></b></div>
                                <div class="ep-widget-count">Total:
                                    <b><?php echo $used_only_widgets + $unused_only_widgets; ?></b>
                                </div>
                            </div>

                            <div class="ep-canvas-wrap">
                                <canvas id="bdt-db-only-widget-status" style="height: 120px; width: 120px;" data-label="Core Widgets Status - (<?php echo $used_only_widgets + $unused_only_widgets; ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_widgets) . ',' . esc_attr($unused_only_widgets); ?>" data-bg="#EF476F, #ffcdd9" data-bg-hover="#0673e1, #e71522"></canvas>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="bdt-width-1-2@m bdt-width-1-4@l">
                    <div class="ep-widget-status bdt-card bdt-card-body">

                        <?php
                        $used_only_3rdparty   = count(self::get_used_only_3rdparty());
                        $unused_only_3rdparty = count(self::get_unused_only_3rdparty());
                        ?>


                        <div class="ep-count-canvas-wrap bdt-flex bdt-flex-between">
                            <div class="ep-count-wrap">
                                <h1 class="ep-feature-title">3rd Party</h1>
                                <div class="ep-widget-count">Used: <b><?php echo $used_only_3rdparty; ?></b></div>
                                <div class="ep-widget-count">Unused: <b><?php echo $unused_only_3rdparty; ?></b></div>
                                <div class="ep-widget-count">Total:
                                    <b><?php echo $used_only_3rdparty + $unused_only_3rdparty; ?></b>
                                </div>
                            </div>

                            <div class="ep-canvas-wrap">
                                <canvas id="bdt-db-only-3rdparty-status" style="height: 120px; width: 120px;" data-label="3rd Party Widgets Status - (<?php echo $used_only_3rdparty + $unused_only_3rdparty; ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_3rdparty) . ',' . esc_attr($unused_only_3rdparty); ?>" data-bg="#06D6A0, #B6FFEC" data-bg-hover="#0673e1, #e71522"></canvas>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="bdt-width-1-2@m bdt-width-1-4@l">
                    <div class="ep-widget-status bdt-card bdt-card-body">

                        <div class="ep-count-canvas-wrap bdt-flex bdt-flex-between">
                            <div class="ep-count-wrap">
                                <h1 class="ep-feature-title">Active</h1>
                                <div class="ep-widget-count">Core: <b id="bdt-total-widgets-status-core"></b></div>
                                <div class="ep-widget-count">3rd Party: <b id="bdt-total-widgets-status-3rd"></b></div>
                                <div class="ep-widget-count">Extensions: <b id="bdt-total-widgets-status-extensions"></b></div>
                                <div class="ep-widget-count">Total: <b id="bdt-total-widgets-status-heading"></b></div>
                            </div>

                            <div class="ep-canvas-wrap">
                                <canvas id="bdt-total-widgets-status" style="height: 120px; width: 120px;" data-label="Total Active Widgets Status" data-labels="<?php echo esc_attr('Core, 3rd Party, Extensions'); ?>" data-bg="#0680d6, #B0EBFF, #E6F9FF" data-bg-hover="#0673e1, #B0EBFF, #b6f9e8">
                                </canvas>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <div class="bdt-grid" bdt-grid bdt-height-match="target: > div > .bdt-card">
                <div class="bdt-width-1-3@m ep-support-section">
                    <div class="ep-support-content bdt-card bdt-card-body">
                        <h1 class="ep-feature-title">Support And Feedback</h1>
                        <p>Feeling like to consult with an expert? Take live Chat support immediately from <a href="https://elementpack.pro" target="_blank" rel="">ElementPack</a>. We are always
                            ready to help
                            you 24/7.</p>
                        <p><strong>Or if you’re facing technical issues with our plugin, then please create a support
                                ticket</strong></p>
                        <a class="bdt-button bdt-btn-blue bdt-margin-small-top bdt-margin-small-right" target="_blank" rel="" href="https://bdthemes.com/all-knowledge-base-of-element-pack/">Knowledge
                            Base</a>
                        <a class="bdt-button bdt-btn-grey bdt-margin-small-top" target="_blank" href="https://bdthemes.com/support/">Get Support</a>
                    </div>
                </div>

                <div class="bdt-width-2-3@m">
                    <div class="bdt-card bdt-card-body ep-system-requirement">
                        <h1 class="ep-feature-title bdt-margin-small-bottom">System Requirement</h1>
                        <?php $this->element_pack_system_requirement(); ?>
                    </div>
                </div>
            </div>

            <div class="bdt-grid" bdt-grid bdt-height-match="target: > div > .bdt-card">
                <div class="bdt-width-1-2@m ep-support-section">
                    <div class="bdt-card bdt-card-body ep-feedback-bg">
                        <h1 class="ep-feature-title">Missing Any Feature?</h1>
                        <p style="max-width: 520px;">Are you in need of a feature that’s not available in our plugin?
                            Feel free to do a feature request from here,</p>
                        <a class="bdt-button bdt-btn-grey bdt-margin-small-top" target="_blank" rel="" href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/">Request Feature</a>
                    </div>
                </div>

                <div class="bdt-width-1-2@m">
                    <div class="bdt-card bdt-card-body ep-tryaddon-bg">
                        <h1 class="ep-feature-title">Try Our Others Addons</h1>
                        <p style="max-width: 520px;">
                            <b>Prime Slider, Ultimate Store Kit, Ultimate Store Kit & Live Copy Paste </b> addons for <b>Elementor</b> is the best slider, blogs and eCommerce plugin for WordPress.
                        </p>
                        <div class="bdt-others-plugins-link">
                            <a class="bdt-button bdt-btn-ps bdt-margin-small-right" target="_blank" href="https://wordpress.org/plugins/bdthemes-prime-slider-lite/" bdt-tooltip="The revolutionary slider builder addon for Elementor with next-gen superb interface. It's Free! Download it.">Prime Slider</a>
                            <a class="bdt-button bdt-btn-upk bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/ultimate-post-kit/" bdt-tooltip="Best blogging addon for building quality blogging website with fine-tuned features and widgets. It's Free! Download it.">Ultimate Post Kit</a>
                            <a class="bdt-button bdt-btn-usk bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/ultimate-store-kit/" bdt-tooltip="The only eCommmerce addon for answering all your online store design problems in one package. It's Free! Download it.">Ultimate Store Kit</a>
                            <a class="bdt-button bdt-btn-live-copy bdt-margin-small-right" target="_blank" rel="" href="https://wordpress.org/plugins/live-copy-paste/" bdt-tooltip="Superfast cross-domain copy-paste mechanism for WordPress websites with true UI copy experience. It's Free! Download it.">Live Copy Paste</a>
                            <a class="bdt-button bdt-btn-pg bdt-margin-small-right" target="_blank" href="https://wordpress.org/plugins/pixel-gallery/" bdt-tooltip="Pixel Gallery provides more than 30+ essential elements for everyday applications to simplify the whole web building process. It's Free! Download it.">Pixel Gallery</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>


    <?php
    }

    /**
     * Get Pro
     *
     * @access public
     * @return void
     */

    function element_pack_get_pro() {
    ?>
        <div class="ep-dashboard-panel" bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

            <div class="bdt-grid" bdt-grid bdt-height-match="target: > div > .bdt-card" style="max-width: 800px; margin-left: auto; margin-right: auto;">
                <div class="bdt-width-1-1@m ep-comparision bdt-text-center">
                    <h1 class="bdt-text-bold">WHY GO WITH PRO?</h1>
                    <h2>Just Compare With Element Pack Lite Vs Pro</h2>


                    <div>

                        <ul class="bdt-list bdt-list-divider bdt-text-left bdt-text-normal" style="font-size: 16px;">


                            <li class="bdt-text-bold">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Features</div>
                                    <div class="bdt-width-auto@m">Free</div>
                                    <div class="bdt-width-auto@m">Pro</div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m"><span bdt-tooltip="pos: top-left; title: Lite have 35+ Widgets but Pro have 100+ core widgets">Core Widgets</span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Theme Compatibility</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Dynamic Content & Custom Fields Capabilities</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Proper Documentation</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Updates & Support</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Header & Footer Builder</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Rooten Theme Pro Features</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Priority Support</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">WooCommerce Widgets</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Ready Made Pages</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Ready Made Blocks</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Ready Made Header & Footer</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Elementor Extended Widgets</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Asset Manager</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Live Copy or Paste</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Essential Shortcodes</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Template Library (in Editor)</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>
                            <li class="">
                                <div class="bdt-grid">
                                    <div class="bdt-width-expand@m">Context Menu</div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
                                    <div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
                                </div>
                            </li>

                        </ul>


                        <div class="ep-dashboard-divider"></div>


                        <div class="ep-more-features">
                            <ul class="bdt-list bdt-list-divider bdt-text-left" style="font-size: 16px;">
                                <li>
                                    <div class="bdt-grid">
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Incredibly Advanced
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Refund or Cancel Anytime
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Dynamic Content
                                        </div>
                                    </div>
                                </li>

                                <li>
                                    <div class="bdt-grid">
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Super-Flexible Widgets
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> 24/7 Premium Support
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Third Party Plugins
                                        </div>
                                    </div>
                                </li>

                                <li>
                                    <div class="bdt-grid">
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Special Discount!
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Custom Field Integration
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> With Live Chat Support
                                        </div>
                                    </div>
                                </li>

                                <li>
                                    <div class="bdt-grid">
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Trusted Payment Methods
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Interactive Effects
                                        </div>
                                        <div class="bdt-width-1-3@m">
                                            <span class="dashicons dashicons-heart"></span> Video Tutorial
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <!-- <div class="ep-dashboard-divider"></div> -->

                            <div class="ep-purchase-button">
                                <a href="https://elementpack.pro/pricing/" target="_blank">Purchase Now</a>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>
    <?php
    }

    /**
     * Display System Requirement
     *
     * @access public
     * @return void
     */

    function element_pack_system_requirement() {
        $php_version        = phpversion();
        $max_execution_time = ini_get('max_execution_time');
        $memory_limit       = ini_get('memory_limit');
        $post_limit         = ini_get('post_max_size');
        $uploads            = wp_upload_dir();
        $upload_path        = $uploads['basedir'];
        $yes_icon           = '<span class="valid"><i class="dashicons-before dashicons-yes"></i></span>';
        $no_icon            = '<span class="invalid"><i class="dashicons-before dashicons-no-alt"></i></span>';

        $environment = Utils::get_environment_info();


    ?>
        <ul class="check-system-status bdt-grid bdt-child-width-1-2@m bdt-grid-small ">
            <li>
                <div>

                    <span class="label1">PHP Version: </span>

                    <?php
                    if (version_compare($php_version, '7.0.0', '<')) {
                        echo $no_icon;
                        echo '<span class="label2" title="Min: 7.0 Recommended" bdt-tooltip>Currently: ' . $php_version . '</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">Currently: ' . $php_version . '</span>';
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">Max execution time: </span>

                    <?php
                    if ($max_execution_time < '90') {
                        echo $no_icon;
                        echo '<span class="label2" title="Min: 90 Recommended" bdt-tooltip>Currently: ' . $max_execution_time . '</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">Currently: ' . $max_execution_time . '</span>';
                    }
                    ?>
                </div>
            </li>
            <li>
                <div>
                    <span class="label1">Memory Limit: </span>

                    <?php
                    if (intval($memory_limit) < '812') {
                        echo $no_icon;
                        echo '<span class="label2" title="Min: 812M Recommended" bdt-tooltip>Currently: ' . $memory_limit . '</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">Currently: ' . $memory_limit . '</span>';
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">Max Post Limit: </span>

                    <?php
                    if (intval($post_limit) < '32') {
                        echo $no_icon;
                        echo '<span class="label2" title="Min: 32M Recommended" bdt-tooltip>Currently: ' . $post_limit . '</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">Currently: ' . $post_limit . '</span>';
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">Uploads folder writable: </span>

                    <?php
                    if (!is_writable($upload_path)) {
                        echo $no_icon;
                    } else {
                        echo $yes_icon;
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">MultiSite: </span>

                    <?php
                    if ($environment['wp_multisite']) {
                        echo $yes_icon;
                        echo '<span class="label2">MultiSite</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">No MultiSite </span>';
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">GZip Enabled: </span>

                    <?php
                    if ($environment['gzip_enabled']) {
                        echo $yes_icon;
                    } else {
                        echo $no_icon;
                    }
                    ?>
                </div>
            </li>

            <li>
                <div>
                    <span class="label1">Debug Mode: </span>
                    <?php
                    if ($environment['wp_debug_mode']) {
                        echo $no_icon;
                        echo '<span class="label2">Currently Turned On</span>';
                    } else {
                        echo $yes_icon;
                        echo '<span class="label2">Currently Turned Off</span>';
                    }
                    ?>
                </div>
            </li>

        </ul>

        <div class="bdt-admin-alert">
            <strong>Note:</strong> If you have multiple addons like <b>Element Pack</b> so you need some more
            requirement some
            cases so make sure you added more memory for others addon too.
        </div>
    <?php
    }

    /**
     * Display Plugin Page
     *
     * @access public
     * @return void
     */

    function plugin_page() {

        echo '<div class="wrap element-pack-dashboard">';
        echo '<h1>' . BDTEP_TITLE . ' Settings</h1>';

        $this->settings_api->show_navigation();

    ?>


        <div class="bdt-switcher bdt-tab-container bdt-container-xlarge">
            <div id="element_pack_welcome_page" class="ep-option-page group">
                <?php $this->element_pack_welcome(); ?>

                <?php if (!defined('BDTEP_WL')) {
                    $this->footer_info();
                } ?>
            </div>

            <?php
            $this->settings_api->show_forms();
            ?>

            <div id="element_pack_get_pro" class="ep-option-page group">
                <?php $this->element_pack_get_pro(); ?>
            </div>


        </div>

        </div>

        <?php

        $this->script();

        ?>

    <?php
    }






    /**
     * Tabbable JavaScript codes & Initiate Color Picker
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
    ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('.ep-no-result').removeClass('bdt-animation-shake');
            });

            function filterSearch(e) {
                var parentID = '#' + jQuery(e).data('id');
                var search = jQuery(parentID).find('.bdt-search-input').val().toLowerCase();

                jQuery(".ep-options .ep-option-item").filter(function() {
                    jQuery(this).toggle(jQuery(this).attr('data-widget-name').toLowerCase().indexOf(search) > -1)
                });

                if (!search) {
                    jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "");
                    jQuery(parentID).find('.ep-widget-all').trigger('click');
                } else {
                    jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "filter: [data-widget-name*='" + search + "']");
                    jQuery(parentID).find('.bdt-search-input').removeClass('bdt-active'); // Thanks to Bar-Rabbas
                    jQuery(parentID).find('.bdt-search-input').trigger('click');
                }
            }

            jQuery('.ep-options-parent').each(function(e, item) {
                var eachItem = '#' + jQuery(item).attr('id');
                jQuery(eachItem).on("beforeFilter", function() {
                    jQuery(eachItem).find('.ep-no-result').removeClass('bdt-animation-shake');
                });

                jQuery(eachItem).on("afterFilter", function() {

                    var isElementVisible = false;
                    var i = 0;

                    while (!isElementVisible && i < jQuery(eachItem).find(".ep-option-item").length) {
                        if (jQuery(eachItem).find(".ep-option-item").eq(i).is(":visible")) {
                            isElementVisible = true;
                        }
                        i++;
                    }

                    if (isElementVisible === false) {
                        jQuery(eachItem).find('.ep-no-result').addClass('bdt-animation-shake');
                    }
                });


            });


            jQuery('.ep-widget-filter-nav li a').on('click', function(e) {
                jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('');
                jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('').attr('bdt-filter-control', '');
            });


            jQuery(document).ready(function($) {
                'use strict';

                function hashHandler() {
                    var $tab = jQuery('.element-pack-dashboard .bdt-tab');
                    if (window.location.hash) {
                        var hash = window.location.hash.substring(1);
                        bdtUIkit.tab($tab).show(jQuery('#bdt-' + hash).data('tab-index'));
                    }
                }

                jQuery(window).on('load', function() {
                    hashHandler();
                });

                window.addEventListener("hashchange", hashHandler, true);

                jQuery('.toplevel_page_element_pack_options > ul > li > a ').on('click', function(event) {
                    jQuery(this).parent().siblings().removeClass('current');
                    jQuery(this).parent().addClass('current');
                });

                jQuery('#element_pack_active_modules_page a.ep-active-all-widget').click(function() {

                    jQuery('#element_pack_active_modules_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).attr('checked', 'checked').prop("checked", true);
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
                });

                jQuery('#element_pack_active_modules_page a.ep-deactive-all-widget').click(function() {

                    jQuery('#element_pack_active_modules_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).removeAttr('checked');
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-active-all-widget').removeClass('bdt-active');
                });

                jQuery('#element_pack_third_party_widget_page a.ep-active-all-widget').click(function() {

                    jQuery('#element_pack_third_party_widget_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).attr('checked', 'checked').prop("checked", true);
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
                });

                jQuery('#element_pack_third_party_widget_page a.ep-deactive-all-widget').click(function() {

                    jQuery('#element_pack_third_party_widget_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).removeAttr('checked');
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-active-all-widget').removeClass('bdt-active');
                });

                jQuery('#element_pack_elementor_extend_page a.ep-active-all-widget').click(function() {

                    jQuery('#element_pack_elementor_extend_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).attr('checked', 'checked').prop("checked", true);
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-deactive-all-widget').removeClass('bdt-active');
                });

                jQuery('#element_pack_elementor_extend_page a.ep-deactive-all-widget').click(function() {

                    jQuery('#element_pack_elementor_extend_page .ep-widget-free .checkbox:visible').each(function() {
                        jQuery(this).removeAttr('checked');
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ep-active-all-widget').removeClass('bdt-active');
                });

                jQuery('form.settings-save').submit(function(event) {
                    event.preventDefault();

                    bdtUIkit.notification({
                        message: '<div bdt-spinner></div> <?php esc_html_e('Please wait, Saving settings...', 'bdthemes-element-pack') ?>',
                        timeout: false
                    });

                    jQuery(this).ajaxSubmit({
                        success: function() {
                            bdtUIkit.notification.closeAll();
                            bdtUIkit.notification({
                                message: '<span class="dashicons dashicons-yes"></span> <?php esc_html_e('Settings Saved Successfully.', 'bdthemes-element-pack') ?>',
                                status: 'primary'
                            });
                        },
                        error: function(data) {
                            bdtUIkit.notification.closeAll();
                            bdtUIkit.notification({
                                message: '<span bdt-icon=\'icon: warning\'></span> <?php esc_html_e('Unknown error, make sure access is correct!', 'bdthemes-element-pack') ?>',
                                status: 'warning'
                            });
                        }
                    });

                    return false;
                });

            });
        </script>
    <?php
    }

    /**
     * Display Footer
     *
     * @access public
     * @return void
     */

    function footer_info() {
    ?>

        <div class="element-pack-footer-info bdt-margin-medium-top">

            <div class="bdt-grid ">

                <div class="bdt-width-auto@s ep-setting-save-btn">



                </div>

                <div class="bdt-width-expand@s bdt-text-right">
                    <p class="">
                        Element Pack plugin made with love by <a target="_blank" href="https://bdthemes.com">BdThemes</a> Team.
                        <br>All rights reserved by <a target="_blank" href="https://bdthemes.com">BdThemes.com</a>.
                    </p>
                </div>
            </div>

        </div>

<?php
    }



    /**
     * v4 Notice
     * This notice is very important to show minimum 3 to 5 next update released version.
     *
     * @access public
     */

    public function v4_activate_notice() {

        Notices::add_notice(
            [
                'id'               => 'version-4',
                'type'             => 'warning',
                'dismissible'      => true,
                'dismissible-time' => 43200,
                'message'          => __('There are very important changes in our major version <strong>v4.0.0</strong>. If you are continuing with the Element Pack plugin from an earlier version of v4.0.0 then you must read this article carefully <a href="https://bdthemes.com/knowledge-base/read-before-upgrading-to-element-pack-pro-version-4-0" target="_blank">from here</a>. <br> And if you are using this plugin from v4.0.0 there is nothing to worry about you. Thank you.', 'bdthemes-element-pack'),
            ]
        );
    }
    /**
     * 
     * Allow Tracker deactivated warning
     * If Allow Tracker disable in elementor then this notice will be show
     *
     * @access public
     */

    public function allow_tracker_activate_notice() {

        Notices::add_notice(
            [
                'id'               => 'ep-allow-tracker',
                'type'             => 'warning',
                'dismissible'      => true,
                'dismissible-time' => MONTH_IN_SECONDS * 2,
                'message'          => __('Please activate <strong>Usage Data Sharing</strong> features from Elementor, otherwise Widgets Analytics will not work. Please activate the settings from <strong>Elementor > Settings > General Tab >  Usage Data Sharing.</strong> Thank you.', 'bdthemes-element-pack'),
            ]
        );
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages         = get_pages();
        $pages_options = [];
        if ($pages) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }
}

new ElementPack_Admin_Settings();
