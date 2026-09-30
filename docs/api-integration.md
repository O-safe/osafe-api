# O SAFE Security API — Client Integration Guide

## 1. Overview & Architecture

The **O SAFE Security REST API** provides backend services for personal and family safety monitoring, physical security device management, realtime alerts, geofence boundary enforcement, subscription billing, and administrative governance.

### Base URLs & Versioning
- **Production Base URL**: `https://api.osafe.security/api/v1`
- **Staging Base URL**: `https://staging-api.osafe.security/api/v1`
- **API Versioning**: URL path prefix `/api/v1/`.

---

## 2. Authentication & Guard Architecture

O SAFE enforces strict separation between authentication guards to isolate end-user traffic, staff administration, and physical IoT hardware integration.

| Guard / System | Auth Mechanism | Primary Use Case | Base Path |
| :--- | :--- | :--- | :--- |
| **User Guard** (`auth:sanctum` / `auth:user`) | Bearer Token (Sanctum) | Web & Mobile Client Apps | `/api/v1/user/` |
| **Admin Guard** (`auth:sanctum` / `auth:admin`) | Bearer Token (Sanctum) + Spatie RBAC | Staff Admin Portal | `/api/v1/admin/` |
| **Device Integration** | HMAC-SHA256 Request Signatures | Hardware Hubs / Cameras / Sensors | `/api/v1/device-integration/` |
| **Billing Webhook Ingress** | Paystack HMAC Signature | Payment Gateway Notifications | `/api/v1/webhooks/billing/` |

---

## 3. Standard Request & Response Headers

### 3.1 Client Request Headers
All API requests from Web and Mobile clients must specify:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <SANCTUM_API_TOKEN>
X-Device-ID: <TRUSTED_APP_DEVICE_UUID> (Optional for trusted login device tracking)
```

### 3.2 Physical Device Ingress Headers
IoT hardware devices submitting telemetry or fetching command queues must specify HMAC headers:

```http
Accept: application/json
Content-Type: application/json
X-Device-ID: <HARDWARE_DEVICE_UUID>
X-Device-Timestamp: <UNIX_TIMESTAMP_SECONDS>
X-Device-Signature: <HMAC_SHA256_HEX_SIGNATURE>
```

---

## 4. Response Envelope & Error Handling Standards

### 4.1 Successful Response Format
All successful responses return HTTP status `200 OK` or `201 Created` with a uniform JSON wrapper:

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": { ... }
}
```

### 4.2 Paginated Response Format
List endpoints returning paginated records supply standardized pagination metadata inside `data`:

```json
{
  "success": true,
  "message": "Resource list retrieved successfully.",
  "data": {
    "items": [ ... ],
    "pagination": {
      "total": 150,
      "count": 15,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 10,
      "links": {
        "next": "https://api.osafe.security/api/v1/user/alerts?page=2",
        "prev": null
      }
    }
  }
}
```

### 4.3 Standard Error Responses

#### `401 Unauthorized`
Returned when Bearer token is missing, expired, or invalid:
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

#### `403 Forbidden`
Returned when user lacks RBAC permissions or policy authorization (e.g., non-owner modifying family settings):
```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

#### `404 Not Found`
Returned when the requested resource UUID does not exist or belong to the user's family scope:
```json
{
  "success": false,
  "message": "Resource not found."
}
```

#### `422 Unprocessable Content` (Validation Error)
Returned when request parameters fail validation:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

#### `429 Too Many Requests` (Rate Limited)
Returned when exceeding API rate limits:
```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

---

## 5. Realtime Events & WebSocket Integration

O SAFE uses **Laravel Reverb** (WebSockets) for real-time notification delivery, device status changes, and family member emergency SOS events.

### 5.1 Connection Setup
- **WebSocket Endpoint**: `wss://ws.osafe.security/app/<REVERB_APP_KEY>`
- **Channel Authorization Endpoint**: `POST /api/v1/broadcasting/auth`
- **Auth Header**: `Authorization: Bearer <SANCTUM_TOKEN>`

### 5.2 Subscription Channels

| Channel Pattern | Access Scope | Broadcast Events |
| :--- | :--- | :--- |
| `private-user.{userId}` | Individual User | `NotificationSent`, `SubscriptionStatusUpdated` |
| `private-family.{familyId}` | Family Circle Members | `EmergencySosTriggered`, `GeofenceBreached`, `DeviceAlertTriggered` |
| `presence-family.{familyId}` | Family Circle Members | Location updates & online status tracking |
| `private-device.{deviceId}` | Device Owners | `DeviceStateChanged`, `TelemetryUpdated`, `CommandExecuted` |

---

## 6. Physical Device HMAC Ingress Authentication

Hardware devices (Hubs, Cameras, Motion Sensors) register with secret tokens to sign requests.

### HMAC Signature Generation
To make a request to `/api/v1/device-integration/...`:
1. Concatenate: `<HTTP_METHOD>.<REQUEST_PATH>.<TIMESTAMP>.<REQUEST_BODY_JSON>`
2. Compute `HMAC-SHA256` signature using the assigned `device_secret_key`.
3. Send signature in `X-Device-Signature` header.

Example (Node.js / Python):
```python
import hmac, hashlib, json, time

secret = "device_secret_key_hex"
timestamp = str(int(time.time()))
body = json.dumps({"battery_level": 95, "signal_strength": -65})
payload = f"POST./api/v1/device-integration/telemetry.{timestamp}.{body}"

signature = hmac.new(secret.encode(), payload.encode(), hashlib.sha256).hexdigest()
```

---

## 7. Client Integration Blueprints

### 7.1 Web & Mobile App Authentication Flow
1. `POST /api/v1/auth/login` → Returns `access_token` and `user` object.
2. Store `access_token` securely (Encrypted SharedPreferences on Android, Keychain on iOS, HTTP-only cookie or secure storage on Web).
3. Include token in `Authorization: Bearer <token>` header for all subsequent requests.
4. On logout, execute `POST /api/v1/user/auth/logout` to revoke the token on the server.

### 7.2 Subscription Checkout Flow
1. Request plans: `GET /api/v1/setup/plans`.
2. Initiate checkout: `POST /api/v1/user/subscriptions/initialize` with `plan_id` and `payment_method`.
3. Frontend receives Paystack payment authorization URL / reference.
4. Redirect user to complete payment or handle in-app SDK.
5. Paystack posts webhook to `POST /api/v1/webhooks/billing` -> Backend processes activation idempotently.
6. Verify status: `GET /api/v1/user/subscriptions/current`.

---

## 8. Development & Support
For API integration support or bug reporting, refer to `docs/api-contract.md` or contact `dev-support@osafe.security`.
