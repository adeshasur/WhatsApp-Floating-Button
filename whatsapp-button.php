<?php
/**
 * Plugin Name:       WhatsApp Floating Button
 * Plugin URI:        https://github.com/your-username/whatsapp-floating-button
 * Description:       Adds a floating WhatsApp contact button to the bottom-right corner of every page on your website. Configure your number from Settings → WhatsApp Button.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatsapp-floating-button
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────
// 1. CONSTANTS
// ─────────────────────────────────────────────
define( 'WAFB_VERSION',     '1.0.0' );
define( 'WAFB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WAFB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WAFB_OPTION_KEY',  'wafb_settings' );

// ─────────────────────────────────────────────
// 2. ACTIVATION HOOK — set default options
// ─────────────────────────────────────────────
register_activation_hook( __FILE__, 'wafb_activate' );
function wafb_activate() {
    if ( ! get_option( WAFB_OPTION_KEY ) ) {
        add_option( WAFB_OPTION_KEY, array(
            'phone_number'    => '',
            'tooltip_text'    => 'Chat with us on WhatsApp!',
            'button_position' => 'right',
            'show_on_mobile'  => '1',
        ) );
    }
}

// ─────────────────────────────────────────────
// 3. ADMIN MENU & SETTINGS PAGE
// ─────────────────────────────────────────────
add_action( 'admin_menu', 'wafb_add_settings_page' );
function wafb_add_settings_page() {
    add_options_page(
        __( 'WhatsApp Floating Button', 'whatsapp-floating-button' ),
        __( 'WhatsApp Button',          'whatsapp-floating-button' ),
        'manage_options',
        'wafb-settings',
        'wafb_render_settings_page'
    );
}

// Register settings / sections / fields.
add_action( 'admin_init', 'wafb_register_settings' );
function wafb_register_settings() {
    register_setting(
        'wafb_settings_group',
        WAFB_OPTION_KEY,
        array(
            'sanitize_callback' => 'wafb_sanitize_settings',
        )
    );

    add_settings_section(
        'wafb_main_section',
        __( 'Button Configuration', 'whatsapp-floating-button' ),
        '__return_false',
        'wafb-settings'
    );

    // Phone number field.
    add_settings_field(
        'phone_number',
        __( 'WhatsApp Phone Number', 'whatsapp-floating-button' ),
        'wafb_field_phone_number',
        'wafb-settings',
        'wafb_main_section'
    );

    // Tooltip / pre-filled message field.
    add_settings_field(
        'tooltip_text',
        __( 'Tooltip Text', 'whatsapp-floating-button' ),
        'wafb_field_tooltip_text',
        'wafb-settings',
        'wafb_main_section'
    );

    // Button position field.
    add_settings_field(
        'button_position',
        __( 'Button Position', 'whatsapp-floating-button' ),
        'wafb_field_button_position',
        'wafb-settings',
        'wafb_main_section'
    );

    // Show on mobile field.
    add_settings_field(
        'show_on_mobile',
        __( 'Show on Mobile', 'whatsapp-floating-button' ),
        'wafb_field_show_on_mobile',
        'wafb-settings',
        'wafb_main_section'
    );
}

// ── Sanitization ────────────────────────────
function wafb_sanitize_settings( $input ) {
    $output = array();

    // Strip everything except digits and '+'.
    $output['phone_number'] = preg_replace( '/[^\d+]/', '', sanitize_text_field( $input['phone_number'] ?? '' ) );

    $output['tooltip_text']    = sanitize_text_field( $input['tooltip_text'] ?? '' );
    $output['button_position'] = in_array( $input['button_position'] ?? '', array( 'left', 'right' ), true )
        ? $input['button_position']
        : 'right';
    $output['show_on_mobile']  = ! empty( $input['show_on_mobile'] ) ? '1' : '0';

    return $output;
}

// ── Field callbacks ──────────────────────────
function wafb_field_phone_number() {
    $opts = get_option( WAFB_OPTION_KEY, array() );
    $val  = esc_attr( $opts['phone_number'] ?? '' );
    echo '<input type="tel" id="wafb_phone_number" name="' . WAFB_OPTION_KEY . '[phone_number]"
               value="' . $val . '" class="regular-text" placeholder="+1234567890" />';
    echo '<p class="description">' . esc_html__( 'Include country code, e.g. +1234567890. No spaces or dashes.', 'whatsapp-floating-button' ) . '</p>';
}

function wafb_field_tooltip_text() {
    $opts = get_option( WAFB_OPTION_KEY, array() );
    $val  = esc_attr( $opts['tooltip_text'] ?? 'Chat with us on WhatsApp!' );
    echo '<input type="text" id="wafb_tooltip_text" name="' . WAFB_OPTION_KEY . '[tooltip_text]"
               value="' . $val . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__( 'Text shown when hovering over the button.', 'whatsapp-floating-button' ) . '</p>';
}

function wafb_field_button_position() {
    $opts = get_option( WAFB_OPTION_KEY, array() );
    $pos  = $opts['button_position'] ?? 'right';
    ?>
    <select id="wafb_button_position" name="<?php echo WAFB_OPTION_KEY; ?>[button_position]">
        <option value="right" <?php selected( $pos, 'right' ); ?>><?php esc_html_e( 'Bottom Right', 'whatsapp-floating-button' ); ?></option>
        <option value="left"  <?php selected( $pos, 'left' );  ?>><?php esc_html_e( 'Bottom Left',  'whatsapp-floating-button' ); ?></option>
    </select>
    <?php
}

function wafb_field_show_on_mobile() {
    $opts    = get_option( WAFB_OPTION_KEY, array() );
    $checked = ! empty( $opts['show_on_mobile'] ) ? 'checked' : '';
    echo '<label>';
    echo '<input type="checkbox" id="wafb_show_on_mobile" name="' . WAFB_OPTION_KEY . '[show_on_mobile]" value="1" ' . $checked . ' />';
    echo ' ' . esc_html__( 'Display the button on mobile devices', 'whatsapp-floating-button' );
    echo '</label>';
}

// ── Render the settings page HTML ───────────
function wafb_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap" id="wafb-settings-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;
                         width:36px;height:36px;border-radius:50%;background:#25D366;">
                <?php echo wafb_whatsapp_svg( '#ffffff', 22 ); ?>
            </span>
            <?php esc_html_e( 'WhatsApp Floating Button', 'whatsapp-floating-button' ); ?>
        </h1>

        <?php settings_errors( 'wafb_messages' ); ?>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'wafb_settings_group' );
                do_settings_sections( 'wafb-settings' );
                submit_button( __( 'Save Settings', 'whatsapp-floating-button' ) );
            ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Preview', 'whatsapp-floating-button' ); ?></h2>
        <p><?php esc_html_e( 'This is how the button will appear on your website:', 'whatsapp-floating-button' ); ?></p>

        <?php
        $opts    = get_option( WAFB_OPTION_KEY, array() );
        $tooltip = esc_html( $opts['tooltip_text'] ?? 'Chat with us on WhatsApp!' );
        ?>
        <div style="position:relative;height:100px;width:100%;max-width:500px;
                    background:#f0f0f1;border-radius:6px;border:1px solid #c3c4c7;">
            <div style="position:absolute;bottom:16px;right:16px;">
                <div class="wafb-btn" style="cursor:default;" title="<?php echo $tooltip; ?>">
                    <?php echo wafb_whatsapp_svg( '#ffffff', 28 ); ?>
                </div>
                <span style="display:block;text-align:center;margin-top:6px;
                             font-size:11px;color:#555;"><?php esc_html_e( 'Preview', 'whatsapp-floating-button' ); ?></span>
            </div>
        </div>

        <!-- Inline preview style only shown on admin page -->
        <style>
            .wafb-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background-color: #25D366;
                box-shadow: 0 4px 12px rgba(37,211,102,.45);
            }
        </style>
    </div>
    <?php
}

// ─────────────────────────────────────────────
// 4. FRONTEND — enqueue CSS & render button
// ─────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'wafb_enqueue_assets' );
function wafb_enqueue_assets() {
    $opts  = get_option( WAFB_OPTION_KEY, array() );
    $phone = $opts['phone_number'] ?? '';

    // Don't load assets when no number is set.
    if ( empty( $phone ) ) {
        return;
    }

    wp_enqueue_style(
        'wafb-style',
        WAFB_PLUGIN_URL . 'css/whatsapp-button.css',
        array(),
        WAFB_VERSION
    );
}

add_action( 'wp_footer', 'wafb_render_button' );
function wafb_render_button() {
    $opts    = get_option( WAFB_OPTION_KEY, array() );
    $phone   = $opts['phone_number']    ?? '';
    $tooltip = $opts['tooltip_text']    ?? 'Chat with us on WhatsApp!';
    $pos     = $opts['button_position'] ?? 'right';
    $mobile  = $opts['show_on_mobile']  ?? '1';

    // Do nothing if no phone number is saved.
    if ( empty( $phone ) ) {
        return;
    }

    // Build the WhatsApp URL.
    $url = 'https://wa.me/' . ltrim( $phone, '+' );

    $position_class  = ( $pos === 'left' ) ? 'wafb--left' : 'wafb--right';
    $mobile_class    = ( $mobile !== '1' ) ? ' wafb--hide-mobile' : '';

    ?>
    <a  id="wafb-floating-btn"
        href="<?php echo esc_url( $url ); ?>"
        class="wafb-floating-btn <?php echo esc_attr( $position_class . $mobile_class ); ?>"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="<?php echo esc_attr( $tooltip ); ?>"
        title="<?php echo esc_attr( $tooltip ); ?>">
        <?php echo wafb_whatsapp_svg( '#ffffff', 30 ); ?>
        <span class="wafb-tooltip"><?php echo esc_html( $tooltip ); ?></span>
    </a>
    <?php
}

// ─────────────────────────────────────────────
// 5. HELPER — inline SVG WhatsApp icon
// ─────────────────────────────────────────────
function wafb_whatsapp_svg( $fill = '#ffffff', $size = 30 ) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                 width="' . intval( $size ) . '" height="' . intval( $size ) . '"
                 aria-hidden="true" focusable="false">
        <path fill="' . esc_attr( $fill ) . '"
              d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222
                 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27
                 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67-157z
                 M223.9 438.5c-33.1 0-65.5-8.9-93.7-25.7l-6.7-4-69.8 18.3
                 18.6-67.5-4.4-6.9C51 323.1 41.7 287.9 41.7 254
                 c0-101.3 82.5-183.8 184.2-183.8 49.1 0 95.3 19.1 130 53.9
                 34.7 34.7 56.4 80.9 56.3 130-.1 101.4-82.6 183.9-184.3 183.9z
                 m100.9-137.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5
                 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4
                 -5.5-2.8-23.2-8.6-44.2-27.3-16.3-14.6-27.3-32.6-30.5-38.1
                 -3.2-5.6-.3-8.6 2.4-11.4 2.4-2.5 5.5-6.5 8.2-9.7
                 2.8-3.2 3.7-5.6 5.5-9.2 1.9-3.7.9-6.9-.5-9.7
                 -1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5
                 -3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9
                 -5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4
                 2.8 3.7 39.1 59.7 94.8 83.8 13.2 5.7 23.6 9.1 31.6 11.7
                 13.3 4.2 25.4 3.6 34.9 2.2 10.7-1.6 32.8-13.4 37.4-26.4
                 4.6-12.9 4.6-24 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
    </svg>';
}
