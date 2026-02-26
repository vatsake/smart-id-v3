<?php

use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\Utils\UrlSafe;

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? null;
$qr = ($_GET['qr'] ?? false) === 'true';

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Action is required']);
    exit;
}

$smartClient = new SmartId($config);
$request = buildRequest($action, $qr);
$session = createSession($smartClient, $action, $request);

// Store session and action in session for status polling
$_SESSION['session'] = $session;
$_SESSION['action'] = $action; // This is for Web2App flows so status.php can redirect back with it

echo buildResponse($session, $action, $qr);

function buildRequest(string $action, bool $qr): mixed
{
    switch ($action) {
        case 'notification_sign':
            return buildNotificationSigningRequest();
        case 'notification_auth':
            return buildNotificationAuthRequest();
        case 'device_link_sign':
            return buildDeviceLinkSignRequest($qr);
        case 'device_link_auth':
            return buildDeviceLinkAuthRequest($qr);
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
}

function buildNotificationSigningRequest(): NotificationSigningRequest
{
    return NotificationSigningRequest::builder()
        ->withInteractions('sign text', 'sign text but longer')
        ->withRequestProperties(true)
        ->withData('Hello world', HashAlgorithm::SHA_256)
        ->build();
}

function buildNotificationAuthRequest(): NotificationAuthRequest
{
    return NotificationAuthRequest::builder()
        ->withInteractions('Log in text', 'Log in text but longer')
        ->withRequestProperties(true)
        ->withRpChallenge(RpChallenge::generate(), HashAlgorithm::SHA_256)
        ->build();
}

function buildDeviceLinkSignRequest(bool $qr): DeviceLinkCertChoiceRequest
{
    $req = DeviceLinkCertChoiceRequest::builder();

    if (!$qr) {
        $callbackUrl = getCallbackUrl('device_link_sign');
        $req->withInitialCallbackUrl($callbackUrl);
    }

    return $req->build();
}

function buildDeviceLinkAuthRequest(bool $qr): DeviceLinkAuthRequest
{
    $req = DeviceLinkAuthRequest::builder()
        ->withInteractions('Log in text', 'Log in text but longer')
        ->withRequestProperties(true)
        ->withRpChallenge(RpChallenge::generate(), HashAlgorithm::SHA_256);

    if (!$qr) {
        $callbackUrl = getCallbackUrl('device_link_auth');
        $req->withInitialCallbackUrl($callbackUrl);
    }

    return $req->build();
}

function getCallbackUrl(string $action): string
{
    global $callbackUrlBase;
    $randomBytes = UrlSafe::toUrlSafe(base64_encode(random_bytes(8)));
    $_SESSION['uid'] = $randomBytes; // Store in session for later verification in callback
    return $callbackUrlBase . '?action=' . $action . '&uid=' . $randomBytes;
}

function createSession(SmartId $smartClient, string $action, mixed $request): mixed
{
    $semIdentifier = $_GET['semIdentifier'] ?? null;

    switch ($action) {
        case 'notification_sign':
            $etsi = SemanticsIdentifier::fromString($semIdentifier);
            return $smartClient->notification()->signing()->startEtsi($request, $etsi);
        case 'notification_auth':
            $etsi = SemanticsIdentifier::fromString($semIdentifier);
            return $smartClient->notification()->authentication()->startEtsi($request, $etsi);
        case 'device_link_sign':
            return $smartClient->deviceLink()->signing()->startAnonymousCertChoice($request);
        case 'device_link_auth':
            return $smartClient->deviceLink()->authentication()->startAnonymous($request);
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
}

function buildResponse(mixed $session, string $action, bool $qr): string
{
    $response = ['status' => 'ok'];

    // For Web2App flow include link
    if (!$qr && str_starts_with($action, 'device_link')) {
        $response['link'] = $session->getDeviceLink(DeviceLinkType::WEB_TO_APP);
    }

    return json_encode($response);
}
