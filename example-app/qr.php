<?php

use Vatsake\SmartIdV3\Enums\DeviceLinkType;

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

session_start();

/** @var \Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession */
$session = $_SESSION['session'];

echo json_encode(['link' => $session->getDeviceLink(DeviceLinkType::QR)]);
