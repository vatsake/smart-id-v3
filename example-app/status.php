<?php

use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\SmartId;

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

session_start();
/** @var Vatsake\SmartIdV3\Features\SessionContract */
$session = $_SESSION['session'];
$action = $_SESSION['action'];
session_write_close();

$smartClient = new SmartId($config);

switch ($action) {
    case 'notification_sign':
        $res = $smartClient->session($session)->getSigningSession(10000);
        if (!$res->isComplete()) {
            echo json_encode(['state' => $res->state->value]);
            exit;
        }
        break;
    case 'notification_auth':
        $res = $smartClient->session($session)->getAuthSession(10000);
        if (!$res->isComplete()) {
            echo json_encode(['state' => $res->state->value]);
            exit;
        }
        break;
    case 'device_link_sign':
        $res = $smartClient->session($session)->getCertChoiceSession(10000);
        if (!$res->isComplete()) {
            echo json_encode(['state' => $res->state->value]);
            exit;
        }
        if (!$res->isSuccessful()) {
            break;
        }
        $req = LinkedRequest::builder()
            ->withData('Hello world', HashAlgorithm::SHA_256)
            ->withInteractions('Sign text', 'Sign text but longer')
            ->withLinkedSessionId($session->getSessionId())
            ->build();
        $resp = $smartClient->notification()->signing()->startLinkedSigning($req, $res->documentNumber);
        $res = $smartClient->session($resp)->withPolling(500)->getSigningSession(10000);
        if (!$res->isComplete()) {
            echo json_encode(['state' => $res->state->value]);
            exit;
        }
        break;
    case 'device_link_auth':
        $res = $smartClient->session($session)->getAuthSession(10000);
        if (!$res->isComplete()) {
            echo json_encode(['state' => $res->state->value]);
            exit;
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

if ($res->isSuccessful()) {
    try {
        $res->validate()
            ->withCertificateValidation(false) // Only works if trusted certificates are set
            ->withRevocationValidation(false) // Only works if trusted certificates are set
            ->withSignatureValidation(true)
            ->check();
        $verificationResult = true;
    } catch (ValidationException $e) {
        $verificationResult = false;
    }
}


echo json_encode([
    'state' => $res->state->value,
    'endResult' => $res->endResult?->value,
    'signer' => $res->isSuccessful() ?  $res->certificate->getSubjectName() : null,
    'verificationResult' => $verificationResult ?? null
]);
