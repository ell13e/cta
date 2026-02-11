# Eventbrite Platform API v3 - Complete Documentation

**Comprehensive guide combining official documentation, API reference, and implementation examples.**

**Last Updated:** January 2026  
**API Version:** v3  
**Base URL:** `https://www.eventbriteapi.com/v3/`

---

## Table of Contents

1. [Introduction & Overview](#introduction--overview)
2. [Authentication & Authorization](#authentication--authorization)
3. [API Basics](#api-basics)
4. [Core Data Objects](#core-data-objects)
5. [Event Management](#event-management)
6. [Ticket Classes](#ticket-classes)
7. [Orders & Attendees](#orders--attendees)
8. [Venues & Locations](#venues--locations)
9. [Organizations & Users](#organizations--users)
10. [Rate Limits & Quotas](#rate-limits--quotas)
11. [Error Handling](#error-handling)
12. [Pagination & Expansions](#pagination--expansions)
13. [Best Practices](#best-practices)
14. [Resources & Links](#resources--links)

---

## Introduction & Overview

The Eventbrite Platform API v3 is a RESTful web service that enables developers to integrate Eventbrite's event management and ticketing platform with external applications, websites, and workflows.

### Key Capabilities (Relevant to WordPress Integration)

- **Event Management**: Create, read, update, publish, and unpublish events
- **Ticket Management**: Configure ticket types, pricing, and availability
- **Order Tracking**: Monitor ticket sales and sync spaces back to WordPress
- **Venue Operations**: Create and manage event venues (default: The Maidstone Studios)
- **Attendee Management**: Track attendees for space syncing

### API Architecture

The Eventbrite API follows REST (Representational State Transfer) principles:

- **REST-based** (though uses POST instead of PUT for updates)
- **OAuth2** for authorization
- **JSON** responses
- **HTTPS** only

**Base URL:** `https://www.eventbriteapi.com/v3/`

### Why REST API?

- Well-suited for web and mobile applications
- Scalable and flexible architecture
- Simple and intuitive endpoint design
- Easy to test and debug
- Widely supported across programming languages

---

## Authentication & Authorization

### Authentication Methods

#### 1. Private Token (Personal Access Token)

The simplest authentication method for personal use and small-scale integrations.

**How to Generate:**
1. Log into your Eventbrite account
2. Navigate to [API Keys page](https://www.eventbrite.com/platform/api-keys)
3. Generate a new private token
4. Copy the token securely (you'll only see it once)

**Usage in Requests:**

**Authorization Header (Recommended):**
```
Authorization: Bearer YOUR_PRIVATE_TOKEN
Content-Type: application/json
```

**Query Parameter (Alternative):**
```
/v3/users/me/?token=YOUR_PRIVATE_TOKEN
```

**Example cURL Request:**
```bash
curl --include \
  --header "Authorization: Bearer YOUR_PRIVATE_TOKEN" \
  --header "Content-Type: application/json" \
  'https://www.eventbriteapi.com/v3/users/me/'
```

**Example PHP/WordPress:**
```php
$response = wp_remote_get('https://www.eventbriteapi.com/v3/users/me/', [
    'headers' => [
        'Authorization' => 'Bearer ' . $oauth_token,
        'Content-Type' => 'application/json',
    ],
]);
```


### Authentication Best Practices

1. **Do not use your private token directly in client-side code**
   - Before making your application publicly available, ensure client-side code does not contain private tokens

2. **Delete unneeded API keys**
   - To minimize exposure to attack, delete any private tokens you no longer need

3. **Secure token storage**
   - Never hardcode tokens in source code
   - Use environment variables or secure secret management
   - Rotate tokens regularly
   - Revoke tokens when no longer needed

4. **HTTPS Only**
   - Always use HTTPS for API requests
   - Validate SSL certificates
   - Never transmit tokens over HTTP

---

## API Basics

### HTTP Methods

- `GET` - Retrieve resources
- `POST` - Create resources or perform actions (also used for updates)
- `PUT` - Not typically used (Eventbrite uses POST for updates)
- `DELETE` - Delete resources

### Response Format

All responses are JSON. Success responses include the requested data:

```json
{
  "id": "123456789",
  "name": {
    "text": "Event Name",
    "html": "Event Name"
  },
  "created": "2023-01-15T10:30:00Z",
  "modified": "2023-01-20T14:20:00Z"
}
```

### Status Codes

| Status | Meaning | Example |
|--------|---------|---------|
| **200** | OK - Request successful | GET request returns data |
| **201** | Created - New resource created | POST creates event |
| **204** | No Content - Success, no response body | DELETE successful |
| **400** | Bad Request - Invalid parameters | Missing required field |
| **401** | Unauthorized - Authentication failed | Invalid or missing token |
| **403** | Forbidden - No permission | Accessing another user's event |
| **404** | Not Found - Resource doesn't exist | Event ID doesn't exist |
| **429** | Rate Limited - Too many requests | Exceeded API quota |
| **500** | Server Error - Eventbrite issue | Service temporarily unavailable |
| **502** | Bad Gateway - Service temporarily down | Maintenance window |

### Content Types

- **Request:** `application/json`
- **Response:** `application/json`

---

## Core Data Objects

### Event Object

Represents an Eventbrite Event with comprehensive details.

**Key Properties:**
- `id` - Unique event identifier
- `name` - Event title (text object with multilingual support)
- `description` - Event description (text object with HTML)
- `summary` - Short event summary (140 characters)
- `start` - Event start date/time (timezone-aware)
- `end` - Event end date/time
- `status` - Event status (draft, live, started, ended, completed, canceled)
- `capacity` - Maximum attendees allowed
- `venue_id` - Associated venue ID
- `logo` - Event logo/image
- `organizer_id` - Event organizer ID
- `online_event` - Boolean (true for online events)
- `url` - Event public URL
- `currency` - Event currency (e.g., "GBP", "USD")

**Text Objects:**
Eventbrite uses text objects for multilingual support:
```json
{
  "name": {
    "text": "Plain text version",
    "html": "HTML version"
  },
  "description": {
    "text": "Plain text description",
    "html": "<p>HTML formatted description</p>"
  }
}
```

### Attendee Object

Represents individual event attendees and ticket purchasers.

**Key Properties:**
- `id` - Unique attendee identifier
- `email` - Attendee email address
- `name` - Attendee full name
- `first_name` - First name
- `last_name` - Last name
- `status` - Attendance status (attending, not_attending, unpaid)
- `event_id` - Associated event ID
- `ticket_class_id` - Ticket type purchased
- `order_id` - Associated order ID
- `checked_in` - Boolean indicating check-in status
- `created` - Timestamp of creation

### Order Object

Represents ticket purchase orders.

**Key Properties:**
- `id` - Unique order identifier
- `event_id` - Associated event
- `user_id` - Purchaser user ID
- `email` - Purchaser email
- `status` - Order status (placed, paid, pending, refunded)
- `created` - Order creation timestamp
- `costs` - Pricing breakdown (base price, fees, taxes, gross)
- `attendees` - List of attendees in order

### Ticket Class Object

Defines ticket types and pricing for events.

**Key Properties:**
- `id` - Unique ticket class identifier
- `event_id` - Associated event
- `name` - Ticket type name
- `description` - Ticket description
- `quantity_total` - Total tickets available
- `quantity_sold` - Tickets sold
- `quantity_available` - Remaining tickets
- `cost` - Price object with currency and value
- `fee` - Service fees
- `tax` - Applied taxes
- `free` - Boolean (true for free tickets)
- `on_sale_status` - Sales status (on sale, off sale, not on sale)

### Venue Object

Represents event locations.

**Key Properties:**
- `id` - Unique venue identifier
- `name` - Venue name
- `address` - Address object with:
  - `address_1` - Street address
  - `address_2` - Additional address line
  - `city` - City name
  - `region` - State/region
  - `postal_code` - ZIP/postal code
  - `country` - Country code (e.g., "GB", "US")
- `latitude` - Geographic latitude
- `longitude` - Geographic longitude

---

## Event Management

### Create Event

**Endpoint:** `POST /organizations/{organization_id}/events/`

**Required Parameters:**
```json
{
  "event": {
    "name": {
      "text": "My Awesome Event"
    },
    "start": {
      "timezone": "Europe/London",
      "local": "2026-06-15T10:00:00"
    },
    "end": {
      "timezone": "Europe/London",
      "local": "2026-06-15T14:00:00"
    },
    "currency": "GBP"
  }
}
```

**Optional Parameters:**
- `description` - Event description (text and HTML)
- `summary` - Event summary (max 140 characters)
- `capacity` - Maximum attendees
- `venue_id` - Associated venue ID
- `organizer_id` - Event organizer
- `listed` - Boolean (public listing)

**Example Request:**
```bash
curl -X POST \
  https://www.eventbriteapi.com/v3/organizations/{org_id}/events/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event": {
      "name": {
        "text": "Training Course - Maidstone, Kent"
      },
      "description": {
        "text": "Course description",
        "html": "<p>Course description</p>"
      },
      "start": {
        "timezone": "Europe/London",
        "local": "2026-01-15T09:00:00"
      },
      "end": {
        "timezone": "Europe/London",
        "local": "2026-01-15T17:00:00"
      },
      "currency": "GBP",
      "venue_id": "123456789"
    }
  }'
```

**Example PHP/WordPress:**
```php
function cta_create_eventbrite_event($event_data, $oauth_token, $org_id) {
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/events/";
    
    $payload = [
        'event' => $event_data['event']
    ];
    
    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($payload),
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($code >= 200 && $code < 300) {
        return $data;
    }
    
    return new WP_Error('api_error', 'Eventbrite API error: ' . $body);
}
```

### Get Event

**Endpoint:** `GET /events/{event_id}/`

**Query Parameters:**
- `expand` - Expand related resources (venue, organizer, ticket_classes)

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://www.eventbriteapi.com/v3/events/123456789/?expand=venue,organizer
```

### Update Event

**Endpoint:** `POST /events/{event_id}/`

**Note:** Eventbrite uses POST for updates, not PUT.

**Modifiable Fields:**
- `name`
- `description`
- `summary`
- `start` and `end` times
- `capacity`
- `venue_id`

**Example:**
```bash
curl -X POST \
  https://www.eventbriteapi.com/v3/events/123456789/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event": {
      "name": {
        "text": "Updated Event Name"
      }
    }
  }'
```

### Publish Event

**Endpoint:** `POST /events/{event_id}/publish/`

Publishes a draft event, making it live and searchable.

**Example:**
```bash
curl -X POST \
  https://www.eventbriteapi.com/v3/events/123456789/publish/ \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Unpublish Event

**Endpoint:** `POST /events/{event_id}/unpublish/`

Unpublishes a live event, converting it back to draft.

### Cancel Event

**Endpoint:** `POST /events/{event_id}/cancel/`

Cancels an event.

### Delete Event

**Endpoint:** `POST /events/{event_id}/delete/`

Permanently deletes an event.

### List Events

**Endpoint:** `GET /organizations/{organization_id}/events/`

**Query Parameters:**
- `status` - Filter by status (draft, live, started, ended, completed, canceled)
- `order_by` - Sort order (start_asc, start_desc, created_asc, created_desc)
- `expand` - Expand related resources (venue, organizer, ticket_classes)
- `continuation` - Pagination token

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://www.eventbriteapi.com/v3/organizations/{org_id}/events/?status=live&order_by=start_asc&expand=venue"
```

### Event Statuses

- `draft` - Not published
- `live` - Published and active
- `started` - Event has started
- `ended` - Event has ended
- `completed` - Event completed
- `canceled` - Event canceled

### Copy Event

**Endpoint:** `POST /events/{event_id}/copy/`

Creates a duplicate of an existing event.

---

## Ticket Classes

### Create Ticket Class

**Endpoint:** `POST /events/{event_id}/ticket_classes/`

**Required Fields:**
```json
{
  "ticket_class": {
    "name": "Standard Ticket",
    "quantity_total": 50,
    "free": false,
    "cost": {
      "currency": "GBP",
      "value": 5000,
      "display": "£50.00"
    }
  }
}
```

**Optional Fields:**
- `description` - Ticket description
- `donation` - Boolean (for donation tickets)
- `hidden` - Boolean (hide from public)
- `sales_start` - When sales start
- `sales_end` - When sales end
- `minimum_quantity` - Minimum purchase
- `maximum_quantity` - Maximum purchase

**Free Tickets:**
```json
{
  "ticket_class": {
    "name": "Free Admission",
    "quantity_total": 100,
    "free": true
  }
}
```

**Example:**
```bash
curl -X POST \
  https://www.eventbriteapi.com/v3/events/123456789/ticket_classes/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ticket_class": {
      "name": "General Admission",
      "quantity_total": 100,
      "free": false,
      "cost": {
        "currency": "GBP",
        "value": 5000
      }
    }
  }'
```

### Get Ticket Classes

**Endpoint:** `GET /events/{event_id}/ticket_classes/`

**Response includes:**
- `quantity_sold` - Number of tickets sold
- `quantity_total` - Total available
- `quantity_available` - Remaining tickets

### Update Ticket Class

**Endpoint:** `POST /events/{event_id}/ticket_classes/{ticket_class_id}/`

### Delete Ticket Class

**Endpoint:** `POST /events/{event_id}/ticket_classes/{ticket_class_id}/delete/`

---

## Orders & Attendees

### Get Order

**Endpoint:** `GET /orders/{order_id}/`

### List Orders

**Endpoint:** `GET /events/{event_id}/orders/`

**Query Parameters:**
- `status` - Filter by status (placed, paid, refunded, etc.)
- `changed_since` - Only orders changed since timestamp
- `expand` - Expand related resources

**Order Statuses:**
- `placed` - Order placed
- `paid` - Payment received
- `partially_refunded` - Partially refunded
- `refunded` - Fully refunded
- `cancelled` - Cancelled

### Get Attendee

**Endpoint:** `GET /attendees/{attendee_id}/`

### List Attendees

**Endpoint:** `GET /events/{event_id}/attendees/`

**Query Parameters:**
- `status` - Filter by status (attending, not_attending, etc.)
- `changed_since` - Only attendees changed since timestamp
- `expand` - Expand related resources (answers, profile)

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://www.eventbriteapi.com/v3/events/123456789/attendees/?expand=answers,profile"
```

---

## Venues & Locations

### Create Venue

**Endpoint:** `POST /organizations/{organization_id}/venues/`

**Required Fields:**
```json
{
  "venue": {
    "name": "The Maidstone Studios",
    "address": {
      "address_1": "The Maidstone Studios, New Cut Road",
      "city": "Maidstone",
      "region": "Kent",
      "postal_code": "ME14 5NZ",
      "country": "GB"
    }
  }
}
```

**Example:**
```bash
curl -X POST \
  https://www.eventbriteapi.com/v3/organizations/{org_id}/venues/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "venue": {
      "name": "The Maidstone Studios",
      "address": {
        "address_1": "The Maidstone Studios, New Cut Road",
        "city": "Maidstone",
        "region": "Kent",
        "postal_code": "ME14 5NZ",
        "country": "GB"
      }
    }
  }'
```

### List Venues

**Endpoint:** `GET /organizations/{organization_id}/venues/`

### Get Venue

**Endpoint:** `GET /venues/{venue_id}/`

### Update Venue

**Endpoint:** `POST /venues/{venue_id}/`

---

## Organizations & Users

### Get Organization

**Endpoint:** `GET /organizations/{organization_id}/`

### List Organizations

**Endpoint:** `GET /users/me/organizations/`

### Get User

**Endpoint:** `GET /users/me/`

Returns the authenticated user's profile.

### Get User Events

**Endpoint:** `GET /users/{user_id}/events/`

### Get User Owned Events

**Endpoint:** `GET /users/{user_id}/owned_events/`

---

## Rate Limits & Quotas

### Default Rate Limits

| Metric | Limit | Notes |
|--------|-------|-------|
| **API Calls per Hour** | 2,000 | Per OAuth token |
| **API Calls per Day** | 24,000 | Per OAuth token |
| **Burst Limit** | 10 requests/second | Short-term burst |

### Rate Limit Headers

When making API requests, Eventbrite returns rate limit information in response headers:

```
X-RateLimit-Limit: 2000
X-RateLimit-Remaining: 1995
X-RateLimit-Reset: 1641234567
```

### Handling Rate Limit Errors

**HTTP 429 Status Code - Too Many Requests**

When rate limit is exceeded:
```json
{
  "status_code": 429,
  "error": "HIT_RATE_LIMIT",
  "error_description": "Hourly rate limit has been reached for this token."
}
```

**Recommended Retry Strategy:**
```php
$max_retries = 3;
$retry_count = 0;
$delay = 1;

while ($retry_count < $max_retries) {
    $response = wp_remote_get($url, $args);
    $code = wp_remote_retrieve_response_code($response);
    
    if ($code !== 429) {
        break; // Success or other error
    }
    
    // Rate limited - wait and retry
    sleep($delay);
    $delay *= 2; // Exponential backoff
    $retry_count++;
}
```

### Rate Limit Best Practices

1. **Monitor remaining calls** - Check `X-RateLimit-Remaining` header
2. **Implement exponential backoff** - Retry with increasing delays on rate limit errors
3. **Cache responses** - Store frequently-accessed data to reduce API calls
4. **Batch operations** - Combine multiple operations when possible
5. **Throttle requests** - Add delays between requests during bulk operations (e.g., 0.2 seconds between syncs)

---

## Error Handling

### Error Response Format

When an error occurs during an API request, you will receive:

- An HTTP error status (in the 400-500 range)
- A JSON response containing more information about the error

**Typical Error Response:**
```json
{
  "error": "VENUE_AND_ONLINE",
  "error_description": "You cannot both specify a venue and set online_event",
  "status_code": 400
}
```

### Common Errors

| Status Code | Error Code | Description |
|:---------- | :---------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 301 | PERMANENTLY_MOVED | Resource must be retrieved from a different URL. |
| 400 | ACTION_NOT_PROCESSED | Requested operation not processed. |
| 400 | ARGUMENTS_ERROR | There are errors with your arguments. |
| 400 | BAD_CONTINUATION_TOKEN | Invalid continuation token passed. |
| 400 | BAD_PAGE | Page number does not exist or is an invalid format (e.g. negative). |
| 400 | BAD_REQUEST | The resource you're creating already exists. |
| 400 | INVALID_ARGUMENT | Invalid argument value passed. |
| 400 | INVALID_AUTH | Authentication/OAuth token is invalid. |
| 400 | INVALID_AUTH_HEADER | Authentication header is invalid. |
| 400 | INVALID_BODY | A request body that was not in JSON format was passed. |
| 400 | UNSUPPORTED_OPERATION | Requested operation not supported. |
| 400 | VENUE_AND_ONLINE | Cannot specify both venue and online_event. |
| 401 | ACCESS_DENIED | Authentication unsuccessful. |
| 401 | NO_AUTH | Authentication not provided. |
| 403 | NOT_AUTHORIZED | User has not been authorized to perform that action. |
| 404 | NOT_FOUND | Invalid URL or resource doesn't exist. |
| 405 | METHOD_NOT_ALLOWED | Method is not allowed for this endpoint. |
| 409 | REQUEST_CONFLICT | Requested operation resulted in conflict. |
| 429 | HIT_RATE_LIMIT | Hourly rate limit has been reached for this token. |
| 500 | EXPANSION_FAILED | Unhandled error occurred during expansion. |
| 500 | INTERNAL_ERROR | Unhandled error occurred in Eventbrite. |

### Error Handling Best Practices

1. **Check HTTP status codes** - Always verify response status
2. **Parse error responses** - Extract error codes and descriptions
3. **Log errors** - Record errors for debugging
4. **Handle gracefully** - Provide user-friendly error messages
5. **Retry on transient errors** - Retry on 429 and 5xx errors with backoff

**Example Error Handling:**
```php
$response = wp_remote_get($url, $args);
$code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);

if ($code >= 400) {
    $error = isset($data['error']) ? $data['error'] : 'Unknown error';
    $error_desc = isset($data['error_description']) ? $data['error_description'] : '';
    
    error_log("Eventbrite API Error ({$code}): {$error} - {$error_desc}");
    
    if ($code === 429) {
        // Rate limited - retry with backoff
        return retry_with_backoff($url, $args);
    }
    
    return new WP_Error('eventbrite_api_error', $error_desc, $data);
}
```

---

## Pagination & Expansions

### Paginated Responses

An Eventbrite paginated response is made up of two main sections: A pagination header and a list of objects.

**Example Paginated Response:**
```json
{
  "pagination": {
    "object_count": 4,
    "continuation": "AEtFRyiWxkr0ZXyCJcnZ5U1-uSWXJ6vO0sxN06GbrDngaX5U5i8XYmEuZfmZZYB9Uq6bSizOLYoV",
    "page_count": 2,
    "page_size": 2,
    "has_more_items": true,
    "page_number": 1
  },
  "events": [
    {
      "id": "123456",
      "name": {
        "text": "Event Name"
      }
    }
  ]
}
```

**Pagination Attributes:**

| Attribute | Example | Description |
| :---------------- | :---------------------------------- | :------------------------------------------------------------------------------------------------ |
| `object_count` | `4` | The total number of objects found in your response, across all pages. |
| `continuation` | `AEtFRyiWxkr0Z...` | The continuation token you'll use to get to the next set of results. |
| `page_count` | `2` | The total number of pages found in your response. |
| `page_size` | `2` | The maximum number of objects that can be returned per page for this API endpoint. |
| `has_more_items` | `true` | Boolean indicating whether or not there are more items in your response. |
| `page_number` | `1` | The page number you are currently viewing (always starts at 1). |

### Using Continuation Tokens

1. Make a call to any listing endpoint that retrieves a paginated response
2. Verify that the "has_more_items" attribute is "true" before continuing
3. Copy the continuation token from your response
4. Call the endpoint again, adding the continuation token as a query string parameter:
   ```
   /v3/categories/?continuation=AEtFRyiWxkr0ZXyCJcnZ5U1-uSWXJ6vO0sxN06GbrDngaX5U5i8XYmEuZfmZZYB9Uq6bSizOLYoV
   ```
5. Repeat until all desired records have been retrieved

**Example:**
```php
$continuation = null;
$all_events = [];

do {
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/events/";
    if ($continuation) {
        $url .= "?continuation=" . urlencode($continuation);
    }
    
    $response = wp_remote_get($url, $args);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($data['events'])) {
        $all_events = array_merge($all_events, $data['events']);
    }
    
    $continuation = isset($data['pagination']['continuation']) 
        ? $data['pagination']['continuation'] 
        : null;
    
} while ($continuation && isset($data['pagination']['has_more_items']) && $data['pagination']['has_more_items']);
```

### Expansions

Eventbrite has many models that refer to each other, and often you'll want to fetch related data along with the primary model you're querying. The way of doing this in the Eventbrite API is called "expansions".

**Expansions v1 (Current):**

Pass a comma-separated list of expansion names as the `expand=` querystring argument:

```
/v3/users/me/owned_events/?expand=organizer,venue
```

**Nested Expansions:**

You can expand attributes of objects that have themselves been returned by an expansion:

```
/v3/users/me/orders/?expand=event.venue
```

Expansions can be nested up to four levels deep.

**Expansions v2 (Recommended if Available):**

If an endpoint's top-level response object contains an attribute named `_type`, that endpoint supports Expansions v2.

Request expansions using `expand.[value of _type]=`:

```
/v3/events/12345/?expand.event=organizer,venue
```

**Common Expansions:**

- `venue` - Venue details
- `organizer` - Organizer details
- `ticket_classes` - Ticket class information

**Best Practices:**

- Use as few expansions as possible to ensure faster response times
- Each expansion slows down your API request slightly
- Use expansions to reduce the number of API requests needed

---

## Common Use Cases

### Use Case: Syncing Spaces from Eventbrite to WordPress

**Objective:** Sync ticket sales from Eventbrite back to WordPress to maintain accurate space availability.

**Implementation:**
1. Fetch ticket class data from Eventbrite
2. Extract `quantity_sold` from ticket classes
3. Store in WordPress as `_eventbrite_bookings` meta
4. Recalculate `spaces_available` = `total_spaces - wordpress_bookings - eventbrite_bookings`

**PHP Example:**
```php
function cta_sync_spaces_from_eventbrite($post_id) {
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    if (!$eventbrite_id) {
        return false;
    }
    
    $oauth_token = get_option('cta_eventbrite_oauth_token', '');
    if (empty($oauth_token)) {
        return false;
    }
    
    // Get ticket class info from Eventbrite
    $url = "https://www.eventbriteapi.com/v3/events/{$eventbrite_id}/ticket_classes/";
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 15,
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['ticket_classes']) && !empty($data['ticket_classes'])) {
        $ticket_class = $data['ticket_classes'][0];
        $quantity_sold = isset($ticket_class['quantity_sold']) ? intval($ticket_class['quantity_sold']) : 0;
        
        // Store Eventbrite bookings count
        update_post_meta($post_id, '_eventbrite_bookings', $quantity_sold);
        
        // Recalculate spaces_available based on BOTH sources
        cta_recalculate_spaces_available($post_id);
        
        return true;
    }
    
    return false;
}
```

### Use Case: Importing Events from Eventbrite

**Objective:** Import existing Eventbrite events into WordPress and auto-fill fields.

**Implementation:**
1. Fetch events from organization
2. Display list to user
3. Parse Eventbrite event data
4. Auto-fill WordPress ACF fields

**PHP Example:**
```php
function cta_ajax_import_eventbrite_event() {
    $event_data = json_decode(stripslashes($_POST['eventbrite_event']), true);
    
    // Parse Eventbrite event data
    $event_name = $event_data['name']['text'];
    $start_local = $event_data['start']['local'];
    $venue_name = $event_data['venue']['name'];
    $capacity = $event_data['capacity'];
    
    // Parse date/time
    $start_datetime = new DateTime($start_local, new DateTimeZone('Europe/London'));
    $event_date = $start_datetime->format('Y-m-d');
    $start_time = $start_datetime->format('H:i');
    
    // Fill ACF fields
    update_field('event_date', $event_date, $post_id);
    update_field('start_time', $start_time, $post_id);
    update_field('event_location', $venue_name, $post_id);
    update_field('total_spaces', $capacity, $post_id);
    
    // Store Eventbrite ID
    update_post_meta($post_id, 'eventbrite_id', $event_data['id']);
}
```

---

## Best Practices

### Security

1. **Secure Token Storage**
   - Never hardcode tokens in source code
   - Use environment variables or secure secret management
   - Rotate tokens regularly
   - Revoke tokens when no longer needed

2. **HTTPS Only**
   - Always use HTTPS for API requests
   - Validate SSL certificates
   - Never transmit tokens over HTTP


### Performance Optimization

1. **Pagination Efficiency**
   - Request only needed page_size
   - Use `has_more_items` to control pagination loop
   - Cache paginated results appropriately

2. **Expand Parameters**
   - Use `expand` parameter to get related data in single call
   - Reduces number of API requests needed
   ```
   GET /events/{event_id}/?expand=venue,organizer,ticket_classes
   ```

3. **Filtering**
   - Use query parameters to filter results
   - Reduces data transfer and processing
   - Improves response times

4. **Caching Strategy**
   - Cache event metadata (venue, category)
   - Implement appropriate TTL for cached data
   - Invalidate cache on updates

### Error Handling & Resilience

1. **Implement Retry Logic**
   - Retry on 429 (rate limit) and 5xx errors
   - Use exponential backoff strategy
   - Set maximum retry attempts

2. **Graceful Degradation**
   - Handle missing optional fields
   - Provide fallback data when API unavailable
   - Cache critical data locally

3. **Monitoring & Alerts**
   - Log all API errors and warnings
   - Alert on repeated failures
   - Monitor rate limit usage
   - Track API response times

### Documentation & Maintenance

1. **Version Control**
   - Document API integration code
   - Keep track of API version being used
   - Plan for future API updates

2. **Testing**
   - Use test API tokens with sandbox events
   - Test error scenarios and edge cases
   - Verify space syncing accuracy

3. **Compliance**
   - Review Terms of Service regularly
   - Ensure data handling complies with privacy laws
   - Implement proper data retention policies

### Integration Architecture

1. **Delayed Processing**
   - Use WordPress scheduled events for delayed uploads
   - Prevents blocking during save operations
   - Ensures ACF fields are saved before API calls

2. **Logging & Debugging**
   - Log all API requests and responses
   - Error log API failures for debugging
   - Monitor sync results

---

## Resources & Links

### Official Documentation

- **API Documentation:** https://www.eventbrite.com/platform/docs/
- **API Reference:** https://www.eventbrite.com/platform/api/
- **API Explorer:** https://www.eventbrite.com/platform/docs/api-explorer
- **API Keys Management:** https://www.eventbrite.com/platform/api-keys/

### Specific Documentation Pages

- **Introduction:** https://www.eventbrite.com/platform/docs/introduction
- **Authentication:** https://www.eventbrite.com/platform/docs/authentication
- **API Basics:** https://www.eventbrite.com/platform/docs/api-basics
- **Creating Events:** https://www.eventbrite.com/platform/docs/create-events
- **Events:** https://www.eventbrite.com/platform/docs/events
- **Event Description:** https://www.eventbrite.com/platform/docs/event-description
- **Ticket Classes:** https://www.eventbrite.com/platform/docs/ticket-classes
- **Orders:** https://www.eventbrite.com/platform/docs/orders
- **Attendees:** https://www.eventbrite.com/platform/docs/attendees
- **Organizations:** https://www.eventbrite.com/platform/docs/organizations
- **Rate Limits:** https://www.eventbrite.com/platform/docs/rate-limits

---

## WordPress Integration Notes

This documentation is used in conjunction with the WordPress Eventbrite integration located in:

- **Main Integration File:** `wordpress-theme/inc/eventbrite-integration.php`
- **Settings:** `wordpress-theme/inc/api-keys-settings.php`
- **ACF Fields:** `wordpress-theme/inc/acf-fields.php`
- **Admin UI:** `wordpress-theme/inc/admin.php`

### Implementation Features

- Automatic event upload to Eventbrite
- AI-generated SEO-optimized descriptions
- Bidirectional space syncing
- Booking tracking (WordPress + Eventbrite)
- Import from Eventbrite
- Venue management (default: The Maidstone Studios)
- Scheduled automatic sync

---

**Last Updated:** January 2026  
**API Version:** v3  
**Documentation compiled from:**
- Official Eventbrite Platform documentation
- API Blueprint specification (eventbriteapiv3public.apib)
- WordPress integration implementation experience

---

*This guide focuses on the Eventbrite API endpoints and features relevant to the WordPress integration use case: event creation/management, ticket classes, venue management, and space syncing.*
