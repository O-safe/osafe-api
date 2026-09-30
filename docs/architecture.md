# O SAFE Security — Technical System Architecture Blueprint

## Executive Summary

O SAFE Security is a high-availability, multi-tenant physical security and safety monitoring platform backend. Designed to process continuous device telemetry, manage personal and family protection networks, enforce fine-grained access policies, and handle recurring subscription billing, O SAFE Security provides a secure and scalable foundation for connected safety devices and mobile/web applications.

---

## 1. System Overview & Core Principles

The backend is built on **Laravel 12.x** using a domain-driven, service-oriented RESTful API architecture.

### Key Architectural Principles:
1. **API-First & Headless**: Decoupled backend REST API serving web dashboards, mobile applications, and embedded hardware endpoints.
2. **Multi-Guard Access Isolation**: Complete separation between end-user authentication (`user`) and administrative operations (`admin`).
3. **Hardware Integration Layer**: Dedicated hardware telemetry and command execution pipeline (`/api/v1/device-integration/`) operating under HMAC signature verification.
4. **Policy-Driven Authorization**: Domain policies enforcing user-level and family-level tenant data isolation on every read/write operation.
5. **Event-Driven Asynchronous Processing**: Offloading high-frequency telemetry logging, notification dispatches, and email transmissions to Redis background queues.
6. **Payment Provider Abstraction**: Interface-bound billing engine decoupling domain subscription logic from third-party payment gateways.

---

## 2. High-Level System Architecture

```mermaid
flowchart TB
    subgraph Clients["Clients & Edge Tier"]
        WebApp["Web Dashboard"]
        MobileApp["Mobile Apps (iOS / Android)"]
        Hardware["Physical Security Devices"]
    end

    subgraph API_Gateway["API Gateway & Ingress Boundary"]
        Router["Laravel API Router (/api/v1)"]
        Throttle["Rate Limiter & Global API Key Check"]
        SanctumAuth["Sanctum Multi-Guard Authentication"]
        HMACAuth["Hardware HMAC Signature Verifier"]
    end

    subgraph Core_Services["Domain Service Layer"]
        UserSvc["User & Profile Service"]
        DeviceSvc["Physical Device Management Service"]
        TelemetrySvc["Location & Telemetry Ingestion Service"]
        GeofenceSvc["Geofence Perimeter Engine"]
        FamilySvc["Family Safety Circle Service"]
        AlertSvc["Security Alert Resolution Engine"]
        NotificationSvc["Multi-Channel Notification Service"]
        BillingSvc["Subscription & Billing Engine"]
        AuditSvc["Security Audit Service"]
    end

    subgraph Data_Storage["Data Storage & Event Pipeline"]
        MySQL[("MySQL 8.0 Primary DB")]
        RedisCache[("Redis Cache & Session Store")]
        RedisQueue[("Redis Queue Workers")]
        Broadcaster["Laravel Reverb (WebSockets)"]
    end

    subgraph External["External Gateways"]
        SMTP["SMTP Transactional Mailer"]
        Paystack["Paystack Gateway / Null Driver"]
    end

    WebApp -->|HTTPS / REST| Router
    MobileApp -->|HTTPS / REST| Router
    Hardware -->|HTTP Ingestion / Telemetry| Router

    Router --> Throttle
    Throttle --> SanctumAuth
    Throttle --> HMACAuth

    SanctumAuth --> Core_Services
    HMACAuth --> TelemetrySvc

    UserSvc --> MySQL
    DeviceSvc --> MySQL
    TelemetrySvc --> RedisQueue
    TelemetrySvc --> MySQL
    GeofenceSvc --> AlertSvc
    FamilySvc --> MySQL
    AlertSvc --> NotificationSvc
    NotificationSvc --> RedisQueue
    NotificationSvc --> Broadcaster
    NotificationSvc --> SMTP
    BillingSvc --> Paystack
    BillingSvc --> MySQL
    AuditSvc --> MySQL

    RedisQueue --> TelemetrySvc
    RedisQueue --> NotificationSvc
```

---

## 3. Domain Model & Subsystem Boundaries

The application is structured into clearly separated domain boundaries:

```mermaid
classDiagram
    class User {
        +BigInteger user_id
        +String email
        +String password
        +Integer status_id
        +devices()
        +families()
        +subscriptions()
    }

    class Staff {
        +BigInteger staff_id
        +String email
        +String password
        +roles()
    }

    class Device {
        +BigInteger device_id
        +String serial_number
        +String name
        +String status
        +String hmac_secret
        +telemetries()
        +commands()
    }

    class Family {
        +BigInteger family_id
        +String name
        +BigInteger owner_id
        +members()
    }

    class FamilyMember {
        +BigInteger id
        +BigInteger family_id
        +BigInteger user_id
        +String role
        +String relationship
    }

    class Geofence {
        +BigInteger geofence_id
        +String name
        +String shape_type
        +Decimal latitude
        +Decimal longitude
        +Decimal radius
    }

    class Subscription {
        +BigInteger subscription_id
        +BigInteger user_id
        +BigInteger plan_id
        +String status
        +DateTime starts_at
        +DateTime ends_at
    }

    User "1" -- "*" Device : owns/monitors
    User "1" -- "*" FamilyMember : participates
    Family "1" -- "*" FamilyMember : contains
    User "1" -- "*" Subscription : subscribes
```

### Key Subsystem Descriptions:

1. **User & Identity Domain**:
   - Manages end-user profiles, security preferences, trusted login devices (`user_devices`), and password credentials.
2. **Staff Administration & RBAC Domain**:
   - Manages administrative staff users (`staff`), Spatie roles, granular permissions, and security incident tracking.
3. **Physical Device Domain**:
   - Handles physical hardware inventory, device assignments (`device_user`), state transitions (`ACTIVE`, `SUSPENDED`, `DEACTIVATED`), HMAC credential issuance, and remote command pipelines (`device_commands`).
4. **Location & Geofencing Domain**:
   - High-throughput location record ingestion (`device_locations`), spatial distance calculations, circular and polygon geofence bounds (`geofences`), and automated perimeter breach evaluations.
5. **Family Safety Circle Domain**:
   - Shared safety pools (`families`), tokenized email invitations (`family_invitations`), membership roles (`owner`, `admin`, `member`, `child`), and family-level device monitoring permissions.
6. **Alerts & Notification Domain**:
   - Emergency alert generation (`alerts`), user-configurable delivery preferences (`notification_preferences`), scheduled quiet hours, and multi-channel dispatch (in-app, email, WebSocket broadcasting).
7. **Subscriptions & Billing Domain**:
   - Tiered plans (`subscription_plans`), subscription lifecycles, billing transaction ledgers (`billing_transactions`), Paystack webhook idempotency, and provider abstraction.

---

## 4. Physical Device Integration Boundary

Physical hardware devices communicate with the backend through a specialized boundary designed for high reliability and security.

### Ingestion Flow:
```mermaid
sequenceDiagram
    autonumber
    participant Dev as Physical Security Device
    participant API as Ingress Middleware (DeviceAuth)
    participant Svc as Device Integration Service
    participant Queue as Redis Telemetry Queue
    participant DB as MySQL Storage
    participant WS as Reverb WebSockets

    Dev->>API: POST /api/v1/device-integration/location (X-Device-ID, X-Device-Signature)
    API->>API: Verify HMAC-SHA256 Payload Signature
    API->>Svc: Hand off Validated Telemetry Payload
    Svc->>Queue: Push Async Location Processing Job
    Svc-->>Dev: HTTP 202 Accepted (Pending Commands Attached)

    Queue->>DB: Persist Location Coordinates to device_locations
    Queue->>Svc: Evaluate Active Geofences
    alt Perimeter Breached
        Svc->>DB: Record Security Alert
        Svc->>WS: Broadcast Emergency Alert to Family Channel
    end
```

### Hardware Authentication & Security:
- **Device Identifiers**: Every hardware unit presents a unique `X-Device-ID` header.
- **HMAC Signatures**: Telemetry payloads are signed with the device's assigned secret key (`X-Device-Signature: hmac_sha256(payload, secret)`).
- **Credential Rotation**: Admins can rotate device secret keys dynamically via `/api/v1/admin/devices/{id}/credentials/rotate`.

---

## 5. Subscription & Billing Architecture

The billing subsystem uses an interface-driven design allowing seamless transitions between payment providers.

```mermaid
classDiagram
    class PaymentProviderInterface {
        <<interface>>
        +initializeCheckout(User user, SubscriptionPlan plan) PaymentCheckoutResponse
        +verifyTransaction(String reference) PaymentVerificationResponse
        +processWebhook(Request request) WebhookEvent
    }

    class PaystackPaymentProvider {
        +initializeCheckout()
        +verifyTransaction()
        +processWebhook()
    }

    class NullPaymentProvider {
        +initializeCheckout()
        +verifyTransaction()
        +processWebhook()
    }

    class SubscriptionService {
        -PaymentProviderInterface provider
        +checkout(User user, Plan plan)
        +activateSubscription(User user, Plan plan)
        +handleWebhook(Request request)
    }

    PaymentProviderInterface <|.. PaystackPaymentProvider
    PaymentProviderInterface <|.. NullPaymentProvider
    SubscriptionService --> PaymentProviderInterface
```

### Webhook Idempotency & Security Pipeline:
1. **Signature Validation**: Validates the cryptographic header (`X-Paystack-Signature`).
2. **Idempotency Lock**: Acquires an atomic Redis lock on the event ID (`billing:webhook:{event_id}`).
3. **Transaction State Machine**: Verifies that billing transactions transition predictably (`PENDING` -> `SUCCESS` -> `COMPLETED`).
4. **Subscription Period Extension**: Extends subscription validity exactly once per successful payment event.

---

## 6. Realtime & Background Processing Pipeline

High-frequency operations (telemetry, alerts, mail dispatches) execute asynchronously off the HTTP request-response cycle.

```mermaid
flowchart LR
    Event["Domain Event Trigger<br/>(e.g., Device Tampered)"]
    Queue["Redis Queue<br/>(jobs: notifications, alerts)"]
    Worker["Queue Worker"]
    Mail["SMTP Transactional Email"]
    WS["Laravel Reverb WebSockets"]
    DB[("MySQL Database")]

    Event --> Queue
    Queue --> Worker
    Worker --> DB
    Worker --> Mail
    Worker --> WS
```

---

## 7. Security & Compliance Controls

- **Sanctum Multi-Guard Auth**: Prevents cross-contamination between administrative staff and end-user access tokens.
- **Rate Limiting**: Strict rate limiting on authentication routes (5 requests/min on login, 3 requests/min on OTP generation).
- **IDOR / BOLA Prevention**: All resource controllers resolve models via explicit policy gates (`$this->authorize('view', $device)`).
- **Data Protection**: Encrypted model attributes (`SensitiveFieldEncryption`) for credentials and integration keys.
- **Audit Ledger**: Comprehensive audit logging recording IP address, user agent, action name, and target payload for administrative operations.

---

## 8. Deployment & Infrastructure Guidelines

- **Web Server**: Nginx / Apache forwarding to PHP-FPM 8.2+.
- **Database Engine**: MySQL 8.0.30+ with InnoDB engine and UTF8MB4 collation.
- **Caching & Queues**: Redis instance configured with `predis` driver.
- **Process Supervisor**: Supervisor monitoring background queue workers (`php artisan queue:work redis --tries=3`).
- **WebSocket Gateway**: Laravel Reverb daemon bound to internal port 8080 with reverse-proxy SSL termination.
