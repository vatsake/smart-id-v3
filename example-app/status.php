<?php

use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\SmartId;

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

session_start();

/** @var Vatsake\SmartIdV3\Features\SessionContract */
$session = $_SESSION['session'];
$action = $_SESSION['action'];
$isCallback = isset($callback);

session_write_close();

$smartClient = new SmartId($config);

$res = getSessionResponse($smartClient, $session, $action);

// If not complete and not a callback request, return state (polling)
if (!$res->isComplete() && !$isCallback) {
    echo json_encode(['state' => $res->state->value]);
    exit;
}

$verificationResult = null;

if ($res->isSuccessful()) {
    $verificationResult = validateResponse($res, $action);
}

$responseData = [
    'state' => $res->state->value,
    'endResult' => $res->endResult?->value,
    'signer' => $res->isSuccessful() ? $res->certificate->getSubjectName() : null,
    'verificationResult' => $verificationResult
];

// Redirect with results if callback, otherwise JSON
if ($isCallback) {
    redirect('/index.php', $action, $responseData);
} else {
    echo json_encode($responseData);
}

function getSessionResponse(SmartId $smartClient, mixed $session, string $action): mixed
{
    switch ($action) {
        case 'notification_sign':
            return $smartClient->session($session)->getSigningSession(10000);
        case 'notification_auth':
            return $smartClient->session($session)->getAuthSession(10000);
        case 'device_link_sign':
            return handleDeviceLinkSign($smartClient, $session);
        case 'device_link_auth':
            return $smartClient->session($session)->getAuthSession(10000);
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
}

function handleDeviceLinkSign(SmartId $smartClient, mixed $session): mixed
{
    $res = $smartClient->session($session)->getCertChoiceSession(10000);

    if (!$res->isComplete() || !$res->isSuccessful()) {
        return $res;
    }

    $req = LinkedRequest::builder()
        ->withData('Hello world', HashAlgorithm::SHA_256)
        ->withInteractions('Sign text', 'Sign text but longer')
        ->withLinkedSessionId($session->getSessionId())
        ->build();

    $resp = $smartClient->notification()->signing()->startLinkedSigning($req, $res->documentNumber);
    return $smartClient->session($resp)->withPolling(500)->getSigningSession(10000);
}

function validateResponse(SigningSession|AuthSession $res, string $action): bool
{
    try {
        $builder = $res->validate()
            ->withCertificateValidation(false)
            ->withRevocationValidation(false)
            ->withSignatureValidation(true);

        if (isset($callback)) { // callback.php sets this
            $builder->withCallbackUrlValidationParameters(true, $_GET['sessionSecretDigest'], $_GET['userChallengeVerifier'], $_SESSION['uid'], $_GET['uid'] ?? null);
            // Or
            //$builder->withCallbackUrlValidation(true, $_SERVER['REQUEST_URI'], 'uid', $_SESSION['uid']);
        }

        $builder->check();
        return true;
    } catch (ValidationException $e) {
        return false;
    }
}

function redirect(string $url, string $action, array $data): void
{
    $redirectUrl = $url . '?action=' . urlencode($action);

    if ($data['signer']) {
        $redirectUrl .= '&signer=' . urlencode($data['signer']);
    }
    if ($data['endResult']) {
        $redirectUrl .= '&endResult=' . urlencode($data['endResult']);
    }
    if ($data['verificationResult'] !== null) {
        $redirectUrl .= '&verificationResult=' . ($data['verificationResult'] ? 'true' : 'false');
    }

    header('Location: ' . $redirectUrl);
    exit;
}
