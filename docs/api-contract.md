# O SAFE Security API — Comprehensive API Contract Specification

**Version**: `1.0.0`  
**Base Path**: `/api/v1`  
**Content-Type**: `application/json`  
**Response Format**: `{ "success": boolean, "message": string, "data": mixed }`

---

## Table of Contents
1. [Setup & Public Endpoints](#1-setup--public-endpoints)
2. [Authentication Module](#2-authentication-module)
3. [User Profile & Account Module](#3-user-profile--account-module)
4. [Family Safety Circle Module](#4-family-safety-circle-module)
5. [Physical Security Device Module](#5-physical-security-device-module)
6. [Physical Device Ingress Integration Module](#6-physical-device-ingress-integration-module)
7. [Location Tracking & History Module](#7-location-tracking--history-module)
8. [Geofencing & Boundary Module](#8-geofencing--boundary-module)
9. [Alerts & Emergency SOS Module](#9-alerts--emergency-sos-module)
10. [Notifications Module](#10-notifications-module)
11. [Subscriptions & Tier Module](#11-subscriptions--tier-module)
12. [Billing, Transactions & Webhooks Module](#12-billing-transactions--webhooks-module)
13. [Support & Helpdesk Module](#13-support--helpdesk-module)
14. [Audit Logging Module](#14-audit-logging-module)
15. [Administrative Governance Module (Admin Guard)](#15-administrative-governance-module-admin-guard)
16. [System & Health Check Endpoints](#16-system--health-check-endpoints)

---

## 1. Setup & Public Endpoints

### 1.1 List Available Plans
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/setup/plans`
- **Auth Guard**: Public / None
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Subscription plans retrieved successfully.",
    "data": [
      {
        "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3d0001",
        "name": "Basic Protection",
        "slug": "basic-protection",
        "price": 4999.00,
        "currency": "NGN",
        "billing_interval": "monthly",
        "max_family_members": 3,
        "max_devices": 2,
        "features": ["Location History (7 days)", "1 Geofence Zone", "Standard Email Alerts"]
      }
    ]
  }
  ```

---

## 2. Authentication Module

### 2.1 User Registration
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/auth/register`
- **Auth Guard**: Public
- **Request Body**:
  ```json
  {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+2348012345678",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!"
  }
  ```
- **Validation Rules**: `first_name` (required|string|max:50), `last_name` (required|string|max:50), `email` (required|email|unique:users), `phone` (nullable|string|max:20), `password` (required|min:8|confirmed).
- **Response `201 Created`**:
  ```json
  {
    "success": true,
    "message": "User registered successfully.",
    "data": {
      "user": {
        "id": "usr_9b1deb4d_0001",
        "first_name": "John",
        "last_name": "Doe",
        "email": "john.doe@example.com"
      },
      "access_token": "1|sanctum_token_string_here",
      "token_type": "Bearer"
    }
  }
  ```

### 2.2 User Login
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/auth/login`
- **Auth Guard**: Public
- **Request Body**:
  ```json
  {
    "email": "john.doe@example.com",
    "password": "SecurePassword123!"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Login successful.",
    "data": {
      "user": { ... },
      "access_token": "2|sanctum_token_string_here",
      "token_type": "Bearer"
    }
  }
  ```

### 2.3 User Logout
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/auth/logout`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Successfully logged out."
  }
  ```

---

## 3. User Profile & Account Module

### 3.1 Get Profile
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/profile`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Profile retrieved.",
    "data": {
      "id": "usr_9b1deb4d_0001",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "phone": "+2348012345678",
      "two_factor_enabled": false,
      "created_at": "2026-09-20T10:00:00.000000Z"
    }
  }
  ```

### 3.2 Update Profile
- **HTTP Method**: `PUT` / `PATCH`
- **Endpoint**: `/api/v1/user/profile`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "first_name": "Johnny",
    "phone": "+2348099999999"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Profile updated successfully.",
    "data": { ... }
  }
  ```

---

## 4. Family Safety Circle Module

### 4.1 List User Families
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/families`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Families retrieved.",
    "data": [
      {
        "id": "fam_001",
        "name": "Doe Family",
        "role": "owner",
        "member_count": 3
      }
    ]
  }
  ```

### 4.2 Create Family Circle
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/families`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**: `{"name": "Doe Family Circle"}`
- **Response `201 Created`**: Returns created family object with caller set as `owner`.

### 4.3 Add Family Member
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/families/{family}/members`
- **Auth Guard**: `auth:sanctum` (`auth:user`), `FamilyPolicy@addMember`
- **Request Body**:
  ```json
  {
    "user_id": "usr_9b1deb4d_0002",
    "role": "member"
  }
  ```
- **Validation Rules**: `role` must be one of `owner`, `admin`, `member`, `child`.
- **Response `201 Created`**: Returns updated family membership record.

---

## 5. Physical Security Device Module

### 5.1 List Family Devices
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/devices`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**: Returns list of physical security hardware devices (cameras, hubs, sensors).

### 5.2 Register Physical Device
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/devices`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "family_id": "fam_001",
    "serial_number": "SN-CAM-992123",
    "name": "Front Door Camera",
    "device_type": "camera"
  }
  ```
- **Response `201 Created`**: Returns registered device with provisioned credentials (`device_id`, `secret_key`).

### 5.3 Send Device Command
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/devices/{device}/commands`
- **Auth Guard**: `auth:sanctum` (`auth:user`), `DevicePolicy@control`
- **Request Body**:
  ```json
  {
    "command_type": "arm_system",
    "payload": {"mode": "away", "delay": 30}
  }
  ```
- **Response `202 Accepted`**: Queues command for device polling/realtime execution.

---

## 6. Physical Device Ingress Integration Module

### 6.1 Submit Telemetry
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/device-integration/telemetry`
- **Auth Guard**: `AuthenticatePhysicalDeviceMiddleware` (HMAC SHA-256 Signature)
- **Headers Required**: `X-Device-ID`, `X-Device-Timestamp`, `X-Device-Signature`
- **Request Body**:
  ```json
  {
    "battery_level": 88,
    "signal_strength": -55,
    "status": "online",
    "sensor_readings": {"motion_detected": true, "temp": 24.5}
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "message": "Telemetry processed."
  }
  ```

### 6.2 Fetch Pending Commands
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/device-integration/commands`
- **Auth Guard**: HMAC SHA-256 Signature
- **Response `200 OK`**: Returns array of pending commands queued for this hardware ID.

---

## 7. Location Tracking & History Module

### 7.1 Report User Location
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/locations`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "latitude": 6.5244,
    "longitude": 3.3792,
    "accuracy": 5.0,
    "battery_level": 85
  }
  ```
- **Response `201 Created`**: Broadcasts location update to family presence channel and evaluates geofence boundaries.

---

## 8. Geofencing & Boundary Module

### 8.1 Create Geofence Zone
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/geofences`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "family_id": "fam_001",
    "name": "Home Safe Zone",
    "latitude": 6.5244,
    "longitude": 3.3792,
    "radius_meters": 150,
    "alert_on_entry": true,
    "alert_on_exit": true
  }
  ```
- **Response `201 Created`**: Returns created geofence configuration.

---

## 9. Alerts & Emergency SOS Module

### 9.1 Trigger Emergency SOS
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/alerts/sos`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "family_id": "fam_001",
    "latitude": 6.5244,
    "longitude": 3.3792,
    "note": "Immediate assistance needed!"
  }
  ```
- **Response `201 Created`**: Broadcasts high-priority alert to family members, sends push/email notifications, and logs audit trail.

---

## 10. Notifications Module

### 10.1 List Notifications
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/notifications`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Query Params**: `unread_only` (boolean, optional)
- **Response `200 OK`**: Returns paginated notification list.

---

## 11. Subscriptions & Tier Module

### 11.1 Get Active Subscription
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/subscriptions/current`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**: Returns current active subscription details, active plan features, and renewal dates.

### 11.2 Initialize Checkout
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/subscriptions/initialize`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "plan_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3d0001",
    "payment_method": "paystack"
  }
  ```
- **Response `200 OK`**: Returns Paystack authorization URL and reference ID.

---

## 12. Billing, Transactions & Webhooks Module

### 12.1 Billing Webhook Ingress
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/webhooks/billing`
- **Auth Guard**: Public + `X-Paystack-Signature` HMAC Verification
- **Idempotency**: Redis key locking on `event_id` / transaction reference.
- **Response `200 OK`**: `{ "status": "success", "message": "Webhook processed." }`

---

## 13. Support & Helpdesk Module

### 13.1 Create Support Ticket
- **HTTP Method**: `POST`
- **Endpoint**: `/api/v1/user/support/tickets`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Request Body**:
  ```json
  {
    "subject": "Camera Setup Issue",
    "category": "technical_support",
    "priority": "medium",
    "message": "Having trouble connecting door camera to home WiFi."
  }
  ```
- **Response `201 Created`**: Returns ticket reference ID.

---

## 14. Audit Logging Module

### 14.1 Get User Security Logs
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/user/audit-logs`
- **Auth Guard**: `auth:sanctum` (`auth:user`)
- **Response `200 OK`**: Returns audit trail of account logins, password resets, and security events.

---

## 15. Administrative Governance Module (Admin Guard)

### 15.1 Admin List Users
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/admin/users`
- **Auth Guard**: `auth:sanctum` (`auth:admin`)
- **Permission**: `users.view` (Spatie RBAC)
- **Response `200 OK`**: Returns paginated user catalog.

### 15.2 Admin Manage Subscription Plans
- **HTTP Method**: `POST` / `PUT`
- **Endpoint**: `/api/v1/admin/plans`
- **Auth Guard**: `auth:sanctum` (`auth:admin`)
- **Permission**: `subscriptions.manage`
- **Response `201 Created`**: Returns created or modified plan metadata.

---

## 16. System & Health Check Endpoints

### 16.1 API Health Verification
- **HTTP Method**: `GET`
- **Endpoint**: `/api/v1/health`
- **Auth Guard**: Public
- **Response `200 OK`**:
  ```json
  {
    "status": "healthy",
    "services": {
      "database": "connected",
      "redis": "connected",
      "reverb": "connected"
    },
    "timestamp": "2026-09-30T04:40:00.000000Z"
  }
  ```
