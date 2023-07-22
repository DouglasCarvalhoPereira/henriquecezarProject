<?php
/**
 * Simply Schedule Appointments Notices Data.
 *
 * @since   6.0.4
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Notices Data.
 *
 * @since 6.0.4
 */
class SSA_Notices_Data {


    /**
     * !! IMPORTANT: whenever a new notice is added & it requires any of:
     * ['installed_feature_any'], ['enabled_feature_any'], ['activated_feature_any'], ['not_installed_feature_any'], ['not_enabled_feature_any'], ['not_activated_feature_any']
     * PLEASE check the following methods in class-notices.php:
     * check_if_feature_installed, check_if_feature_enabled, check_if_feature_activated
     * 
     * 
     * References:
     * Features: check https://simplyscheduleappointments.com/pricing/
     * Editions: array( 'basic', 'plus', 'pro', 'business' ) -> array( '1', '2', '3', '4' )
     * 
     * The ['type'] field could have values: upgrade_promo, feature_promo, feature_setup, announcement, celebration, blog_post, error_msg, warning_msg
     * 
     * ['priority']: Could be used in this order:
     *              1 most important
     *              5 for error_msg
     *              10 for warning_msg
     *              15 for celebration
     *              20 for announcement
     *              25 for setting up specific feature
     *              30 for edition upgrade to unlock specific feature
     *              35 for edition upgrade
     *              40 least important
     */
	static $all_notices = array(
        array(
            'id' => 'promo-plus', // Unique ID that matches the promo component name in the frontend
            'name' => 'upsell-plus', // a name/label for this notice
            'description' => 'Upgrade to our Plus Edition and unlock awesome features',
            'type' => array( 'upgrade_promo' ),
            'active' => true, // if set to false we're gonna ignore it whatsoever
            'priority' => '35',
            'requires' => array(
                'current_edition_any' => array( '1' ), // if empty means no specific editions are required
                'min_appt_count' => '5', // Does this notice require minimum booked appointment count for this site? 
                'min_activated_days' => '', // Does it require a minimum activation day for SSA on this site
                'active_plugin_any' => array(), // Require a third party plugin to be installed e.g. 'gravity forms'
                'installed_feature_any' => array(), // They have the edition to use this feature, the files are there
                'enabled_feature_any' => array(), // The feature is turned on in the admin-app
                'activated_feature_any' => array(), // The feature is activated and all set up
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),
            )
        ),
        array(
            'id' => 'promo-pro',
            'name' => 'upsell-pro',
            'description' => 'Upgrade to our Pro Edition and unlock awesome features',
            'type' => array( 'upgrade_promo' ),
            'active' => true,
            'priority' => '35',
            'requires' => array(
                'current_edition_any' => array( '2' ),
                'min_appt_count' => '10',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'promo-business',
            'name' => 'upsell-business',
            'description' => 'Prompt users to upgrade to the Business Edition',
            'type' => array( 'upgrade_promo' ),
            'active' => false, // ignore this one
            'priority' => '35',
            'requires' => array(
                'current_edition_any' => array( '3' ),
                'min_appt_count' => '15',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'promo-zoom-upgrade',
            'name' => 'upsell-zoom',
            'description' => 'Prompt users to upgrade to the Plus Edition to unlock Zoom',
            'type' => array( 'feature_promo' ),
            'active' => true,
            'priority' => '30',
            'requires' => array(
                'current_edition_any' => array( '1' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'promo-zoom-announce',
            'name' => 'upsell-zoom',
            'description' => 'Zoom is available now; learn how to schedule zoom calls',
            'type' => array( 'feature_setup' ),
            'active' => true,
            'priority' => '25',
            'requires' => array(
                'current_edition_any' => array( '2', '3', '4' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array( 'zoom' ),
                'enabled_feature_any' => array( 'zoom' ),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array( 'zoom' ),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'support-setup-sms',
            'name' => 'upsell-sms',
            'description' => 'Get step-by-step instructions on getting set up to send SMS reminders to your customers',
            'type' => array( 'feature_setup' ),
            'active' => true,
            'priority' => '25',
            'requires' => array(
                'current_edition_any' => array( '3', '4' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array( 'sms' ),
                'enabled_feature_any' => array( 'sms' ),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array( 'sms' ),
                'current_user_can' => array( 'ssa_manage_site_settings' ),

            )
        ),
        array(
            'id' => 'payments-upsell',
            'name' => 'upsell-payments',
            'description' => 'Payments Upsell for Basic and Plus',
            'type' => array( 'feature_promo' ),
            'active' => true,
            'priority' => '30',
            'requires' => array(
                'current_edition_any' => array( '1', '2' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'promo-google-calendar',
            'name' => 'upsell-google-calendar',
            'description' => 'Unlock the Google Calendar Integration in the Plus edition to enjoy all of the features',
            'type' => array( 'feature_promo' ),
            'active' => true,
            'priority' => '30',
            'requires' => array(
                'current_edition_any' => array( '1' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array(),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'gravity-forms-info',
            'name' => 'upsell-gravity-forms',
            'description' => 'Learn about booking with Gravity Forms',
            'type' => array( 'feature_setup' ),
            'active' => true,
            'priority' => '25',
            'requires' => array(
                'current_edition_any' => array( '2', '3', '4' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array( 'gravityforms' ),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
        array(
            'id' => 'gravity-forms-upsell',
            'name' => 'upsell-gravity-forms-basic',
            'description' => 'Unlock the Gravity Forms Integration in the Plus edition to create highly customized booking forms',
            'type' => array( 'feature_promo' ),
            'active' => true,
            'priority' => '30',
            'requires' => array(
                'current_edition_any' => array( '1' ),
                'min_appt_count' => '',
                'min_activated_days' => '',
                'active_plugin_any' => array( 'gravityforms' ),
                'installed_feature_any' => array(),
                'enabled_feature_any' => array(),
                'activated_feature_any' => array(),
                'not_installed_feature_any' => array(),
                'not_enabled_feature_any' => array(),
                'not_activated_feature_any' => array(),
                'current_user_can' => array(),

            )
        ),
    );


    /**
     * Getter function in case we wanna apply any filters on notices data
     *
     * @return array
     */
    public static function get_notices_list() {
        
        $output = apply_filters( 'ssa/notices/all_notices', self::$all_notices );

        return $output;
    }

}