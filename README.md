# WhatsApp Floating Button – WordPress Plugin

> **v2.0** — Advanced floating WhatsApp button with chat bubble, business hours, custom colours, pre-filled messages, click analytics, and page visibility rules.

## ✨ Features

| Feature | Description |
|---|---|
| 💬 **Pre-filled Message** | Auto-types a custom message when the user opens WhatsApp |
| 🗨️ **Chat Bubble Popup** | Timed popup with agent name, avatar, and a custom greeting |
| 🎨 **Custom Colour + Size** | Native colour picker + Small / Medium / Large button |
| 🏷️ **Pill Label** | Button expands on hover to reveal a text label |
| ⏰ **Business Hours** | Hides the button outside your configured hours & timezone |
| 👁️ **Page Visibility Rules** | Show or hide the button on specific pages |
| 📊 **Click Analytics** | Counts every click directly in the WordPress dashboard |
| 🎬 **Animation Styles** | Pulse Ring, Bounce, Shake, or None |
| 🔒 **Secure** | WordPress Settings API with full sanitisation & nonce-protected AJAX |
| ⚡ **Zero bloat** | CSS/JS only loaded when a phone number is saved |

## 📸 Settings Page Tabs

- **⚙️ General** — Phone number, pre-filled message, tooltip, position, mobile toggle
- **🎨 Appearance** — Colour, size, label text, animation
- **💬 Chat Bubble** — Enable, agent name + avatar, message, delay
- **👁️ Visibility** — All pages / include / exclude + Business hours
- **📊 Analytics** — Live click counter with reset option

## 🚀 Installation

1. Download or clone this repository.
2. Copy the `WhatsApp-Floating-Button` folder into your WordPress `wp-content/plugins/` directory.
3. Go to **WordPress Admin → Plugins** and activate **WhatsApp Floating Button**.
4. Navigate to **Settings → WhatsApp Button** and enter your phone number.

## ⚙️ Configuration

### General
- **WhatsApp Phone Number** — Include full country code, e.g. `+94771234567`
- **Pre-filled Message** — Automatically typed in the chat (optional)
- **Tooltip Text** — Hover label shown above the button

### Appearance
- **Button Colour** — Choose any colour with the native colour picker
- **Button Size** — Small (48 px) / Medium (60 px) / Large (72 px)
- **Label Text** — Expands the button into a pill shape on hover
- **Animation** — Pulse Ring / Bounce / Shake / None

### Chat Bubble
- **Enable** — Toggle on/off
- **Agent Name & Avatar** — Personalise the popup header
- **Bubble Message** — Greeting text (supports emojis & line breaks)
- **Show After** — Delay in seconds before the bubble appears

### Visibility
- **Page Rules** — Show on all pages, include specific pages, or exclude specific pages
- **Business Hours** — Set active days, time range, and timezone

### Analytics
- **Click Tracking** — Toggle on/off
- **Total Clicks** — Live counter in the dashboard
- **Reset Counter** — Check the reset box and save to clear the count

## 📁 File Structure

```
WhatsApp-Floating-Button/
├── whatsapp-button.php       ← Main plugin file
├── css/
│   └── whatsapp-button.css   ← Frontend styles
├── js/
│   └── whatsapp-button.js    ← Chat bubble & click tracking
└── README.md
```

## 📋 Changelog

### v2.0.0
- Added pre-filled message support
- Added chat bubble popup with avatar, agent name, and delay
- Added custom button colour (colour picker)
- Added Small / Medium / Large size options
- Added pill label expand-on-hover
- Added animation styles: Pulse Ring / Bounce / Shake / None
- Added page visibility rules (all / include / exclude)
- Added business hours gating (day, time range, timezone)
- Added click analytics with reset option
- Added tabbed admin settings page
- Added AJAX-based secure click tracking

### v1.0.0
- Initial release — basic floating button with settings page

## 📄 License

[GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html)