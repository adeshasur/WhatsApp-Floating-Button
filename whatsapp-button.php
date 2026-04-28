<?php
/**
 * Plugin Name:       WhatsApp Floating Button
 * Plugin URI:        https://github.com/adeshasur/WhatsApp-Floating-Button
 * Description:       Advanced floating WhatsApp button with chat bubble popup, business hours, custom colours, pre-filled messages, click analytics, and page visibility rules.
 * Version:           2.1.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Adheesha Sooriyaarachchi
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatsapp-floating-button
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load plugin textdomain for translations.
 */
add_action( 'init', function () {
    load_plugin_textdomain( 'whatsapp-floating-button', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// ─────────────────────────────────────────────
// CONSTANTS
// ─────────────────────────────────────────────
define( 'WAFB_VERSION',    '2.0.0' );
define( 'WAFB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WAFB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAFB_OPTION_KEY', 'wafb_settings' );

// ─────────────────────────────────────────────
// DEFAULT SETTINGS
// ─────────────────────────────────────────────
function wafb_defaults() {
    return array(
        // General
        'phone_number'      => '',
        'prefilled_message' => '',
        'tooltip_text'      => __( 'Chat with us on WhatsApp!', 'whatsapp-floating-button' ),
        'button_position'   => 'right',
        'show_on_mobile'    => '1',
        // Appearance
        'button_color'      => '#00A884',
        'button_size'       => 'medium',
        'button_label'      => '',
        'animation_style'   => 'pulse',
        // Chat Bubble
        'bubble_enable'     => '0',
        'bubble_name'       => __( 'Support Team', 'whatsapp-floating-button' ),
        'bubble_text'       => __( "Hi there! 👋\nHow can I help you today?", 'whatsapp-floating-button' ),
        'bubble_delay'      => '3',
        'bubble_avatar'     => '',
        // Visibility
        'visibility_type'   => 'all',
        'visibility_pages'  => array(),
        'hours_enable'      => '0',
        'hours_start'       => '09:00',
        'hours_end'         => '17:00',
        'hours_days'        => array( '1', '2', '3', '4', '5' ),
        'hours_timezone'    => 'UTC',
        // Analytics
        'tracking_enable'   => '1',
        'click_count'       => 0,
    );
}

// Fetch settings merged with defaults.
function wafb_get_settings() {
    return wp_parse_args( get_option( WAFB_OPTION_KEY, array() ), wafb_defaults() );
}

// ─────────────────────────────────────────────
// ACTIVATION
// ─────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    if ( ! get_option( WAFB_OPTION_KEY ) ) {
        add_option( WAFB_OPTION_KEY, wafb_defaults() );
    }
} );

// ─────────────────────────────────────────────
// ADMIN MENU
// ─────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'WhatsApp Floating Button Settings', 'whatsapp-floating-button' ),
        __( 'WhatsApp Button', 'whatsapp-floating-button' ),
        'manage_options',
        'wafb-settings',
        'wafb_render_settings_page'
    );
} );

// ─────────────────────────────────────────────
// REGISTER SETTINGS & SANITISE
// ─────────────────────────────────────────────
add_action( 'admin_init', function () {
    register_setting( 'wafb_group', WAFB_OPTION_KEY, array(
        'sanitize_callback' => 'wafb_sanitize',
    ) );
} );

function wafb_sanitize( $input ) {
    $d   = wafb_defaults();
    $out = array();

    // ── General ──────────────────────────────────────────
    $out['phone_number']      = preg_replace( '/[^\d+]/', '', sanitize_text_field( $input['phone_number'] ?? '' ) );
    $out['prefilled_message'] = sanitize_textarea_field( $input['prefilled_message'] ?? '' );
    $out['tooltip_text']      = sanitize_text_field( $input['tooltip_text'] ?? $d['tooltip_text'] );
    $out['button_position']   = in_array( $input['button_position'] ?? '', array( 'left', 'right' ), true )
        ? $input['button_position'] : 'right';
    $out['show_on_mobile']    = ! empty( $input['show_on_mobile'] ) ? '1' : '0';

    // ── Appearance ────────────────────────────────────────
    $out['button_color']    = sanitize_hex_color( $input['button_color'] ?? $d['button_color'] ) ?: $d['button_color'];
    $out['button_size']     = in_array( $input['button_size'] ?? '', array( 'small', 'medium', 'large' ), true )
        ? $input['button_size'] : 'medium';
    $out['button_label']    = sanitize_text_field( $input['button_label'] ?? '' );
    $out['animation_style'] = in_array( $input['animation_style'] ?? '', array( 'pulse', 'bounce', 'shake', 'none' ), true )
        ? $input['animation_style'] : 'pulse';

    // ── Chat Bubble ───────────────────────────────────────
    $out['bubble_enable'] = ! empty( $input['bubble_enable'] ) ? '1' : '0';
    $out['bubble_name']   = sanitize_text_field( $input['bubble_name'] ?? $d['bubble_name'] );
    $out['bubble_text']   = sanitize_textarea_field( $input['bubble_text'] ?? $d['bubble_text'] );
    $out['bubble_delay']  = absint( $input['bubble_delay'] ?? 3 );
    $out['bubble_avatar'] = esc_url_raw( $input['bubble_avatar'] ?? '' );

    // ── Visibility ────────────────────────────────────────
    $out['visibility_type']  = in_array( $input['visibility_type'] ?? '', array( 'all', 'include', 'exclude' ), true )
        ? $input['visibility_type'] : 'all';
    $raw_pages               = $input['visibility_pages'] ?? array();
    $out['visibility_pages'] = is_array( $raw_pages ) ? array_map( 'absint', $raw_pages ) : array();
    $out['hours_enable']     = ! empty( $input['hours_enable'] ) ? '1' : '0';
    $out['hours_start']      = sanitize_text_field( $input['hours_start'] ?? '09:00' );
    $out['hours_end']        = sanitize_text_field( $input['hours_end']   ?? '17:00' );
    $raw_days                = is_array( $input['hours_days'] ?? null ) ? $input['hours_days'] : array();
    $out['hours_days']       = array_values( array_intersect( $raw_days, array( '0','1','2','3','4','5','6' ) ) );
    $out['hours_timezone']   = sanitize_text_field( $input['hours_timezone'] ?? 'UTC' );

    // ── Analytics ─────────────────────────────────────────
    $existing           = get_option( WAFB_OPTION_KEY, array() );
    $out['tracking_enable'] = ! empty( $input['tracking_enable'] ) ? '1' : '0';
    $out['click_count']     = ! empty( $input['reset_clicks'] )
        ? 0
        : absint( $existing['click_count'] ?? 0 );

    return $out;
}

// ─────────────────────────────────────────────
// SETTINGS PAGE
// ─────────────────────────────────────────────
function wafb_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $s = wafb_get_settings();
    ?>
    <div class="wrap" id="wafb-wrap">
    <style>
        #wafb-wrap { max-width: 1000px; margin-top: 20px; }
        #wafb-tabs-nav { background: #fff; padding: 10px 10px 0; border-radius: 8px 8px 0 0; border: 1px solid #e5e5e5; border-bottom: none; }
        #wafb-tabs-nav .nav-tab { 
            border: none; background: transparent; margin: 0 5px 0 0; padding: 10px 18px; 
            font-weight: 600; font-size: 14px; color: #666; border-radius: 6px 6px 0 0;
            transition: all 0.2s;
        }
        #wafb-tabs-nav .nav-tab-active { background: #f0f0f1; color: #2271b1; box-shadow: inset 0 -3px 0 #2271b1; }
        #wafb-tabs-nav .nav-tab:hover:not(.nav-tab-active) { background: #f6f7f7; color: #1d2327; }
        
        .wafb-tab-content { 
            background: #fff; padding: 25px 30px; border: 1px solid #e5e5e5; 
            border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .form-table th { font-weight: 600; color: #1d2327; width: 220px; }
        .form-table td { padding: 15px 10px; }
    </style>

        <h1 style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;
                         width:40px;height:40px;border-radius:50%;
                         background:<?php echo esc_attr( $s['button_color'] ); ?>;
                         box-shadow:0 3px 10px rgba(0,0,0,.2);">
                <?php echo wafb_svg( '#fff', 22 ); ?>
            </span>
            <?php _e( 'WhatsApp Floating Button', 'whatsapp-floating-button' ); ?>
            <span style="font-size:12px;font-weight:400;color:#999;align-self:flex-end;margin-bottom:4px;">v<?php echo esc_html( WAFB_VERSION ); ?></span>
        </h1>
        
        <div style="background:#fff; border:1px solid #e5e5e5; border-radius:8px; padding:15px 20px; margin-bottom:25px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <div>
                <p style="margin:0; font-weight:600; color:#333;"><?php _e( 'Want to show the button via Shortcode?', 'whatsapp-floating-button' ); ?></p>
                <p style="margin:0; font-size:13px; color:#666;"><?php _e( 'Paste this anywhere on your pages or posts.', 'whatsapp-floating-button' ); ?></p>
            </div>
            <code style="background:#f0f0f1; padding:8px 15px; border-radius:5px; font-size:14px; border:1px solid #dcdcde; cursor:pointer;" onclick="navigator.clipboard.writeText('[whatsapp_button]') && alert('Shortcode copied!')" title="<?php esc_attr_e( 'Click to copy', 'whatsapp-floating-button' ); ?>">[whatsapp_button]</code>
        </div>

        <p style="color:#666;margin-bottom:20px;"><?php _e( 'Configure your floating WhatsApp button from one place.', 'whatsapp-floating-button' ); ?></p>

        <div id="wafb-admin-preview-sticky" style="position:fixed; top:150px; right:40px; width:300px; z-index:100; display:none;">
            <div style="background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1);">
                <h3 style="margin-top:0; font-size:14px; border-bottom:1px solid #eee; padding-bottom:10px;"><?php _e( 'Live Preview', 'whatsapp-floating-button' ); ?></h3>
                <div id="wafb-live-preview-container" style="height:150px; display:flex; align-items:center; justify-content:center; background:#f9f9f9; border-radius:8px; position:relative; overflow:hidden;">
                    <!-- Live preview content will be injected by JS -->
                </div>
                <p style="font-size:11px; color:#888; margin-top:10px; text-align:center;"><?php _e( 'Note: Some animations only show on frontend.', 'whatsapp-floating-button' ); ?></p>
            </div>
        </div>

        <?php settings_errors( 'wafb_messages' ); ?>

        <!-- TAB NAV -->
        <nav class="nav-tab-wrapper" id="wafb-tabs-nav" style="margin-bottom:0;">
            <a href="#tab-general"    class="nav-tab nav-tab-active" data-tab="tab-general">⚙️ &nbsp;<?php _e( 'General', 'whatsapp-floating-button' ); ?></a>
            <a href="#tab-appearance" class="nav-tab" data-tab="tab-appearance">🎨 &nbsp;<?php _e( 'Appearance', 'whatsapp-floating-button' ); ?></a>
            <a href="#tab-bubble"     class="nav-tab" data-tab="tab-bubble">💬 &nbsp;<?php _e( 'Chat Bubble', 'whatsapp-floating-button' ); ?></a>
            <a href="#tab-visibility" class="nav-tab" data-tab="tab-visibility">👁️ &nbsp;<?php _e( 'Visibility', 'whatsapp-floating-button' ); ?></a>
            <a href="#tab-analytics"  class="nav-tab" data-tab="tab-analytics">📊 &nbsp;<?php _e( 'Analytics', 'whatsapp-floating-button' ); ?></a>
            <a href="#tab-support"    class="nav-tab" data-tab="tab-support">❓ &nbsp;<?php _e( 'Support & More', 'whatsapp-floating-button' ); ?></a>
        </nav>

        <form method="post" action="options.php" id="wafb-settings-form"
              style="background:#fff;border:1px solid #c3c4c7;border-top:none;border-radius:0 0 6px 6px;padding:20px 24px;">
            <?php settings_fields( 'wafb_group' ); ?>

            <!-- ══ TAB: GENERAL ══════════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-general">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wafb_phone"><?php _e( 'WhatsApp Phone Number', 'whatsapp-floating-button' ); ?> <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="tel" id="wafb_phone"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[phone_number]"
                                   value="<?php echo esc_attr( $s['phone_number'] ); ?>"
                                   class="regular-text" placeholder="+94771234567" />
                            <p class="description"><?php _e( 'Include country code. No spaces or dashes. e.g.', 'whatsapp-floating-button' ); ?> <code>+94771234567</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_message"><?php _e( 'Pre-filled Message', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <textarea id="wafb_message"
                                      name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[prefilled_message]"
                                      rows="3" class="large-text"
                                      placeholder="<?php esc_attr_e( "Hi! I'm interested in...", 'whatsapp-floating-button' ); ?>"><?php echo esc_textarea( $s['prefilled_message'] ); ?></textarea>
                            <p class="description"><?php _e( 'Automatically typed into WhatsApp when the user opens the chat. Leave empty to open a blank chat.', 'whatsapp-floating-button' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_tooltip"><?php _e( 'Hover Tooltip Text', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <input type="text" id="wafb_tooltip"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[tooltip_text]"
                                   value="<?php echo esc_attr( $s['tooltip_text'] ); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Button Position', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <fieldset>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[button_position]"
                                           value="right" <?php checked( $s['button_position'], 'right' ); ?>>
                                    <?php _e( 'Bottom Right', 'whatsapp-floating-button' ); ?>
                                </label>
                                <label>
                                    <input type="radio" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[button_position]"
                                           value="left" <?php checked( $s['button_position'], 'left' ); ?>>
                                    <?php _e( 'Bottom Left', 'whatsapp-floating-button' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Show on Mobile', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[show_on_mobile]"
                                       value="1" <?php checked( $s['show_on_mobile'], '1' ); ?>>
                                <?php _e( 'Display the button on mobile devices (< 768px)', 'whatsapp-floating-button' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ══ TAB: APPEARANCE ═══════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-appearance" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wafb_color"><?php _e( 'Button Colour', 'whatsapp-floating-button' ); ?></label></th>
                        <td style="display:flex;align-items:center;gap:12px;padding-top:12px;">
                            <input type="color" id="wafb_color"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[button_color]"
                                   value="<?php echo esc_attr( $s['button_color'] ); ?>"
                                   style="width:54px;height:38px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid #ddd;" />
                            <span style="color:#666;font-size:13px;"><?php _e( 'Default:', 'whatsapp-floating-button' ); ?> <code>#25D366</code> (<?php _e( 'WhatsApp Green', 'whatsapp-floating-button' ); ?>)</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Button Size', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <fieldset>
                                <?php
                                $sizes = array(
                                    'small'  => __( 'Small (48 px)', 'whatsapp-floating-button' ),
                                    'medium' => __( 'Medium (60 px)', 'whatsapp-floating-button' ),
                                    'large'  => __( 'Large (72 px)', 'whatsapp-floating-button' ),
                                );
                                foreach ( $sizes as $val => $lbl ) :
                                ?>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[button_size]"
                                           value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['button_size'], $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_label"><?php _e( 'Button Label Text', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <input type="text" id="wafb_label"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[button_label]"
                                   value="<?php echo esc_attr( $s['button_label'] ); ?>"
                                   class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Chat with us', 'whatsapp-floating-button' ); ?>" />
                            <p class="description"><?php _e( 'Optional. The button expands into a pill shape on hover to reveal this text. Leave empty for icon-only.', 'whatsapp-floating-button' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Animation Style', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <fieldset>
                                <?php
                                $anims = array(
                                    'pulse'  => __( '🔴 Pulse Ring', 'whatsapp-floating-button' ),
                                    'bounce' => __( '⬆️ Bounce', 'whatsapp-floating-button' ),
                                    'shake'  => __( '📳 Shake', 'whatsapp-floating-button' ),
                                    'none'   => __( '⏹️ None', 'whatsapp-floating-button' ),
                                );
                                foreach ( $anims as $val => $lbl ) :
                                ?>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[animation_style]"
                                           value="<?php echo esc_attr( $val ); ?>" <?php checked( $s['animation_style'], $val ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ══ TAB: CHAT BUBBLE ══════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-bubble" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e( 'Enable Chat Bubble', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="wafb_bubble_enable"
                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[bubble_enable]"
                                       value="1" <?php checked( $s['bubble_enable'], '1' ); ?>>
                                <?php _e( 'Show a chat popup bubble above the button after a delay', 'whatsapp-floating-button' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_name"><?php _e( 'Agent / Team Name', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <input type="text" id="wafb_bubble_name"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[bubble_name]"
                                   value="<?php echo esc_attr( $s['bubble_name'] ); ?>"
                                   class="regular-text" placeholder="<?php esc_attr_e( 'Support Team', 'whatsapp-floating-button' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_text"><?php _e( 'Bubble Message', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <textarea id="wafb_bubble_text"
                                      name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[bubble_text]"
                                      rows="3" class="large-text"><?php echo esc_textarea( $s['bubble_text'] ); ?></textarea>
                            <p class="description"><?php _e( 'Supports emojis. Line breaks are preserved.', 'whatsapp-floating-button' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_avatar"><?php _e( 'Agent Avatar URL', 'whatsapp-floating-button' ); ?></label></th>
                        <td>
                            <input type="url" id="wafb_bubble_avatar"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[bubble_avatar]"
                                   value="<?php echo esc_url( $s['bubble_avatar'] ); ?>"
                                   class="large-text" placeholder="https://example.com/avatar.jpg" />
                            <p class="description"><?php _e( 'Optional profile photo URL. Leave empty to use the WhatsApp icon.', 'whatsapp-floating-button' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_delay"><?php _e( 'Show After', 'whatsapp-floating-button' ); ?></label></th>
                        <td style="display:flex;align-items:center;gap:10px;padding-top:12px;">
                            <input type="number" id="wafb_bubble_delay"
                                   name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[bubble_delay]"
                                   value="<?php echo absint( $s['bubble_delay'] ); ?>"
                                   min="0" max="120" style="width:80px;" />
                            <span style="color:#555;"><?php _e( 'seconds after page load', 'whatsapp-floating-button' ); ?></span>
                        </td>
                    </tr>
                </table>

                <!-- Bubble preview -->
                <?php
                $preview_name   = esc_html( $s['bubble_name'] ?: 'Support Team' );
                $preview_text   = nl2br( esc_html( $s['bubble_text'] ?: "Hi there! 👋\nHow can I help you today?" ) );
                $preview_color  = esc_attr( $s['button_color'] );
                $preview_avatar = esc_url( $s['bubble_avatar'] );
                ?>
                <hr style="margin:20px 0;" />
                <h3 style="margin-bottom:8px;"><?php _e( 'Live Preview', 'whatsapp-floating-button' ); ?></h3>
                <div style="position:relative;width:320px;margin:0 auto;">
                    <div class="wafb-bubble-preview" style="
                        width:300px;    background:   rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border:       1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    box-shadow:   0 12px 40px rgba(0, 0, 0, 0.12);
overflow:hidden;
                        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
                        <div style="background:<?php echo $preview_color; ?>;padding:14px 18px;display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;border-radius:50%;overflow:hidden;
                                        background:rgba(255,255,255,.2);flex-shrink:0;
                                        display:flex;align-items:center;justify-content:center;">
                                <?php if ( $preview_avatar ) : ?>
                                    <img src="<?php echo $preview_avatar; ?>" alt="" style="width:100%;height:100%;object-fit:cover;" />
                                <?php else : ?>
                                    <?php echo wafb_svg( '#fff', 20 ); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong style="display:block;color:#fff;font-size:14px;"><?php echo $preview_name; ?></strong>
                                <span style="color:rgba(255,255,255,.85);font-size:11px;">● <?php _e( 'Online', 'whatsapp-floating-button' ); ?></span>
                            </div>
                        </div>
                        <div style="padding:14px;background:#f0f2f5;">
                            <div style="background:#fff;border-radius:0 10px 10px 10px;padding:10px 14px;
                                        font-size:13px;line-height:1.55;color:#333;
                                        box-shadow:0 1px 3px rgba(0,0,0,.08);">
                                <?php echo $preview_text; ?>
                            </div>
                        </div>
                        <div style="background:<?php echo $preview_color; ?>;padding:12px 16px;
                                    display:flex;align-items:center;justify-content:center;
                                    gap:8px;color:#fff;font-size:13px;font-weight:600;">
                            <?php echo wafb_svg( '#fff', 16 ); ?> <?php _e( 'Start Conversation', 'whatsapp-floating-button' ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ TAB: VISIBILITY ═══════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-visibility" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e( 'Page Visibility', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <?php
                            $vis_opts = array(
                                'all'     => __( 'Show on <strong>all</strong> pages', 'whatsapp-floating-button' ),
                                'include' => __( 'Show <strong>only</strong> on selected pages', 'whatsapp-floating-button' ),
                                'exclude' => __( '<strong>Hide</strong> on selected pages', 'whatsapp-floating-button' ),
                            );
                            foreach ( $vis_opts as $val => $lbl ) :
                            ?>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" class="wafb-vis-radio"
                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[visibility_type]"
                                       value="<?php echo esc_attr( $val ); ?>"
                                       <?php checked( $s['visibility_type'], $val ); ?>>
                                <?php echo $lbl; ?>
                            </label>
                            <?php endforeach; ?>

                            <div id="wafb-vis-pages"
                                 style="<?php echo $s['visibility_type'] === 'all' ? 'display:none;' : ''; ?>
                                        margin-top:12px;padding:14px;
                                        background:#f8f8f8;border:1px solid #ddd;border-radius:6px;max-width:480px;">
                                <p style="margin:0 0 10px;font-weight:600;font-size:13px;"><?php _e( 'Select Pages:', 'whatsapp-floating-button' ); ?></p>
                                <div style="max-height:200px;overflow-y:auto;">
                                <?php
                                $all_pages = get_pages();
                                $sel_pages = (array) $s['visibility_pages'];
                                foreach ( $all_pages as $page ) :
                                    $checked = in_array( $page->ID, $sel_pages, true ) ? 'checked' : '';
                                ?>
                                <label style="display:block;padding:4px 0;font-size:13px;">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[visibility_pages][]"
                                           value="<?php echo esc_attr( $page->ID ); ?>" <?php echo $checked; ?>>
                                    <?php echo esc_html( $page->post_title ); ?>
                                </label>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Business Hours', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <label style="display:block;margin-bottom:14px;">
                                <input type="checkbox" id="wafb_hours_enable"
                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[hours_enable]"
                                       value="1" <?php checked( $s['hours_enable'], '1' ); ?>>
                                <?php _e( 'Only show button during business hours', 'whatsapp-floating-button' ); ?>
                            </label>

                            <div id="wafb-hours-opts"
                                 style="<?php echo $s['hours_enable'] !== '1' ? 'display:none;' : ''; ?>
                                        padding:18px;background:#f8f8f8;
                                        border:1px solid #ddd;border-radius:6px;max-width:540px;">
                                <table style="border-spacing:0 10px;">
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;white-space:nowrap;"><?php _e( 'Active Days', 'whatsapp-floating-button' ); ?></td>
                                        <td>
                                            <?php
                                            $day_names = array(
                                                '0' => __( 'Sun', 'whatsapp-floating-button' ),
                                                '1' => __( 'Mon', 'whatsapp-floating-button' ),
                                                '2' => __( 'Tue', 'whatsapp-floating-button' ),
                                                '3' => __( 'Wed', 'whatsapp-floating-button' ),
                                                '4' => __( 'Thu', 'whatsapp-floating-button' ),
                                                '5' => __( 'Fri', 'whatsapp-floating-button' ),
                                                '6' => __( 'Sat', 'whatsapp-floating-button' ),
                                            );
                                            foreach ( $day_names as $num => $name ) :
                                                $chk = in_array( (string) $num, (array) $s['hours_days'], true ) ? 'checked' : '';
                                            ?>
                                            <label style="margin-right:12px;font-size:13px;">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[hours_days][]"
                                                       value="<?php echo esc_attr( $num ); ?>" <?php echo $chk; ?>>
                                                <?php echo esc_html( $name ); ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;"><?php _e( 'Time Range', 'whatsapp-floating-button' ); ?></td>
                                        <td>
                                            <input type="time" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[hours_start]"
                                                   value="<?php echo esc_attr( $s['hours_start'] ); ?>" />
                                            &nbsp;—&nbsp;
                                            <input type="time" name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[hours_end]"
                                                   value="<?php echo esc_attr( $s['hours_end'] ); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;"><?php _e( 'Timezone', 'whatsapp-floating-button' ); ?></td>
                                        <td>
                                            <select name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[hours_timezone]">
                                                <?php
                                                foreach ( DateTimeZone::listIdentifiers() as $tz ) :
                                                    $sel = selected( $s['hours_timezone'], $tz, false );
                                                ?>
                                                <option value="<?php echo esc_attr( $tz ); ?>" <?php echo $sel; ?>><?php echo esc_html( $tz ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:10px 0 0;font-size:12px;color:#888;">
                                    <?php printf(
                                        __( 'Current server time: <strong>%s</strong> (server timezone: %s)', 'whatsapp-floating-button' ),
                                        current_time( 'H:i' ),
                                        esc_html( wp_timezone_string() )
                                    ); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ══ TAB: ANALYTICS ════════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-analytics" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php _e( 'Click Tracking', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[tracking_enable]"
                                       value="1" <?php checked( $s['tracking_enable'], '1' ); ?>>
                                <?php _e( 'Track every click on the WhatsApp button', 'whatsapp-floating-button' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Clicks are stored in the WordPress database. No external service is used.', 'whatsapp-floating-button' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Total Clicks', 'whatsapp-floating-button' ); ?></th>
                        <td>
                            <div style="display:inline-flex;align-items:center;gap:20px;
                                        background:#f8f8f8;border:1px solid #ddd;border-radius:10px;
                                        padding:20px 28px;">
                                <div style="text-align:center;">
                                    <div style="font-size:48px;font-weight:800;color:<?php echo esc_attr( $s['button_color'] ); ?>;line-height:1;">
                                        <?php echo number_format( absint( $s['click_count'] ) ); ?>
                                    </div>
                                    <div style="font-size:12px;color:#888;margin-top:4px;"><?php _e( 'TOTAL CLICKS', 'whatsapp-floating-button' ); ?></div>
                                </div>
                                <div style="border-left:1px solid #ddd;height:60px;margin:0 4px;"></div>
                                <div>
                                    <p style="margin:0 0 8px;color:#555;font-size:13px;">
                                        <?php _e( 'Every time a visitor clicks the WhatsApp<br>button, this counter increments by 1.', 'whatsapp-floating-button' ); ?>
                                    </p>
                                    <label style="font-size:12px;color:#c00;">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( WAFB_OPTION_KEY ); ?>[reset_clicks]"
                                               value="1">
                                        <?php _e( '⚠️ Reset counter to 0 on save', 'whatsapp-floating-button' ); ?>
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Support Tab -->
            <div id="tab-support" class="wafb-tab-content" style="display:none;">
                <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:10px;padding:30px;text-align:center;max-width:600px;margin:20px auto;">
                    <div style="font-size:40px;margin-bottom:15px;">❓</div>
                    <h3 style="font-size:22px;margin:0 0 10px;"><?php _e( 'Need Help?', 'whatsapp-floating-button' ); ?></h3>
                    <p style="color:#666;font-size:14px;line-height:1.6;margin-bottom:25px;">
                        <?php _e( 'If you have any issues, feature requests, or need help setting up the plugin, feel free to visit our GitHub repository or check the documentation.', 'whatsapp-floating-button' ); ?>
                    </p>
                    <div style="display:flex;justify-content:center;gap:15px;">
                        <a href="https://github.com/adeshasur/WhatsApp-Floating-Button" target="_blank" class="button button-primary button-large">
                            <?php _e( 'View on GitHub', 'whatsapp-floating-button' ); ?>
                        </a>
                        <a href="https://adeshasur.github.io/WhatsApp-Floating-Button/" target="_blank" class="button button-large">
                            <?php _e( 'Live Demo & Docs', 'whatsapp-floating-button' ); ?>
                        </a>
                    </div>
                    <hr style="margin:30px 0;border:0;border-top:1px solid #eee;">
                    <h4 style="margin:0 0 10px;"><?php _e( 'Spread the Love', 'whatsapp-floating-button' ); ?></h4>
                    <p style="color:#888;font-size:13px;">
                        <?php _e( 'If you like this plugin, please consider giving it a star on GitHub. It helps us grow and keep building more cool features!', 'whatsapp-floating-button' ); ?>
                    </p>
                </div>
            </div>

            <div style="margin-top:30px;padding:20px;background:#fff;border:1px solid #ddd;border-radius:6px;display:flex;justify-content:flex-end;">
                <?php submit_button( __( 'Save All Changes', 'whatsapp-floating-button' ), 'primary large', 'submit', false ); ?>
            </div>
        </form>
    </div>

    <script>
    (function () {
        // ── Tab switching ──────────────────────────────────────────────────
        var tabs     = document.querySelectorAll('#wafb-tabs-nav .nav-tab');
        var contents = document.querySelectorAll('.wafb-tab-content');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                tabs.forEach(function (t)     { t.classList.remove('nav-tab-active'); });
                contents.forEach(function (c) { c.style.display = 'none'; });
                tab.classList.add('nav-tab-active');
                var target = document.getElementById(tab.dataset.tab);
                if (target) target.style.display = 'block';
            });
        });

        // ── Visibility type toggle ─────────────────────────────────────────
        var visRadios = document.querySelectorAll('.wafb-vis-radio');
        var visPages  = document.getElementById('wafb-vis-pages');
        visRadios.forEach(function (r) {
            r.addEventListener('change', function () {
                if (visPages) visPages.style.display = (this.value === 'all') ? 'none' : 'block';
            });
        });

        // ── Business hours toggle ──────────────────────────────────────────
        var hoursChk  = document.getElementById('wafb_hours_enable');
        var hoursOpts = document.getElementById('wafb-hours-opts');
        if (hoursChk && hoursOpts) {
            hoursChk.addEventListener('change', function () {
                hoursOpts.style.display = this.checked ? 'block' : 'none';
            });
        }
        // ── Live Preview logic ──────────────────────────────────────────────
        var liveContainer = document.getElementById('wafb-live-preview-container');
        var stickyPreview = document.getElementById('wafb-admin-preview-sticky');
        
        function updateLivePreview() {
            var color = document.getElementById('wafb_color').value;
            var label = document.getElementById('wafb_label').value;
            var size  = document.querySelector('input[name="wafb_settings[button_size]"]:checked').value;
            
            var px = (size === 'small') ? 40 : (size === 'medium' ? 50 : 60);
            var svgSize = (size === 'small') ? 18 : (size === 'medium' ? 22 : 28);
            
            var html = '<div style="display:flex; align-items:center; background:' + color + '; padding:10px ' + (label ? '15px' : '10px') + '; border-radius:50px; color:#fff; box-shadow:0 5px 15px rgba(0,0,0,0.1); cursor:default;">';
            html += '<svg viewBox="0 0 448 512" width="' + svgSize + '" height="' + svgSize + '" fill="#fff"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.1 0-65.6-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.1-16.4-14.6-27.4-32.7-30.6-38.2-3.2-5.6-.3-8.6 2.4-11.3 2.5-2.4 5.5-6.5 8.3-9.7 2.8-3.3 3.7-5.6 5.5-9.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 13.2 5.8 23.5 9.2 31.5 11.8 13.3 4.2 25.4 3.6 35 2.2 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>';
            if (label) html += '<span style="margin-left:10px; font-weight:700; font-size:14px;">' + label + '</span>';
            html += '</div>';
            
            if (liveContainer) liveContainer.innerHTML = html;
        }
        
        // Show sticky preview when on Appearance tab
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (stickyPreview) stickyPreview.style.display = (tab.dataset.tab === 'tab-appearance') ? 'block' : 'none';
            });
        });
        
        // Listeners for live updates
        ['wafb_color', 'wafb_label'].forEach(function(id){
            var el = document.getElementById(id);
            if(el) el.addEventListener('input', updateLivePreview);
        });
        document.querySelectorAll('input[name="wafb_settings[button_size]"]').forEach(function(r){
            r.addEventListener('change', updateLivePreview);
        });
        
        updateLivePreview(); // Init
    })();
    </script>
    <?php
}

// ─────────────────────────────────────────────
// BUSINESS HOURS CHECK (server-side)
// ─────────────────────────────────────────────
function wafb_is_within_business_hours( $s ) {
    if ( empty( $s['hours_enable'] ) || $s['hours_enable'] !== '1' ) {
        return true;
    }
    try {
        $tz   = new DateTimeZone( $s['hours_timezone'] ?: 'UTC' );
        $now  = new DateTime( 'now', $tz );
        $day  = $now->format( 'w' ); // 0 = Sunday … 6 = Saturday
        $time = $now->format( 'H:i' );

        if ( ! in_array( (string) $day, (array) $s['hours_days'], true ) ) {
            return false;
        }

        return ( $time >= ( $s['hours_start'] ?? '09:00' ) && $time <= ( $s['hours_end'] ?? '17:00' ) );
    } catch ( Exception $e ) {
        return true;
    }
}

// ─────────────────────────────────────────────
// PAGE VISIBILITY CHECK (server-side)
// ─────────────────────────────────────────────
function wafb_is_page_visible( $s ) {
    $type = $s['visibility_type'] ?? 'all';
    if ( $type === 'all' ) {
        return true;
    }
    $page_id  = (int) get_queried_object_id();
    $selected = array_map( 'intval', (array) ( $s['visibility_pages'] ?? array() ) );

    if ( $type === 'include' ) {
        return in_array( $page_id, $selected, true );
    }
    if ( $type === 'exclude' ) {
        return ! in_array( $page_id, $selected, true );
    }
    return true;
}

// ─────────────────────────────────────────────
// ENQUEUE FRONTEND ASSETS
// ─────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    $s = wafb_get_settings();
    if ( empty( $s['phone_number'] ) ) {
        return;
    }

    wp_enqueue_style(
        'wafb-style',
        WAFB_PLUGIN_URL . 'css/whatsapp-button.css',
        array(),
        WAFB_VERSION
    );

    wp_enqueue_script(
        'wafb-script',
        WAFB_PLUGIN_URL . 'js/whatsapp-button.js',
        array(),
        WAFB_VERSION,
        true
    );

    wp_localize_script( 'wafb-script', 'wafbData', array(
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'nonce'        => wp_create_nonce( 'wafb_click' ),
        'trackingOn'   => $s['tracking_enable'] === '1',
        'bubbleEnable' => $s['bubble_enable']   === '1',
        'bubbleDelay'  => (int) ( $s['bubble_delay'] ?? 3 ),
    ) );
} );

// ─────────────────────────────────────────────
// AJAX: CLICK TRACKING
// ─────────────────────────────────────────────
add_action( 'wp_ajax_wafb_track_click',        'wafb_handle_click' );
add_action( 'wp_ajax_nopriv_wafb_track_click', 'wafb_handle_click' );
function wafb_handle_click() {
    check_ajax_referer( 'wafb_click', 'nonce' );
    $s = wafb_get_settings();
    if ( $s['tracking_enable'] !== '1' ) {
        wp_die();
    }
    $s['click_count'] = absint( $s['click_count'] ) + 1;
    update_option( WAFB_OPTION_KEY, $s );
    wp_send_json_success( array( 'count' => $s['click_count'] ) );
}

// ─────────────────────────────────────────────
// RENDER BUTTON IN FOOTER
// ─────────────────────────────────────────────
add_action( 'wp_footer', 'wafb_render_button' );
function wafb_render_button() {
    $s = wafb_get_settings();

    if ( empty( $s['phone_number'] ) )          return;
    if ( ! wafb_is_within_business_hours( $s ) ) return;
    if ( ! wafb_is_page_visible( $s ) )          return;

    // Build WhatsApp URL.
    $phone = ltrim( $s['phone_number'], '+' );
    $msg   = $s['prefilled_message'] ?? '';
    $url   = 'https://wa.me/' . $phone;
    if ( ! empty( $msg ) ) {
        $url .= '?text=' . rawurlencode( $msg );
    }

    $pos     = $s['button_position']  ?? 'right';
    $mobile  = $s['show_on_mobile']   ?? '1';
    $color   = $s['button_color']     ?? '#25D366';
    $rgb     = wafb_hex_to_rgb( $color );
    $size    = $s['button_size']      ?? 'medium';
    $label   = $s['button_label']     ?? '';
    $anim    = $s['animation_style']  ?? 'pulse';
    $tooltip = $s['tooltip_text']     ?? 'Chat with us on WhatsApp!';

    $bubble_on     = $s['bubble_enable']  === '1';
    $bubble_name   = esc_html( $s['bubble_name']   ?? 'Support Team' );
    $bubble_text   = nl2br( esc_html( $s['bubble_text'] ?? '' ) );
    $bubble_avatar = esc_url( $s['bubble_avatar']  ?? '' );

    // Determine icon size by button size.
    $icon_sizes = array( 'small' => 22, 'medium' => 28, 'large' => 34 );
    $icon_size  = $icon_sizes[ $size ] ?? 28;

    // CSS classes for the button.
    $classes  = 'wafb-floating-btn';
    $classes .= ' wafb--' . esc_attr( $pos );
    $classes .= ' wafb--' . esc_attr( $size );
    $classes .= ' wafb--anim-' . esc_attr( $anim );
    if ( $mobile !== '1' ) {
        $classes .= ' wafb--hide-mobile';
    }
    if ( ! empty( $label ) ) {
        $classes .= ' wafb--has-label';
    }

    $inline = '--wafb-color:' . esc_attr( $color ) . ';--wafb-rgb:' . esc_attr( $rgb ) . ';';
    ?>

    <?php if ( $bubble_on ) : ?>
    <div class="wafb-bubble wafb-bubble--<?php echo esc_attr( $pos ); ?>"
         id="wafb-bubble"
         style="--wafb-color:<?php echo esc_attr( $color ); ?>;"
         role="dialog" aria-label="<?php esc_attr_e( 'WhatsApp Chat', 'whatsapp-floating-button' ); ?>" aria-live="polite">
        <button class="wafb-bubble__close" id="wafb-bubble-close" aria-label="<?php esc_attr_e( 'Close chat bubble', 'whatsapp-floating-button' ); ?>">&times;</button>
        <div class="wafb-bubble__header">
            <div class="wafb-bubble__avatar">
                <?php if ( ! empty( $bubble_avatar ) ) : ?>
                    <img src="<?php echo $bubble_avatar; ?>" alt="<?php echo $bubble_name; ?>" />
                <?php else : ?>
                    <?php echo wafb_svg( '#fff', 20 ); ?>
                <?php endif; ?>
            </div>
            <div>
                <strong class="wafb-bubble__name"><?php echo esc_html( $bubble_name ); ?></strong>
                <span class="wafb-bubble__status"><span class="wafb-status-dot"></span> <?php _e( 'Online', 'whatsapp-floating-button' ); ?></span>
            </div>
        </div>
        <div class="wafb-bubble__body">
            <div class="wafb-bubble__typing" id="wafb-typing">
                <span></span><span></span><span></span>
            </div>
            <div class="wafb-bubble__msg" id="wafb-msg" style="display:none;"><?php echo wp_kses_post( $bubble_text ); ?></div>
        </div>
        <a href="<?php echo esc_url( $url ); ?>"
           class="wafb-bubble__cta"
           target="_blank" rel="noopener noreferrer">
            <?php echo wafb_svg( '#fff', 16 ); ?> <?php _e( 'Start Conversation', 'whatsapp-floating-button' ); ?>
        </a>
    </div>
    <?php endif; ?>

    <a  id="wafb-floating-btn"
        href="<?php echo esc_url( $url ); ?>"
        class="<?php echo esc_attr( $classes ); ?>"
        style="<?php echo $inline; ?>"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="<?php echo esc_attr( $tooltip ); ?>"
        title="<?php echo esc_attr( $tooltip ); ?>">
        <?php echo wafb_svg( '#ffffff', $icon_size ); ?>
        <?php if ( ! empty( $label ) ) : ?>
            <span class="wafb-label"><?php echo esc_html( $label ); ?></span>
        <?php endif; ?>
        <span class="wafb-tooltip" role="tooltip"><?php echo esc_html( $tooltip ); ?></span>
    </a>

    <?php
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

function wafb_svg( $fill = '#ffffff', $size = 28 ) {
    $size = intval( $size );
    $fill = esc_attr( $fill );
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                 width="' . $size . '" height="' . $size . '" aria-hidden="true" focusable="false">
        <path fill="' . $fill . '"
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

function wafb_hex_to_rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    return "$r,$g,$b";
}
