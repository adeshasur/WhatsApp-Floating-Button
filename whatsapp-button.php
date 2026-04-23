<?php
/**
 * Plugin Name:       WhatsApp Floating Button
 * Plugin URI:        https://github.com/adeshasur/WhatsApp-Floating-Button
 * Description:       Advanced floating WhatsApp button with chat bubble popup, business hours, custom colours, pre-filled messages, click analytics, and page visibility rules.
 * Version:           2.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatsapp-floating-button
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        'tooltip_text'      => 'Chat with us on WhatsApp!',
        'button_position'   => 'right',
        'show_on_mobile'    => '1',
        // Appearance
        'button_color'      => '#25D366',
        'button_size'       => 'medium',
        'button_label'      => '',
        'animation_style'   => 'pulse',
        // Chat Bubble
        'bubble_enable'     => '0',
        'bubble_name'       => 'Support Team',
        'bubble_text'       => "Hi there! 👋\nHow can I help you today?",
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
        'WhatsApp Floating Button Settings',
        'WhatsApp Button',
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

        <h1 style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;
                         width:40px;height:40px;border-radius:50%;
                         background:<?php echo esc_attr( $s['button_color'] ); ?>;
                         box-shadow:0 3px 10px rgba(0,0,0,.2);">
                <?php echo wafb_svg( '#fff', 22 ); ?>
            </span>
            WhatsApp Floating Button
            <span style="font-size:12px;font-weight:400;color:#999;align-self:flex-end;margin-bottom:4px;">v2.0</span>
        </h1>
        <p style="color:#666;margin-bottom:20px;">Configure your floating WhatsApp button from one place.</p>

        <?php settings_errors( 'wafb_messages' ); ?>

        <!-- TAB NAV -->
        <nav class="nav-tab-wrapper" id="wafb-tabs-nav" style="margin-bottom:0;">
            <a href="#tab-general"    class="nav-tab nav-tab-active" data-tab="tab-general">⚙️ &nbsp;General</a>
            <a href="#tab-appearance" class="nav-tab" data-tab="tab-appearance">🎨 &nbsp;Appearance</a>
            <a href="#tab-bubble"     class="nav-tab" data-tab="tab-bubble">💬 &nbsp;Chat Bubble</a>
            <a href="#tab-visibility" class="nav-tab" data-tab="tab-visibility">👁️ &nbsp;Visibility</a>
            <a href="#tab-analytics"  class="nav-tab" data-tab="tab-analytics">📊 &nbsp;Analytics</a>
        </nav>

        <form method="post" action="options.php" id="wafb-form"
              style="background:#fff;border:1px solid #c3c4c7;border-top:none;border-radius:0 0 6px 6px;padding:20px 24px;">
            <?php settings_fields( 'wafb_group' ); ?>

            <!-- ══ TAB: GENERAL ══════════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-general">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wafb_phone">WhatsApp Phone Number <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="tel" id="wafb_phone"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[phone_number]"
                                   value="<?php echo esc_attr( $s['phone_number'] ); ?>"
                                   class="regular-text" placeholder="+94771234567" />
                            <p class="description">Include country code. No spaces or dashes. e.g. <code>+94771234567</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_message">Pre-filled Message</label></th>
                        <td>
                            <textarea id="wafb_message"
                                      name="<?php echo WAFB_OPTION_KEY; ?>[prefilled_message]"
                                      rows="3" class="large-text"
                                      placeholder="Hi! I'm interested in..."><?php echo esc_textarea( $s['prefilled_message'] ); ?></textarea>
                            <p class="description">Automatically typed into WhatsApp when the user opens the chat. Leave empty to open a blank chat.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_tooltip">Hover Tooltip Text</label></th>
                        <td>
                            <input type="text" id="wafb_tooltip"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[tooltip_text]"
                                   value="<?php echo esc_attr( $s['tooltip_text'] ); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Button Position</th>
                        <td>
                            <fieldset>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo WAFB_OPTION_KEY; ?>[button_position]"
                                           value="right" <?php checked( $s['button_position'], 'right' ); ?>>
                                    Bottom Right
                                </label>
                                <label>
                                    <input type="radio" name="<?php echo WAFB_OPTION_KEY; ?>[button_position]"
                                           value="left" <?php checked( $s['button_position'], 'left' ); ?>>
                                    Bottom Left
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Show on Mobile</th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo WAFB_OPTION_KEY; ?>[show_on_mobile]"
                                       value="1" <?php checked( $s['show_on_mobile'], '1' ); ?>>
                                Display the button on mobile devices (&lt; 768px)
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ══ TAB: APPEARANCE ═══════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-appearance" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wafb_color">Button Colour</label></th>
                        <td style="display:flex;align-items:center;gap:12px;padding-top:12px;">
                            <input type="color" id="wafb_color"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[button_color]"
                                   value="<?php echo esc_attr( $s['button_color'] ); ?>"
                                   style="width:54px;height:38px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid #ddd;" />
                            <span style="color:#666;font-size:13px;">Default: <code>#25D366</code> (WhatsApp Green)</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Button Size</th>
                        <td>
                            <fieldset>
                                <?php
                                $sizes = array( 'small' => 'Small (48 px)', 'medium' => 'Medium (60 px)', 'large' => 'Large (72 px)' );
                                foreach ( $sizes as $val => $lbl ) :
                                ?>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo WAFB_OPTION_KEY; ?>[button_size]"
                                           value="<?php echo $val; ?>" <?php checked( $s['button_size'], $val ); ?>>
                                    <?php echo $lbl; ?>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_label">Button Label Text</label></th>
                        <td>
                            <input type="text" id="wafb_label"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[button_label]"
                                   value="<?php echo esc_attr( $s['button_label'] ); ?>"
                                   class="regular-text" placeholder="e.g. Chat with us" />
                            <p class="description">Optional. The button expands into a pill shape on hover to reveal this text. Leave empty for icon-only.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Animation Style</th>
                        <td>
                            <fieldset>
                                <?php
                                $anims = array(
                                    'pulse'  => '🔴 Pulse Ring',
                                    'bounce' => '⬆️ Bounce',
                                    'shake'  => '📳 Shake',
                                    'none'   => '⏹️ None',
                                );
                                foreach ( $anims as $val => $lbl ) :
                                ?>
                                <label style="margin-right:20px;">
                                    <input type="radio" name="<?php echo WAFB_OPTION_KEY; ?>[animation_style]"
                                           value="<?php echo $val; ?>" <?php checked( $s['animation_style'], $val ); ?>>
                                    <?php echo $lbl; ?>
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
                        <th scope="row">Enable Chat Bubble</th>
                        <td>
                            <label>
                                <input type="checkbox" id="wafb_bubble_enable"
                                       name="<?php echo WAFB_OPTION_KEY; ?>[bubble_enable]"
                                       value="1" <?php checked( $s['bubble_enable'], '1' ); ?>>
                                Show a chat popup bubble above the button after a delay
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_name">Agent / Team Name</label></th>
                        <td>
                            <input type="text" id="wafb_bubble_name"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[bubble_name]"
                                   value="<?php echo esc_attr( $s['bubble_name'] ); ?>"
                                   class="regular-text" placeholder="Support Team" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_text">Bubble Message</label></th>
                        <td>
                            <textarea id="wafb_bubble_text"
                                      name="<?php echo WAFB_OPTION_KEY; ?>[bubble_text]"
                                      rows="3" class="large-text"><?php echo esc_textarea( $s['bubble_text'] ); ?></textarea>
                            <p class="description">Supports emojis. Line breaks are preserved.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_avatar">Agent Avatar URL</label></th>
                        <td>
                            <input type="url" id="wafb_bubble_avatar"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[bubble_avatar]"
                                   value="<?php echo esc_attr( $s['bubble_avatar'] ); ?>"
                                   class="large-text" placeholder="https://example.com/avatar.jpg" />
                            <p class="description">Optional profile photo URL. Leave empty to use the WhatsApp icon.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wafb_bubble_delay">Show After</label></th>
                        <td style="display:flex;align-items:center;gap:10px;padding-top:12px;">
                            <input type="number" id="wafb_bubble_delay"
                                   name="<?php echo WAFB_OPTION_KEY; ?>[bubble_delay]"
                                   value="<?php echo absint( $s['bubble_delay'] ); ?>"
                                   min="0" max="120" style="width:80px;" />
                            <span style="color:#555;">seconds after page load</span>
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
                <h3 style="margin-bottom:8px;">Live Preview</h3>
                <div style="position:relative;width:320px;margin:0 auto;">
                    <div class="wafb-bubble-preview" style="
                        width:300px;background:#fff;border-radius:16px;
                        box-shadow:0 8px 32px rgba(0,0,0,.15);overflow:hidden;
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
                                <span style="color:rgba(255,255,255,.85);font-size:11px;">● Online</span>
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
                            <?php echo wafb_svg( '#fff', 16 ); ?> Start Conversation
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ TAB: VISIBILITY ═══════════════════════════════════════ -->
            <div class="wafb-tab-content" id="tab-visibility" style="display:none;">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Page Visibility</th>
                        <td>
                            <?php
                            $vis_opts = array(
                                'all'     => 'Show on <strong>all</strong> pages',
                                'include' => 'Show <strong>only</strong> on selected pages',
                                'exclude' => '<strong>Hide</strong> on selected pages',
                            );
                            foreach ( $vis_opts as $val => $lbl ) :
                            ?>
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" class="wafb-vis-radio"
                                       name="<?php echo WAFB_OPTION_KEY; ?>[visibility_type]"
                                       value="<?php echo $val; ?>"
                                       <?php checked( $s['visibility_type'], $val ); ?>>
                                <?php echo $lbl; ?>
                            </label>
                            <?php endforeach; ?>

                            <div id="wafb-vis-pages"
                                 style="<?php echo $s['visibility_type'] === 'all' ? 'display:none;' : ''; ?>
                                        margin-top:12px;padding:14px;
                                        background:#f8f8f8;border:1px solid #ddd;border-radius:6px;max-width:480px;">
                                <p style="margin:0 0 10px;font-weight:600;font-size:13px;">Select Pages:</p>
                                <div style="max-height:200px;overflow-y:auto;">
                                <?php
                                $all_pages = get_pages();
                                $sel_pages = (array) $s['visibility_pages'];
                                foreach ( $all_pages as $page ) :
                                    $checked = in_array( $page->ID, $sel_pages, true ) ? 'checked' : '';
                                ?>
                                <label style="display:block;padding:4px 0;font-size:13px;">
                                    <input type="checkbox"
                                           name="<?php echo WAFB_OPTION_KEY; ?>[visibility_pages][]"
                                           value="<?php echo $page->ID; ?>" <?php echo $checked; ?>>
                                    <?php echo esc_html( $page->post_title ); ?>
                                </label>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Business Hours</th>
                        <td>
                            <label style="display:block;margin-bottom:14px;">
                                <input type="checkbox" id="wafb_hours_enable"
                                       name="<?php echo WAFB_OPTION_KEY; ?>[hours_enable]"
                                       value="1" <?php checked( $s['hours_enable'], '1' ); ?>>
                                Only show button during business hours
                            </label>

                            <div id="wafb-hours-opts"
                                 style="<?php echo $s['hours_enable'] !== '1' ? 'display:none;' : ''; ?>
                                        padding:18px;background:#f8f8f8;
                                        border:1px solid #ddd;border-radius:6px;max-width:540px;">
                                <table style="border-spacing:0 10px;">
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;white-space:nowrap;">Active Days</td>
                                        <td>
                                            <?php
                                            $day_names = array( '0'=>'Sun','1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat' );
                                            foreach ( $day_names as $num => $name ) :
                                                $chk = in_array( $num, (array) $s['hours_days'], true ) ? 'checked' : '';
                                            ?>
                                            <label style="margin-right:12px;font-size:13px;">
                                                <input type="checkbox"
                                                       name="<?php echo WAFB_OPTION_KEY; ?>[hours_days][]"
                                                       value="<?php echo $num; ?>" <?php echo $chk; ?>>
                                                <?php echo $name; ?>
                                            </label>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;">Time Range</td>
                                        <td>
                                            <input type="time" name="<?php echo WAFB_OPTION_KEY; ?>[hours_start]"
                                                   value="<?php echo esc_attr( $s['hours_start'] ); ?>" />
                                            &nbsp;—&nbsp;
                                            <input type="time" name="<?php echo WAFB_OPTION_KEY; ?>[hours_end]"
                                                   value="<?php echo esc_attr( $s['hours_end'] ); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-right:16px;font-weight:600;font-size:13px;">Timezone</td>
                                        <td>
                                            <select name="<?php echo WAFB_OPTION_KEY; ?>[hours_timezone]">
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
                                    Current server time: <strong><?php echo current_time( 'H:i' ); ?></strong>
                                    (server timezone: <?php echo esc_html( wp_timezone_string() ); ?>)
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
                        <th scope="row">Click Tracking</th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo WAFB_OPTION_KEY; ?>[tracking_enable]"
                                       value="1" <?php checked( $s['tracking_enable'], '1' ); ?>>
                                Track every click on the WhatsApp button
                            </label>
                            <p class="description">Clicks are stored in the WordPress database. No external service is used.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Total Clicks</th>
                        <td>
                            <div style="display:inline-flex;align-items:center;gap:20px;
                                        background:#f8f8f8;border:1px solid #ddd;border-radius:10px;
                                        padding:20px 28px;">
                                <div style="text-align:center;">
                                    <div style="font-size:48px;font-weight:800;color:<?php echo esc_attr( $s['button_color'] ); ?>;line-height:1;">
                                        <?php echo number_format( $s['click_count'] ); ?>
                                    </div>
                                    <div style="font-size:12px;color:#888;margin-top:4px;">TOTAL CLICKS</div>
                                </div>
                                <div style="border-left:1px solid #ddd;height:60px;margin:0 4px;"></div>
                                <div>
                                    <p style="margin:0 0 8px;color:#555;font-size:13px;">
                                        Every time a visitor clicks the WhatsApp<br>button, this counter increments by 1.
                                    </p>
                                    <label style="font-size:12px;color:#c00;">
                                        <input type="checkbox"
                                               name="<?php echo WAFB_OPTION_KEY; ?>[reset_clicks]"
                                               value="1">
                                        ⚠️ Reset counter to 0 on save
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button( 'Save Settings' ); ?>
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
         role="dialog" aria-label="WhatsApp Chat" aria-live="polite">
        <button class="wafb-bubble__close" id="wafb-bubble-close" aria-label="Close chat bubble">&times;</button>
        <div class="wafb-bubble__header">
            <div class="wafb-bubble__avatar">
                <?php if ( ! empty( $bubble_avatar ) ) : ?>
                    <img src="<?php echo $bubble_avatar; ?>" alt="<?php echo $bubble_name; ?>" />
                <?php else : ?>
                    <?php echo wafb_svg( '#fff', 20 ); ?>
                <?php endif; ?>
            </div>
            <div>
                <strong class="wafb-bubble__name"><?php echo $bubble_name; ?></strong>
                <span class="wafb-bubble__status">● Online</span>
            </div>
        </div>
        <div class="wafb-bubble__body">
            <div class="wafb-bubble__msg"><?php echo $bubble_text; ?></div>
        </div>
        <a href="<?php echo esc_url( $url ); ?>"
           class="wafb-bubble__cta"
           target="_blank" rel="noopener noreferrer">
            <?php echo wafb_svg( '#fff', 16 ); ?> Start Conversation
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
