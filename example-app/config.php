<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Constants\SmartIdBaseUrl;


//$log = new Logger('name');
//$log->pushHandler(new StreamHandler(__DIR__ . '/log.log'));

$config = new SmartIdConfig(
    baseUrl: SmartIdBaseUrl::DEMO,
    certificatePath: __DIR__ . '/tests', // Path must exist
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    relyingPartyName: 'DEMO',
    //baseUrl: SmartIdBaseUrl::PROD,
    //relyingPartyUUID: '',
    //relyingPartyName: '',
    //logger: $log,
);
