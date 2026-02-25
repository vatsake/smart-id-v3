<?php

use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Utils\RpChallenge;

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
session_start();

$smartClient = new SmartId($config);

$action = $_GET['action'];

switch ($action) {
    case 'notification_sign':
        $semIdentifierString = $_GET['semIdentifier'];
        $req = NotificationSigningRequest::builder()
            ->withInteractions('sign text', 'sign text but longer')
            ->withRequestProperties(true)
            ->withData('Hello world', HashAlgorithm::SHA_256)
            ->build();
        $etsi = SemanticsIdentifier::fromString($semIdentifierString);
        $session = $smartClient->notification()->signing()->startEtsi($req, $etsi);
        break;
    case 'notification_auth':
        $semIdentifierString = $_GET['semIdentifier'];
        $req = NotificationAuthRequest::builder()
            ->withInteractions('Log in text', 'Log in text but longer')
            ->withRequestProperties(true)
            ->withRpChallenge(RpChallenge::generate(), HashAlgorithm::SHA_256)
            ->build();
        $etsi = SemanticsIdentifier::fromString($semIdentifierString);
        $session = $smartClient->notification()->authentication()->startEtsi($req, $etsi);
        break;
    case 'device_link_sign':
        $req = DeviceLinkCertChoiceRequest::builder()->build();
        $session = $smartClient->deviceLink()->signing()->startAnonymousCertChoice($req);
        break;
    case 'device_link_auth':
        $req = DeviceLinkAuthRequest::builder()
            ->withInteractions('Log in text', 'Log in text but longer')
            ->withRequestProperties(true)
            ->withRpChallenge(RpChallenge::generate(), HashAlgorithm::SHA_256)
            ->build();
        $session = $smartClient->deviceLink()->authentication()->startAnonymous($req);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}
$_SESSION['session'] = $session;
$_SESSION['action'] = $action;
echo json_encode(['status' => 'ok']);
