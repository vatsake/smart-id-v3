# PHP Client Library for Smart ID v3 API

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/vatsake/smart-id-v3.svg)](https://packagist.org/packages/vatsake/smart-id-v3)

> [!WARNING]
> **Development Status**
>
> This library is currently under development and has not yet reached a stable release.

A PHP library for interacting with the [Smart ID v3 API](https://sk-eid.github.io/smart-id-documentation/rp-api/introduction.html) with support for authentication, digital signatures, and certificate management.

## Table of Contents

- [Installation](#installation)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
  - [Certificate Setup](#certificate-setup)
  - [Client Initialization](#client-initialization)
  - [SSL/HTTPS Configuration](#sslhttps-configuration)
- [Usage Flows](#usage-flows)
  - [Device Link Flows](#device-link-flows)
  - [Notification Flows](#notification-flows)
  - [Certificate Retrieval](#certificate-retrieval)
- [Response Validation](#response-validation)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [Resources](#resources)
- [Testing](#testing)
- [Todo](#todo)

## Quick Start

```php
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SmartIdEnv;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;

$filesystemAdapter = new Local(__DIR__);
$filesystem = new Filesystem($filesystemAdapter);

$pool = new FilesystemCachePool($filesystem);

$log = new Logger('test');
$log->pushHandler(new StreamHandler(__DIR__ . '/log.log'));

// 1. Initialize client
$config = new SmartIdConfig(
    relyingPartyName: 'DEMO',
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    env: SmartIdEnv::DEMO,
    certificatePath: __DIR__ . '/certificates',
    cache: $pool,
    logger: $log // Optional
);
$smartId = new SmartId($config);

// 2. Create authentication request
$rpChallenge = RpChallenge::generate();
$request = NotificationAuthRequest::builder()->withInteractions('Hello world')
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->build();

// 3. Start notification flow authentication
$session = $smartId->notification()
    ->authentication()
    ->startDocument($request, 'PNOEE-40504040001-DEM0-Q');

// 4. Poll for session completion
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms
    ->getAuthSession(30000); // 30000ms timeout

// 5. Validate response
$response->validate()
    ->withCertificateValidation()
    ->withRevocationValidation()
    ->withSignatureValidation()
    ->check();
echo "Authentication successful!";
```

## Installation

Install via Composer:

```bash
composer require vatsake/smart-id-v3
```

> [!NOTE]
> This library uses `php-http/discovery` to automatically detect and use PSR-18 HTTP clients. You'll need to install a compatible HTTP client implementation:

```bash
composer require guzzlehttp/guzzle
```

Other compatible clients: `symfony/http-client`, `curl-client`, etc.

> [!NOTE]
> This library requires a PSR-6 cache implementation. You must install a compatible cache adapter:

```bash
composer require cache/filesystem-adapter
```

Other compatible clients: `cache/redis-adapter`, `cache/memcached-adapter`, etc.

## Requirements

- PHP ^8.1
- PSR-18 HTTP client implementation
- PSR-6 cache implementation

## Configuration

### Certificate Setup

The library requires intermediate and authority certificates to validate certificate chains.

**Certificate format/location:**

- All certificates must be in PEM format (text-based, not binary DER)
- Place all trusted certificates in a single directory
- Use an absolute path like `/path/to/certificates` when configuring `certificatePath`

**Smart ID Certificates:**
Certificates are available to download at [Smart ID documentation](https://www.skidsolutions.eu/resources/certificates/).

### Client Initialization

Initialize the Smart ID client with `SmartIdConfig`:

```php
$config = new SmartIdConfig(
    relyingPartyName: 'DEMO',
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    env: SmartIdEnv::DEMO,
    certificatePath: '/path/to/trusted-certificates',
	cache: $cache // \Psr\Cache\CacheItemPoolInterface
);
```

> [!NOTE]
> Cache is only used for CRL (Certificate Revocation List) caching to optimize revocation validation performance.

**With HTTP Client and Logging:**

For production deployments, configure a custom HTTP client and enable logging:

```php
$client = new \GuzzleHttp\Client();
$log = new Monolog\Logger('smart-id');
$log->pushHandler(new StreamHandler(__DIR__ . '/log.log'));

$filesystemAdapter = new Local(__DIR__);
$filesystem = new Filesystem($filesystemAdapter);
$pool = new FilesystemCachePool($filesystem);

$config = new SmartIdConfig(
    relyingPartyName: 'DEMO',
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    env: SmartIdEnv::DEMO,
    certificatePath: '/path/to/certificates',
    httpClient: $client,
    logger: $log,
	cache: $pool
);
```

### SSL/HTTPS Configuration

Smart ID requires enhanced security measures including HTTPS key pinning. Configure your HTTP client to use public key pinning:

**Option 1: Strict Key Pinning (Recommended for Production)**

Pin directly to the Smart ID server's public key:

```php
$client = new \GuzzleHttp\Client([
    'curl' => [
        CURLOPT_PINNEDPUBLICKEY => 'sha256//....',
    ],
]);
```

**Option 2: Intermediate Pinning**

Pin to Smart ID's intermediate certificate instead of the leaf certificate. This reduces the need to update your configuration annually:

```php
$client = new \GuzzleHttp\Client([
    'verify'  => '/path/to/cert/bundle', // Contains only Smart ID's intermediate certificate
    'curl' => [
        CURLOPT_CAPATH => '/path/that/does/not/exist', // Disable system truststore
    ],
]);
```

> **Note**: While intermediate pinning is more convenient, strict key pinning provides stronger security guarantees.

## Usage Flows

### Choosing the Right Flow

Smart ID supports multiple authentication and signature flows depending on your use case:

| Flow             | User Initiation            | Use Case                                  |
| ---------------- | -------------------------- | ----------------------------------------- |
| **Device Link**  | User scans QR or opens app | Mobile-first, modern applications         |
| **Notification** | Server initiates           | Traditional web applications; less secure |

### Device Link Flows

[Device Link Flows](https://sk-eid.github.io/smart-id-documentation/rp-api/device_link_flows.html) allow users to initiate sessions themselves by scanning a QR code or pressing a button on a website/app that opens the Smart ID application directly.

**Device Link Advantages:**

- User controls the flow
- Supports [Web2App](https://sk-eid.github.io/smart-id-documentation/rp-api/introduction.html#_about_rp_api_v3) and [App2App](https://sk-eid.github.io/smart-id-documentation/rp-api/introduction.html#_about_rp_api_v3) flows
- Better mobile user experience

#### Device Link - Anonymous authentication

Authenticate a user without requiring them to provide identity information upfront.

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$rpChallenge = RpChallenge::generate();
$request = DeviceLinkAuthRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();
$session = $smartId->deviceLink()->authentication()->startAnonymous($request);

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getAuthSession(10000); // Maximum time in milliseconds to wait for session completion, you might need to specify your httpclient's timeout accordingly

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);
```

#### Device Link - Identified authentication - National ID

Authenticate a specific user by their national identification number. Use when you know who the user is beforehand.

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$identifier = SemanticsIdentifier::builder()
    ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
    ->withCountryCode('EE')
    ->withIdentifier('40404040009')
    ->build();

$rpChallenge = RpChallenge::generate();
$request = DeviceLinkAuthRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();
$session = $smartId->deviceLink()->authentication()->startEtsi($request, $identifier);

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getAuthSession(10000); // Maximum time in milliseconds to wait for session completion

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);
```

#### Device Link - Identified authentication - Document Number

Authenticate a user using their Smart ID document number. Use when you know user's document number.

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$rpChallenge = RpChallenge::generate();
$request = DeviceLinkAuthRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->deviceLink()->authentication()->startDocument($request, 'PNOEE-40404040009-MOCK-Q');

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getAuthSession(10000); // Maximum time in milliseconds to wait for session completion

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);
```

#### Device Link - Anonymous signature with certificate Selection (LINKED session)

Allow user to choose which certificate to use for signing:

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

// Step 1: Certificate selection
$request = DeviceLinkCertChoiceRequest::builder()
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->build();
$session = $smartId->deviceLink()->signing()->startAnonymousCertChoice($request);

$_SESSION['session'] = $session; // Save it for later

// Get certificate selection result
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getCertChoiceSession(10000); // Maximum time in milliseconds to wait for session completion
$_SESSION['documentNo'] = $response->documentNumber; // Save it for later

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);

// Step 2: Sign with selected certificate (after successful cert-choice)
$dataToSign = "hello world";
$request = LinkedRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withLinkedSessionId($session->getSessionId())
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->build();

$documentNo = $_SESSION['documentNo'];
$smartId->notification()->signing()->startLinkedSigning($request, $documentNo);

// Get session status
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion
```

#### Device Link - Identified signature - National ID

Sign documents for a specific user identified by national ID:

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$identifier = SemanticsIdentifier::builder()
    ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
    ->withCountryCode('EE')
    ->withIdentifier('40404040009')
    ->build();

$dataToSign = "hello world";
$request = DeviceLinkSigningRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->build();

$session = $smartId->deviceLink()->signing()->startEtsi($request, $identifier);

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);
```

#### Device Link - Identified signature - Document Number

Sign documents for a user identified by document number:

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$dataToSign = "hello world";
$request = DeviceLinkSigningRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->withInitialCallbackUrl('https://localhost/callback') // Mandatory for Web2App/App2App flows
    ->build();

$session = $smartId->deviceLink()->signing()->startDocument($request, 'PNOEE-40404040009-MOCK-Q');

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion

// During status polling, refresh QR-code every second
$session->getDeviceLink(DeviceLinkType::QR);
```

### Notification Flows

[Notification Flows](https://sk-eid.github.io/smart-id-documentation/rp-api/notification_based_flows.html) are initiated by the server and push notifications to the user's mobile device. This is the traditional Smart ID flow.

**Notification Advantages:**

- Server-initiated, predictable flow
- No QR code generation or refresh needed

#### Notification - Authentication - National ID

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$identifier = SemanticsIdentifier::builder()
    ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
    ->withCountryCode('EE')
    ->withIdentifier('40504040001')
    ->build();

$rpChallenge = RpChallenge::generate();
$request = NotificationAuthRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->notification()->authentication()->startEtsi($request, $identifier);

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getAuthSession(10000); // Maximum time in milliseconds to wait for session completion
```

#### Notification - Authentication - Document Number

Authenticate a user using their Smart ID document number (server-initiated):

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$rpChallenge = RpChallenge::generate();
$request = NotificationAuthRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->notification()->authentication()->startDocument($request, 'PNOEE-40504040001-DEM0-Q');

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getAuthSession(10000); // Maximum time in milliseconds to wait for session completion
```

#### Notification - Signature - National ID

Sign documents for a user identified by national ID (server-initiated):

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$identifier = SemanticsIdentifier::builder()
    ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
    ->withCountryCode('EE')
    ->withIdentifier('40504040001')
    ->build();

$dataToSign = "hello world";
$request = NotificationSigningRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->notification()->signing()->startEtsi($request, $identifier);

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion
```

#### Notification - Signature - Document Number

Sign documents for a user identified by document number (server-initiated):

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$dataToSign = "hello world";
$request = NotificationSigningRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->notification()->signing()->startDocument($request, 'PNOEE-40504040001-DEM0-Q');

$_SESSION['session'] = $session; // Save it for later

// Get session status
$ses = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion
```

#### Notification - Signature with Certificate Selection - National ID

Allow a user to select their certificate before signing. First perform a certificate choice, then sign with the selected certificate:

```php
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

$identifier = SemanticsIdentifier::builder()
    ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
    ->withCountryCode('EE')
    ->withIdentifier('40504040001')
    ->build();

// Step 1: Let user choose certificate
$request = NotificationCertChoiceRequest::builder()
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$session = $smartId->notification()->signing()->startCertChoice($request, $identifier);

$_SESSION['session'] = $session; // Save it for later

// Get certificate selection result
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getCertChoiceSession(10000); // Maximum time in milliseconds to wait for session completion
$_SESSION['documentNo'] = $response->documentNumber; // Save it for later

// Step 2: Sign with selected certificate (after successful cert-choice)
$dataToSign = "hello world";
$request = NotificationSigningRequest::builder()
    ->withInteractions(
        'display text up to 60 characters',
        'display text up to 200 characters'
    )
    ->withData($dataToSign, HashAlgorithm::SHA_256)
    ->withCertificateLevel(CertificateLevel::QUALIFIED) // Optional
    ->build();

$documentNo = $_SESSION['documentNo'];
$session = $smartId->notification()->signing()->startDocument($request, $documentNo);

// Get session status
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion
```

### Certificate Retrieval

Retrieve a user's signing certificate without initiating a full authentication or signing session:

```php
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\SmartId;

$config = new SmartIdConfig(...);
$smartId = new SmartId($config);

// Retrieve certificate for a document
$smartId->getSigningCertificate('PNOEE-40504040001-DEM0-Q');
```

## Response Validation

After receiving a response from Smart ID, you should always validate the response to ensure the signature is valid, the certificate is trusted, and the certificate hasn't been revoked. The library provides a fluent validation API:

```php
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;

// Client signed something

/** @var Vatsake\SmartIdV3\Session\SigningSession */
$response = $smartId->session($session)
    ->withPolling(1000) // Poll every 1000ms (optional, will repeat polling automatically)
    ->getSigningSession(10000); // Maximum time in milliseconds to wait for session completion

try {
    $response->validate()
        ->withSignatureValidation(true)      // Validates the signature (enabled by default)
        ->withCertificateValidation(true)     // Validates certificate chain
        ->withRevocationValidation(true)   // Checks if certificate is revoked (OCSP or CRL)
		->withCallbackUrlValidationParameters(...) // Validate callback url in Web2App/App2App flows
        ->check();

    // Response is valid, proceed with your logic
    echo "Signature is valid!";
} catch (IncompleteSessionException $e) {
    // Session not complete yet
} catch (UserRefusedException $e) {
    // User refused the request
} catch (SessionTimeoutException $e) {
    // Session timed out
} catch (DocumentUnusableException $e) {
    // Document unusable - check Smart-ID app for details
} catch (WrongVcException $e) {
    // User entered wrong verification code
} catch (RequiredInteractionNotSupportedByAppException $e) {
    // User's app doesn't support required interaction
} catch (UserRefusedCertChoiceException $e) {
    // User refused certificate choice
} catch (UserRefusedInteractionException $e) {
    // User cancelled on interaction screen
} catch (ProtocolFailureException $e) {
    // Logical error in signing protocol
} catch (ExpectedLinkedSessionException $e) {
    // App received different transaction instead of linked session
} catch (ServerErrorException $e) {
    // Smart-ID server error
} catch (ValidationException $e) {
    // - SignatureException if signature validation fails
    // - CertificateChainException if certificate chain is invalid
    // - OcspCertificateRevocationException if certificate is revoked
    // - OcspResponseTimeException if OCSP response time is outside acceptable window
    // - OcspSignatureException if OCSP response signature is invalid
    // - OcspKeyUsageException if OCSP responder certificate key usage is invalid
    // - OcspUrlMissingException if OCSP URL is not found
    // - CrlRevocationException if certificate is revoked according to CRL or CRL request fails
    // - CrlSignatureException if CRL signature is invalid
    // - CrlUrlMissingException if CRL URL is not found
    // - UnknownSignatureAlgorithmOidException if signature algorithm OID is unknown
    // - CertificatePolicyException if required Smart-ID policy OIDs are missing
    // - CertificateKeyUsageException if key usage or extended key usage is invalid
    // - CertificateQcException if qualified certificate does not contain required QC statements
    // - SessionSecretMismatchException if session secret digest does not match expected value
    // - InitialCallbackUrlParamMismatchException if callback URL query parameter does not match expected value
    // - UserChallengeMismatchException if user challenge verifier does not match expected value
} catch (Exception $e) {
    // Something unexpected went wrong
}
```

## Error Handling

### Exception Hierarchy

Exceptions are organized in a hierarchy to help with error handling:

**Session State Exceptions** - User or session state issues:

| Exception                    | Description                                                 |
| ---------------------------- | ----------------------------------------------------------- |
| `IncompleteSessionException` | The session is not complete yet. Try polling again.         |
| `UserRefusedException`       | User refused the request (e.g., rejected in Smart-ID app).  |
| `SessionTimeoutException`    | Session timed out. User didn't respond in time.             |
| `DocumentUnusableException`  | Request failed. Check Smart-ID app logs or contact support. |

**User Interaction Exceptions** - Issues with user interactions:

| Exception                                       | Description                                               |
| ----------------------------------------------- | --------------------------------------------------------- |
| `WrongVcException`                              | User entered the wrong verification code.                 |
| `RequiredInteractionNotSupportedByAppException` | User's app version doesn't support required interactions. |
| `UserRefusedCertChoiceException`                | User refused to choose a certificate.                     |
| `UserRefusedInteractionException`               | User cancelled on the interaction screen.                 |

**Protocol Exceptions** - Protocol-level issues:

| Exception                        | Description                                                   |
| -------------------------------- | ------------------------------------------------------------- |
| `ProtocolFailureException`       | A logical error occurred in the signing protocol.             |
| `ExpectedLinkedSessionException` | App received different transaction instead of linked session. |
| `ServerErrorException`           | Smart-ID server returned an error.                            |

**Validation Exceptions** - Certificate and signature validation failures:

| Exception                                  | Description                                                    |
| ------------------------------------------ | -------------------------------------------------------------- |
| `ValidationException`                      | Generic validation failure (parent of others below).           |
| `SignatureException`                       | Signature validation failed.                                   |
| `OcspUrlMissingException`                  | OCSP responder URL is not found in certificate.                |
| `CrlRevocationException`                   | Certificate is revoked according to CRL or CRL request failed. |
| `CrlSignatureException`                    | CRL signature validation failed.                               |
| `CrlUrlMissingException`                   | CRL URL is not found in certificate.                           |
| `UnknownSignatureAlgorithmOidException`    | Signature algorithm OID is unknown or not supported.           |
| `CertificateChainException`                | Certificate chain validation failed. Not trusted.              |
| `OcspCertificateRevocationException`       | OCSP status indicates certificate is revoked.                  |
| `OcspKeyUsageException`                    | OCSP responder certificate missing OCSP signing key usage.     |
| `OcspResponseTimeException`                | OCSP response time outside acceptable clock skew window.       |
| `OcspSignatureException`                   | OCSP response signature validation failed.                     |
| `CertificateKeyUsageException`             | Certificate key usage validation failed.                       |
| `CertificatePolicyException`               | Certificate missing required Smart-ID policy OIDs.             |
| `CertificateQcException`                   | Certificate missing required QC statements.                    |
| `InitialCallbackUrlParamMismatchException` | Initial callback URL unique parameter mismatch.                |
| `SessionSecretMismatchException`           | Session secret mismatch.                                       |
| `UserChallengeMismatchException`           | User challenge mismatch.                                       |

## Best Practices

- **Always validate responses** - Never trust unvalidated signatures or certificates
- **Enable all validations** - Use `withCertificateValidation(true)`, `withRevocationValidation(true)`, `withCallbackUrlValidation(true) (App2App/Web2App flows)`, and `withSignatureValidation(true)`
- **Log exceptions** - Capture exception details for debugging and monitoring
- **Provide user feedback** - Inform users of temporary issues (timeouts, refusal) vs. permanent errors (revoked certificate)

## Troubleshooting

### Common Issues

**Certificate validation failures**

If you're getting `CertificateChainException` or other certificate errors:

1. Verify all your trusted certificates are in PEM format
2. Check that the path is correct
3. Ensure certificate file permissions allow reading (644 or similar)

**OCSP validation errors**

If you encounter out-of-memory errors during revocation checks:

1. Increase memory. Certificate Revocation Lists (CRLs) can be large files containing tens of thousands of revoked certificate entries. During parsing, these are loaded into memory, which consume significant resources.

If you're getting `OcspCertificateRevocationException` or other OCSP errors:

1. Verify your system has correct time/date (OCSP responses include time validation)
2. Check network connectivity to OCSP responders
3. Enable debug logging

**Errors when scanning QR code**

1. Ensure QR codes are refreshed every 1-2 seconds during polling
2. Verify QR code is large enough to scan
3. Check that `withInitialCallbackUrl` is set correctly for Web2App/App2App flows
4. Confirm the callback URL is accessible from the user's device

### Debug Logging

Enable detailed logging to troubleshoot issues:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('smart-id');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$config = new SmartIdConfig(
    ...
    logger: $logger
);
```

## FAQ

** How would I know if user is on mobile? **

> **Note:** This is relevant for Web2App flows where you may want to adjust UX based on device type.

There really isn't a foolproof method. One way is using the User-Agent header, but it can be spoofed or changed by the user.

```js
// Frontend code - JavaScript
function isOnMobile() {
  const ua = navigator.userAgent
  return /mobi|iphone|ipod|ipad|android/i.test(ua)
}
```

## Resources

**Official Documentation:**

- [Smart ID v3 API Documentation](https://sk-eid.github.io/smart-id-documentation/rp-api/introduction.html)
- [Device Link Flows Guide](https://sk-eid.github.io/smart-id-documentation/rp-api/device_link_flows.html)
- [Notification Flows Guide](https://sk-eid.github.io/smart-id-documentation/rp-api/notification_based_flows.html)

## Testing

Run tests with PHPUnit:

```bash
composer test
```

> [!NOTE]
> Integration tests make real API calls to Smart-ID (DEMO environment).

## Todo

- Figure out why mocking in DEMO doesn't work
- Rewrite tests
- Test App2App and Web2App flows
- More stuff
