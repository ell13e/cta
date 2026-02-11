# Facebook Conversions API Implementation

**Status:** ✅ Complete  
**Reference:** [Facebook Conversions API Documentation](https://developers.facebook.com/docs/marketing-api/conversions-api)

---

## Overview

Server-side event tracking for Facebook/Meta Pixel. Sends conversion events directly from your server to Facebook, improving reliability and accuracy especially with iOS 14.5+ privacy changes and ad blockers.

**Benefits:**
- More reliable tracking (not blocked by ad blockers)
- Better accuracy with iOS 14.5+ privacy changes
- Event deduplication (matches client and server events)
- GDPR-compliant (user data is hashed)

---

## Configuration

**Location:** WordPress Admin → Settings → API Keys

**Required Settings:**
1. **Pixel ID** - Your Facebook Pixel ID (found in Events Manager)
2. **Access Token** - Conversions API access token (found in Events Manager → Data Sources → Settings → Conversions API)
3. **Test Event Code** (Optional) - For testing in Events Manager → Test Events

**Enable/Disable:**
- Checkbox to enable/disable Conversions API tracking
- Client-side Pixel code loads automatically when Pixel ID is set

---

## Events Tracked

### 1. PageView
- **When:** Every page load
- **Automatic:** Yes
- **Data Sent:** Page URL, user data (hashed)

### 2. Lead
- **When:** Form submission saved (contact forms, callback requests)
- **Automatic:** Yes
- **Data Sent:** User email, phone, name (hashed), form type
- **Excluded:** Newsletter subscriptions (not leads)

### 3. Purchase
- **When:** Course booking completed
- **Automatic:** Yes
- **Data Sent:** Booking ID, total price, currency (GBP), user data (hashed)
- **Price Calculation:** Course price × number of delegates (with discount applied)

### 4. ViewContent (Available)
- **Function:** `cta_track_facebook_view_content($course_id, $course_title)`
- **Use:** Track when users view course pages
- **Manual:** Call this function when needed

### 5. InitiateCheckout (Available)
- **Function:** `cta_track_facebook_initiate_checkout($course_id, $value)`
- **Use:** Track when users start booking process
- **Manual:** Call this function when needed

---

## Implementation Details

### Files Created/Modified

1. **`inc/facebook-conversions-api.php`** (New)
   - Core Conversions API functions
   - Event tracking functions
   - User data hashing (SHA-256)
   - Automatic event hooks

2. **`inc/api-keys-settings.php`** (Modified)
   - Added Conversions API settings fields
   - Access token input (password field)
   - Test event code input
   - Enable/disable checkbox

3. **`footer.php`** (Modified)
   - Added Facebook Pixel client-side code
   - Loads automatically when Pixel ID is configured

4. **`src/Controllers/CourseBookingController.php`** (Modified)
   - Added hook `cta_course_booking_saved` after booking is saved
   - Triggers Purchase event tracking

5. **`functions.php`** (Modified)
   - Includes `facebook-conversions-api.php`

### User Data Privacy

All sensitive user data is hashed using SHA-256 before sending to Facebook:
- Email addresses
- Phone numbers
- Names (first_name, last_name)
- Location data (city, state, zip, country)

**Required for deduplication:**
- Client IP address
- User agent

---

## Event Deduplication

Facebook automatically deduplicates events sent from both client (Pixel) and server (Conversions API) using:
- Event name
- Event time (within 48 hours)
- User data (hashed email, phone, etc.)
- Client IP and user agent

This ensures you don't count the same conversion twice.

---

## Testing

### Test Event Code

1. Go to Events Manager → Test Events
2. Copy your Test Event Code
3. Enter it in Settings → API Keys → Test Event Code
4. All events will be marked as test events
5. View them in Events Manager → Test Events

### Verify Events

1. Go to Events Manager → Data Sources → Your Pixel
2. Check "Events" tab
3. Look for server events (marked with server icon)
4. Verify event data matches expected values

---

## Manual Event Tracking

You can manually trigger events using these functions:

```php
// Track Lead
cta_track_facebook_lead($form_id, [
    'email' => 'user@example.com',
    'phone' => '+44 1234 567890',
    'first_name' => 'John',
    'last_name' => 'Doe',
]);

// Track Purchase
cta_track_facebook_purchase($booking_id, 150.00, 'GBP', [
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
]);

// Track ViewContent
cta_track_facebook_view_content($course_id, 'Course Title');

// Track InitiateCheckout
cta_track_facebook_initiate_checkout($course_id, 150.00);
```

---

## Troubleshooting

### Events Not Appearing

1. **Check Configuration:**
   - Pixel ID is set
   - Access Token is valid
   - Conversions API is enabled

2. **Check Test Event Code:**
   - If set, events go to Test Events (not production)
   - Remove test code to see production events

3. **Check Server Logs:**
   - Enable `WP_DEBUG` in `wp-config.php`
   - Check for error messages in debug.log

4. **Verify Access Token:**
   - Token must have `ads_management` permission
   - Token must be for the correct Pixel ID
   - Token must not be expired

### Common Issues

**"Access token is invalid"**
- Regenerate access token in Events Manager
- Ensure token has correct permissions

**"Pixel ID not found"**
- Verify Pixel ID matches your Events Manager
- Check for typos in settings

**"Events not deduplicating"**
- Ensure client-side Pixel code is loading
- Verify user data matches between client and server events
- Check event time is within 48 hours

---

## Best Practices

1. **Always send both client and server events** for best deduplication
2. **Hash all user data** (automatically handled)
3. **Include IP and user agent** (automatically handled)
4. **Use test event code during development**
5. **Monitor Events Manager** for dropped events
6. **Keep access token secure** (stored in WordPress options, not in code)

---

## GDPR Compliance

- User data is hashed before sending (SHA-256)
- Only send data users have consented to (marketing consent)
- Respect cookie consent settings
- Data is sent to Facebook (may be outside UK/EEA)
- Documented in Privacy Policy and Cookie Policy

---

## Next Steps

1. **Configure Settings:**
   - Add Pixel ID
   - Add Access Token
   - Enable Conversions API

2. **Test Setup:**
   - Add Test Event Code
   - Submit a test form
   - Check Events Manager → Test Events

3. **Verify Production:**
   - Remove Test Event Code
   - Submit real form/booking
   - Check Events Manager → Events

4. **Monitor Performance:**
   - Check Events Manager regularly
   - Monitor for dropped events
   - Verify deduplication is working

---

---

## Conversion Leads Integration (CRM)

**Reference:** [Conversion Leads Integration Documentation](https://developers.facebook.com/docs/marketing-api/conversions-api/conversion-leads-integration)

### Overview

Conversion Leads Integration allows you to send offline conversion events from your CRM (WordPress form submissions) back to Facebook when leads progress through your sales funnel. This is specifically designed for Facebook Lead Ads optimization.

**Use Case:**
- You run Facebook/Instagram Lead Ads campaigns
- Leads come into your WordPress site as form submissions
- You track lead progression (Qualified → Booked → Paid → Completed)
- Facebook uses this data to optimize for higher-quality leads

### Requirements

1. **Facebook Lead Ads** - You must be using Lead Ads (Instant Forms)
2. **Meta Lead ID** - 15-17 digit Lead ID from Facebook (stored in form submission)
3. **Regular Uploads** - Send conversion events at least once per day
4. **Minimum Volume** - At least 200 leads per month recommended

### Required Parameters

The Conversion Leads Integration requires specific parameters in the payload:

**Server Event Parameters:**
- `event_name` - Name of the conversion event (e.g., "Lead", "Appointment Set", "Sale Completed")
- `event_time` - UNIX timestamp when the lead changed to a new stage
- `action_source` - Must be `"system_generated"` (required for CRM events)

**Custom Event Parameters:**
- `custom_data.event_source` - Must be `"crm"` (required)
- `custom_data.lead_event_source` - Name of your CRM (e.g., "WordPress", "HubSpot", "Salesforce")
- `custom_data.value` - Optional: Event value (e.g., course price)
- `custom_data.currency` - Optional: Currency code (e.g., "GBP")

**Customer Information Parameters (Priority Order):**
1. **Lead ID** (Highest Priority) - 15-17 digit Meta Lead ID
2. **Hashed Email** (`em[]`) - Array of SHA-256 hashed email addresses
3. **Hashed Phone** (`ph[]`) - Array of SHA-256 hashed phone numbers
4. **Other Contact Info** - Optional: Hashed first name, last name, city, etc.

**Configuration:**
- **CRM Name** - Set in WordPress Admin → Settings → API Keys → CRM Name (default: "WordPress")
- **API Version** - Using v24.0 (latest)
- **Endpoint** - `https://graph.facebook.com/v24.0/{DATASET_ID}/events?access_token={ACCESS_TOKEN}`

### How It Works

1. **Lead comes from Facebook Lead Ads**
   - Lead ID is automatically captured (if integrated) or manually entered
   - Stored in form submission as `_submission_meta_lead_id`

2. **Lead progresses through funnel**
   - Status changes in WordPress admin (e.g., "Booked", "Paid")
   - Automatic conversion event sent to Facebook

3. **Facebook optimizes**
   - Uses conversion data to find similar high-quality leads
   - Improves ad targeting and reduces cost per lead

### Status to Event Mapping

| Followup Status | Conversion Event | Description |
|----------------|-----------------|-------------|
| `in-progress` | `Lead` | Lead is qualified/interested |
| `booked` | `Appointment Set` | Appointment/booking made |
| `paid` | `Sale Completed` | Payment received |
| `completed` | `Sale Completed` | Course attended (completed sale) |

### Manual Conversion Events

You can manually send conversion events from the form submission admin:

1. Go to **Form Submissions** → Edit submission
2. Enter **Meta Lead ID** (15-17 digits)
3. Select conversion event from dropdown
4. Click **Send Conversion Event**

**Available Events:**
- Lead (Qualified)
- Appointment Set
- Sale Completed
- Purchase

### Automatic Tracking

Conversion events are automatically sent when:
- Form submission status changes to a mapped status
- Meta Lead ID is present in the submission
- Conversions API is enabled

**Status Changes That Trigger Events:**
- `new` → `in-progress` → Sends "Lead" event
- `in-progress` → `booked` → Sends "Appointment Set" event
- `booked` → `paid` → Sends "Sale Completed" event
- `paid` → `completed` → Sends "Sale Completed" event

### Storing Meta Lead ID

**Option 1: Manual Entry**
- Edit form submission in WordPress admin
- Enter Lead ID in "Meta Lead ID" field
- Save

**Option 2: From Lead Ads Integration**
- If you have Lead Ads → CRM integration set up
- Lead ID can be automatically captured in form data
- Store as `meta_lead_id` in form submission data

**Option 3: From Form Field**
- Add hidden field to forms: `<input type="hidden" name="meta_lead_id" value="...">`
- Lead ID will be saved automatically

### Payload Structure

The implementation follows Meta's Conversion Leads Integration specification:

```json
{
  "data": [
    {
      "action_source": "system_generated",
      "custom_data": {
        "event_source": "crm",
        "lead_event_source": "WordPress"
      },
      "event_name": "Lead",
      "event_time": 1673035686,
      "user_data": {
        "em": ["7b17fb0bd173f625b58636fb796407c22b3d16fc78302d79f0fd30c2fc2fc068"],
        "lead_id": 1234567890123456,
        "ph": ["6069d14bf122fdfd931dc7beb58e5dfbba395b1faf05bdcd42d12358d63d8599"]
      }
    }
  ]
}
```

**Key Implementation Details:**
- Email and phone are hashed using SHA-256 and formatted as arrays (`em[]`, `ph[]`)
- Phone numbers are cleaned (non-numeric characters removed) before hashing
- All customer information is hashed for privacy compliance
- `action_source` is set to `"system_generated"` (required for CRM events)
- `event_source` is set to `"crm"` in custom_data (required)
- `lead_event_source` is configurable in settings (default: "WordPress")

### Best Practices

1. **Always include Lead ID** - Events without Lead ID won't be used for optimization
2. **Send events promptly** - Within 28 days of lead generation for best results
3. **Use consistent event names** - Match Facebook's standard events
4. **Include value data** - Add course price to "Sale Completed" events
5. **Test first** - Use Test Event Code to verify events are received
6. **Configure CRM Name** - Set your CRM name in settings (appears in Events Manager)
7. **Send daily** - Ensure integration uploads data at least once per day

### Verification

1. Go to **Events Manager** → Data Sources → Your Pixel
2. Check **Events** tab
3. Look for offline conversion events (marked with server icon)
4. Verify Lead ID matches and events are deduplicated

### Troubleshooting

**"Meta Lead ID not found"**
- Enter Lead ID in form submission admin
- Verify Lead ID is 15-17 digits
- Check Lead ID is saved correctly

**"Events not appearing"**
- Verify Conversions API is enabled
- Check Access Token is valid
- Ensure Lead ID format is correct (15-17 digits)

**"Events not optimizing"**
- Need minimum 200 leads per month
- Events must be sent within 28 days
- Conversion rate should be 1-40%

---

**Implementation Date:** 2025-01-29  
**Last Updated:** 2025-01-29 (Updated to match Meta's Conversion Leads Integration specification)  
**Status:** ✅ Ready for configuration and testing

---

## Facebook Lead Ads Webhook Integration (Meta → WordPress)

**Reference:** [Lead Ads Webhooks Documentation](https://developers.facebook.com/docs/graph-api/webhooks/reference/leadgen)

### Overview

Receive leads from Facebook Lead Ads automatically and import them as form submissions in WordPress. This is the **opposite direction** from Conversion Leads Integration - leads flow **FROM Meta TO your CRM**.

**Use Case:**
- You run Facebook/Instagram Lead Ads campaigns
- Users submit leads through Facebook's Instant Forms
- Leads are automatically imported into WordPress as form submissions
- You can then track their progression and send conversion events back to Meta

### How It Works

1. **Lead submitted on Facebook**
   - User clicks your Lead Ad and fills out Facebook's Instant Form
   - Facebook sends webhook notification to your WordPress site

2. **Webhook receives lead data**
   - WordPress receives POST request with lead information
   - Fetches full lead details from Facebook Graph API
   - Fetches ad set information to check filtering and parse course/date

3. **Filtering by Ad Set Name**
   - **Only imports leads where ad set name contains "cta"** (case-insensitive)
   - Leads from other ad sets are skipped automatically
   - This ensures only relevant leads are imported

4. **Parse Course and Date from Ad Set Name**
   - Ad set name format: `cta_COURSENAME_DATE` (e.g., `cta_EPFA_27.01.2026`)
   - Extracts course name/acronym (e.g., "EPFA")
   - Extracts date (e.g., "27.01.2026")
   - Automatically finds matching course in WordPress by searching course titles
   - Links course to the form submission

5. **Creates form submission**
   - Creates new form submission post in WordPress
   - Stores Meta Lead ID for conversion tracking
   - Stores course ID and event date (if parsed from ad set name)
   - Categorizes as "facebook-lead" form type

6. **Ready for follow-up**
   - Lead appears in Form Submissions admin
   - Course and date are pre-filled from ad set name
   - You can track status changes (new → in-progress → booked → paid)
   - Conversion events automatically sent back to Meta (see Conversion Leads Integration above)

### Setup Instructions

1. **Configure in WordPress:**
   - Go to **Settings → API Keys**
   - Scroll to **Facebook Lead Ads Webhook** section
   - Enable the webhook
   - Copy the **Webhook URL** and **Verify Token**

2. **Configure in Facebook:**
   - Go to [Meta Business Suite](https://business.facebook.com) → Lead Ads → Settings → Integrations
   - Click **Add Integration** → **Webhook**
   - Enter the **Webhook URL** from WordPress
   - Enter the **Verify Token** from WordPress
   - Subscribe to **"leadgen"** events
   - Save and verify

3. **Test:**
   - Submit a test lead through a Facebook Lead Ad
   - Check WordPress **Form Submissions** admin
   - Verify lead was imported correctly

### Data Received from Facebook

When a lead is submitted, Facebook sends:

**Webhook Payload:**
```json
{
  "entry": [{
    "changes": [{
      "field": "leadgen",
      "value": {
        "leadgen_id": "1234567890123456",
        "page_id": "987654321",
        "form_id": "111222333",
        "created_time": 1673035686
      }
    }]
  }]
}
```

**Full Lead Details (fetched from Graph API):**
```json
{
  "id": "1234567890123456",
  "created_time": "2024-01-15T10:30:00+0000",
  "field_data": [
    {
      "name": "first_name",
      "values": ["John"]
    },
    {
      "name": "last_name",
      "values": ["Doe"]
    },
    {
      "name": "email",
      "values": ["john.doe@example.com"]
    },
    {
      "name": "phone_number",
      "values": ["+44 1234 567890"]
    },
    {
      "name": "message",
      "values": ["I'm interested in your courses"]
    }
  ]
}
```

### Field Mapping

Facebook Lead Ads fields are automatically mapped to WordPress form submission fields:

| Facebook Field | WordPress Field | Notes |
|----------------|-----------------|-------|
| `first_name` | `name` | Combined with last_name |
| `last_name` | `name` | Combined with first_name |
| `full_name` | `name` | Used if first/last not available |
| `email` / `email_address` | `email` | Primary email |
| `phone_number` / `phone` / `mobile_phone` | `phone` | Primary phone |
| `message` / `comments` | `message` | Additional comments |
| Other fields | `form_data` | Stored in custom form data |

**Additional Metadata Stored:**
- `_submission_meta_lead_id` - Meta Lead ID (15-17 digits)
- `_submission_facebook_page_id` - Facebook Page ID
- `_submission_facebook_form_id` - Lead Form ID
- `_submission_facebook_created_time` - Lead creation timestamp

### Ad Set Name Parsing

The webhook automatically parses course name and date from the ad set name:

**Format:** `cta_COURSENAME_DATE`

**Examples:**
- `cta_EPFA_27.01.2026` → Course: "EPFA", Date: "27.01.2026"
- `cta_Safeguarding_L2_15.03.2025` → Course: "Safeguarding_L2", Date: "15.03.2025"
- `cta_First_Aid_10.12.2024` → Course: "First_Aid", Date: "10.12.2024"

**How it works:**
1. Removes `cta_` prefix (case-insensitive)
2. Splits remaining name by underscores
3. Last part is treated as the date (DD.MM.YYYY format)
4. Remaining parts are combined as the course name/acronym
5. Searches WordPress courses by title/acronym to find matching course
6. Links course ID and date to the form submission

**Course Matching:**
- Searches course titles for the parsed course name/acronym
- Case-insensitive matching
- Matches if course name appears anywhere in the title
- If no match found, course is not linked (but submission is still created)

**Filtering:**
- **Only leads from ad sets with "cta" in the name are imported**
- Leads from other ad sets are automatically skipped
- This ensures only relevant leads are processed

### Webhook Endpoint

**URL:** `https://yoursite.com/wp-json/cta/v1/facebook-lead-ads`

**Methods:**
- **GET** - Webhook verification (Facebook sends challenge)
- **POST** - Lead data from Facebook

**Security:**
- Verify token required for GET requests
- Webhook can be enabled/disabled in settings
- Access token required to fetch full lead details from Graph API

### Configuration Options

**Settings Location:** WordPress Admin → Settings → API Keys → Facebook Lead Ads Webhook

**Options:**
- **Enable Webhook** - Turn webhook on/off
- **Verify Token** - Token for webhook verification (auto-generated)
- **Form Type** - Form type slug for imported leads (default: "facebook-lead")

### Troubleshooting

**"Webhook verification failed"**
- Check verify token matches in both Facebook and WordPress
- Ensure webhook URL is accessible (not behind firewall)
- Verify webhook is enabled in WordPress settings

**"Leads not appearing"**
- Check webhook is enabled
- Verify Facebook Access Token is configured (needed to fetch lead details)
- Check WordPress error logs for API errors
- Verify webhook is subscribed to "leadgen" events in Facebook

**"Missing lead data"**
- Ensure Facebook Access Token has `leads_retrieval` permission
- Check Graph API response in error logs
- Verify lead form fields match expected names

### Best Practices

1. **Test first** - Submit a test lead before going live
2. **Monitor imports** - Check Form Submissions regularly
3. **Keep Access Token secure** - Store in WordPress settings, not in code
4. **Handle errors gracefully** - Webhook returns 200 OK even if processing fails (to avoid Facebook retries)
5. **Track Lead IDs** - Always store Meta Lead ID for conversion tracking

---

**Implementation Date:** 2025-01-29  
**Status:** ✅ Ready for configuration and testing

### Recent Updates

**2025-01-29:**
- ✅ Updated to Meta's Conversion Leads Integration specification
- ✅ Changed `action_source` from `"other"` to `"system_generated"` (required)
- ✅ Added `custom_data.event_source: "crm"` (required)
- ✅ Added `custom_data.lead_event_source` (configurable CRM name)
- ✅ Updated API version to v24.0 (latest)
- ✅ Formatted email and phone as arrays (`em[]`, `ph[]`) per specification
- ✅ Added CRM name setting in WordPress admin
- ✅ Improved user data hashing for CRM events
