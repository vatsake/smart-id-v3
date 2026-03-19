<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SmartIdEnv;

//$log = new Logger('name');
//$log->pushHandler(new StreamHandler(__DIR__ . '/log.log'));

$filesystemAdapter = new Local(__DIR__);
$filesystem        = new Filesystem($filesystemAdapter);

$pool = new FilesystemCachePool($filesystem);

$config = new SmartIdConfig(
    env: SmartIdEnv::DEMO,
    certificatePath: __DIR__, // Path must exist, even if it doesn't contain certificates
    relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
    relyingPartyName: 'DEMO',
    //env: SmartIdEnv::PROD,
    //relyingPartyUUID: '',
    //relyingPartyName: '',
    //logger: $log,
    cache: $pool,
);

$callbackUrlBase = 'https://localhost/callback.php'; // Set this
