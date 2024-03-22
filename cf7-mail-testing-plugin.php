<?php
/*
Plugin Name: Cf7 Mail Testing Plugin
Plugin URI: https://github.com/sureshit13/Cf7-Mail-Testing-Plugin.git
Description: This plugin skips sending emails for Contact Form 7 submissions and sends the email to a specified custom email address.
Version: 1.1
Author: Suresh Dutt
*/
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/sureshit13/Cf7-Mail-Testing-Plugin.git',
	__FILE__,
	'Cf7-Mail-Testing-Plugin'
);
$myUpdateChecker->setBranch('main');
$myUpdateChecker->setAuthentication('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');
// Add settings link to plugins list
add_filter('plugin_action_links', 'custom_plugin_settings_link', 10, 2);
function custom_plugin_settings_link($links, $file)
{
    // Check if the current plugin is your custom plugin
    if ($file == plugin_basename(__FILE__)) {
        // Add a new settings link
        $settings_link = '<a href="' . admin_url('options-general.php?page=custom-plugin') . '">' . __('Settings') . '</a>';
        array_push($links, $settings_link);
    }
    return $links;
}
// Hook into the admin menu
add_action('admin_menu', 'custom_menu_plugin_setup');

// Function to set up the menu
function custom_menu_plugin_setup()
{
    // Add a new top-level menu
    add_menu_page('Custom Plugin Settings', 'Contact Form 7 Mail Testing', 'manage_options', 'custom-plugin', 'custom_plugin_page');
    // Check for updates on plugin page load
    add_action('admin_init', 'custom_plugin_check_for_update');
}

// Function to check for plugin updates
function custom_plugin_check_for_update()
{
    $plugin_slug = plugin_basename(__FILE__);
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $plugin_version = $plugin_data['Version'];

    // Get the latest version of the plugin from the WordPress Plugin Repository
    $response = wp_remote_get('https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json');
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Compare the installed version with the latest version
        if (version_compare($plugin_version, $data['version'], '<')) {
            // Set plugin update notification
            add_action('admin_notices', function () use ($data) {
                echo '<div class="notice notice-info"><p>There is a new version of the Cf7 Mail Testing Plugin available: <strong>' . $data['version'] . '</strong>. <a href="' . $data['download_link'] . '">Update Now</a></p></div>';
            });
        }
    }
}

// Function to render the menu page
function custom_plugin_page()
{
    // Display plugin settings page content here
    $custom_email = get_option('custom_plugin_custom_email');
    $send_email_checkbox = get_option('custom_plugin_send_email_checkbox');
    $saved = isset ($_GET['saved']) ? $_GET['saved'] : false; // Check if form was saved successfully
    ?>
    <div class="wrap">
        <?php if ($saved): ?>
            <div class="updated">
                <p>Settings saved successfully!</p>
            </div>
        <?php endif; ?>
        <h2>Contact Form 7 Mail Testing</h2>
        <form method="post" action="admin-post.php" style="max-width: 500px;">
            <?php wp_nonce_field('custom-plugin-form-submit', 'custom-plugin-nonce'); ?>
            <div style="margin-bottom:10px;margin-top:30px;">
                <input type="checkbox" id="send_email_checkbox" name="send_email_checkbox" <?php checked($send_email_checkbox, 'on'); ?>>
                <label for="send_email_checkbox">Send Email to Custom Address</label>
            </div>
            <div style="margin-bottom: 20px;">
                <label for="custom_email">Mail Ids:</label>
                <input type="text" id="custom_email" name="custom_email" value="<?php echo esc_attr($custom_email); ?>"
                    style="width: 100%;" required>
            </div>
            <div>
                <input type="submit" name="submit" value="Save Settings" class="button button-primary" style="width: 100%;">
                <input type="hidden" name="action" value="custom_plugin_form_submit">
            </div>
        </form>
    </div>
    <?php
}

// Function to handle form submission
function handle_custom_plugin_form_submission()
{
    if (isset ($_POST['custom-plugin-nonce']) && wp_verify_nonce($_POST['custom-plugin-nonce'], 'custom-plugin-form-submit')) {
        // Handle form data here
        $custom_email = isset ($_POST['custom_email']) ? $_POST['custom_email'] : '';
        $send_email_checkbox = isset ($_POST['send_email_checkbox']) ? 'on' : 'off';

        // Save custom email address and checkbox value in options
        update_option('custom_plugin_custom_email', $custom_email);
        update_option('custom_plugin_send_email_checkbox', $send_email_checkbox);

        // Redirect after saving
        $redirect_url = add_query_arg(array('page' => 'custom-plugin', 'saved' => 'true'), admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
    }
}

// Hook into admin_post for form submission handling
add_action('admin_post_custom_plugin_form_submit', 'handle_custom_plugin_form_submission');

// Skip sending email on Contact Form 7 form submission
add_action('wpcf7_before_send_mail', 'skip_cf7_email_sending');
function skip_cf7_email_sending($contact_form)
{

    // Get custom email address from options
    $custom_email = get_option('custom_plugin_custom_email');
    $send_email_checkbox = get_option('custom_plugin_send_email_checkbox');

    $multiple_recipients = explode(',', $custom_email);
    $multiple_recipient = array_map('trim', $multiple_recipients);

    //Check if checkbox is checked and custom email is not empty
    if ($send_email_checkbox == 'on' && !empty ($custom_email)) {
        $contact_form->skip_mail = true;
        $submission = WPCF7_Submission::get_instance();
        $posted_data = $submission->get_posted_data();
        // Extracting data from the $posted_data array
        $first_name = isset ($posted_data['first-name']) ? $posted_data['first-name'] : '';
        $last_name = isset ($posted_data['last-name']) ? $posted_data['last-name'] : '';
        $phone = isset ($posted_data['phone']) ? $posted_data['phone'] : '';
        $email = isset ($posted_data['email']) ? $posted_data['email'] : '';
        $message = isset ($posted_data['message']) ? $posted_data['message'] : '';
        $subject = 'BEARDOG DIGITAL Lead:' . $first_name . ' Contacting Law Offices of Andrew Cohen.';
        $body = '<html><style>table tr th,table tr td {vertical-align:top}</style>
            <body><div class="main-box" style="border:1px solid #dddddd;border-radius:5px; width:650px;float:none;text-align:center; margin:40px auto; overflow:hidden;">
            <div class="mb-header" style="background:#79ccfe;padding:20px 30px;">
                <a href="https://andrewcohenlegal.com/"><img src="https://andrewcohen.thebearmarketingfirm.com/wp-content/themes/beardog/assets/images/andrewcohenlegal-logo.png" width="275" /></a>
            </div>
            <div class="mb-content" style="background:#fff; padding:25px;text-align:left;">
                <table cellpadding="5" cellspacing="0" width="100%">
                <tr><td colspan="2">A new inquiry has been submitted to the firm. Please review the details below.</td></tr>
                <tr><th width="150" valign="top">First Name:</th><td>' . $first_name . '</td></tr>
                <tr><th valign="top">Last Name:</th><td>' . $last_name . '</td></tr>
                <tr><th valign="top">Phone:</th><td>' . $phone . '</td></tr>
                <tr><th valign="top">Email:</th><td>' . $email . '</td></tr>
                <tr><th valign="top">Message:</th><td>' . $message . '</td></tr>
                <tr><td colspan="2">This e-mail was sent from a contact form on <a  target="_blank" href="https://andrewcohenlegal.com/">Law Offices of Andrew Cohen</a>.</td></tr>
                </table>
            </div>
            <div class="mb-footer" style="background:#DBE8F9;padding:15px;text-align:center;">
                <a href="https://beardog.digital/" title="beardog" target="_blank"><img src="https://andrewcohen.thebearmarketingfirm.com/wp-content/themes/beardog/assets/images/beardog-logo.png" alt="Beardog" style="width:120px;"></a>
            </div>
            </div></body></html>';
        // if html not showing correctly in mail then add below header
        $headers = array('Content-Type: text/html; charset=UTF-8');
        // Send email to each address
        wp_mail($multiple_recipient, $subject, $body, $headers);
    }
}
?>