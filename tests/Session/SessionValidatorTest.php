<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\InteractionType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Exceptions\HttpException;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Utils\PemFormatter;
use Vatsake\SmartIdV3\Validators\SmartIdCertificateValidator;
use Vatsake\SmartIdV3\Factories\SessionFactory;

class SessionValidatorTest extends TestCase
{
    private SigningSession $session;
    private SmartIdConfig $config;
    private SessionValidatorBuilder $builder;

    private const DEMO_SIGNING_CERTIFICATE_PATH = __DIR__ . '/../Resources/PNOEE-40504040001-DEM0-Q.cer';
    private const DEMO_AUTH_CERTIFICATE_PATH = __DIR__ . '/../Resources/PNOEE-40504040001-DEM0-Q-AUTH.cer';
    private const DEMO_TRUSTED_CERTIFICATES_PATH = __DIR__ . '/../Resources/trusted-mixed-certs';
    private const DEMO_INT_TRUSTED_CERTIFICATES_PATH = __DIR__ . '/../Resources/trusted-int-certs';
    private const DEMO_CA_TRUSTED_CERTIFICATES_PATH = __DIR__ . '/../Resources/trusted-ca-certs';
    private string $demoX509;

    private static ?string $bundleDir = null;

    // This will clear cache
    public static function setUpBeforeClass(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: self::DEMO_TRUSTED_CERTIFICATES_PATH,
        );

        $ref = new ReflectionClass(SmartIdCertificateValidator::class);
        $instance = new SmartIdCertificateValidator($config);
        $property = $ref->getProperty('caBundlePath');
        $caBundlePath = $property->getValue($instance);

        self::$bundleDir = dirname($caBundlePath);
    }

    protected function setUp(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $this->config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: self::DEMO_TRUSTED_CERTIFICATES_PATH,
            httpClient: $client
        );

        $this->demoX509 = file_get_contents(self::DEMO_SIGNING_CERTIFICATE_PATH);

        $notifSession = new NotificationSession(
            '1',
            'hello world',
            'test',
            ''
        );

        $sessionData = [
            'state' => SessionState::COMPLETE->value,
            'result' => [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => '1234567890'
            ],
            'signatureProtocol' => SignatureProtocol::RAW_DIGEST_SIGNATURE->value,
            'signature' => [
                'value' => base64_encode(random_bytes(24)),
                'flowType' => FlowType::QR->value,
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => HashAlgorithm::SHA_256->value,
                    'maskGenAlgorithm' => [
                        'algorithm' => 'test',
                        'parameters' => [
                            'hashAlgorithm' => HashAlgorithm::SHA_256->value
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc',
                ]
            ],
            'cert' => [
                'value' => PemFormatter::stripPemHeaders($this->demoX509),
                'certificateLevel' => 'QUALIFIED'
            ],
            'interactionTypeUsed' => InteractionType::CONFIRMATION_MESSAGE->value,
            'deviceIp' => null,
            'ignoredProperties' => null
        ];

        $this->session = SessionFactory::createSigningSession($sessionData, $notifSession, $this->config);
        $this->builder = new SessionValidatorBuilder($this->session, $this->config);
    }

    public function testMixedBundleBuilding(): void
    {
        if (is_dir(self::$bundleDir)) {
            foreach (glob(self::$bundleDir . '/*') as $file) {
                unlink($file);
            }
            rmdir(self::$bundleDir);
        }
        $builder = $this->builder
            ->withSignatureValidation(false)
            ->withCertificateValidation(true);

        $this->expectNotToPerformAssertions();
        $builder->check();
    }

    public function testDirectPathBundleBuilding(): void
    {
        if (is_dir(self::$bundleDir)) {
            foreach (glob(self::$bundleDir . '/*') as $file) {
                unlink($file);
            }
            rmdir(self::$bundleDir);
        }

        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            caPath: self::DEMO_CA_TRUSTED_CERTIFICATES_PATH,
            intPath: self::DEMO_INT_TRUSTED_CERTIFICATES_PATH,
        );

        $builder = new SessionValidatorBuilder($this->session, $config);
        $builder->withSignatureValidation(false)->withCertificateValidation(true);

        $this->expectNotToPerformAssertions();
        $builder->check();
    }

    public function testCheckWithAllValidationsDisabled(): void
    {
        $builder = $this->builder
            ->withSignatureValidation(false)
            ->withCertificateValidation(false)
            ->withRevocationValidation(false);

        $this->expectNotToPerformAssertions();
        $builder->check();
    }

    public function testValidateSignatureThrowsExceptionWithInvalidSignature(): void
    {
        $builder = new SessionValidatorBuilder($this->session, $this->config);
        $builder->withSignatureValidation(true);

        $this->expectException(SignatureException::class);
        $builder->check();
    }

    public function testValidateAuthSessionSignatureThrowsExceptionWithInvalidSignature(): void
    {
        $notifSession = new NotificationSession(
            '1',
            'hello world',
            'test',
            ''
        );

        $authCert = file_get_contents(self::DEMO_AUTH_CERTIFICATE_PATH);

        $authSessionData = [
            'state' => SessionState::COMPLETE->value,
            'result' => [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => '1234567890'
            ],
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signature' => [
                'value' => base64_encode(random_bytes(24)),
                'serverRandom' => 'server-random-value',
                'userChallenge' => 'user-challenge-value',
                'flowType' => FlowType::QR->value,
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => HashAlgorithm::SHA_256->value,
                    'maskGenAlgorithm' => [
                        'algorithm' => 'test',
                        'parameters' => [
                            'hashAlgorithm' => HashAlgorithm::SHA_256->value
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc',
                ]
            ],
            'cert' => [
                'value' => PemFormatter::stripPemHeaders($authCert),
                'certificateLevel' => 'QUALIFIED'
            ],
            'interactionTypeUsed' => InteractionType::CONFIRMATION_MESSAGE->value,
            'deviceIp' => null,
            'ignoredProperties' => null
        ];

        $authSession = \Vatsake\SmartIdV3\Factories\SessionFactory::createAuthSession($authSessionData, $notifSession, $this->config);

        $builder = new SessionValidatorBuilder($authSession, $this->config);
        $builder->withSignatureValidation(true);

        $this->expectException(SignatureException::class);
        $builder->check();
    }

    public function testValidateSmartIdCertificate(): void
    {
        $builder = new SessionValidatorBuilder($this->session, $this->config);
        $builder->withSignatureValidation(false)->withCertificateValidation();

        $this->expectNotToPerformAssertions();
        $builder->check();
    }

    public function testValidateAuthSessionCertificate(): void
    {
        $notifSession = new NotificationSession(
            '1',
            'hello world',
            'test',
            ''
        );

        $authCert = file_get_contents(self::DEMO_AUTH_CERTIFICATE_PATH);

        $authSessionData = [
            'state' => SessionState::COMPLETE->value,
            'result' => [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => '1234567890'
            ],
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signature' => [
                'value' => base64_encode(random_bytes(24)),
                'serverRandom' => 'server-random-value',
                'userChallenge' => 'user-challenge-value',
                'flowType' => FlowType::QR->value,
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => HashAlgorithm::SHA_256->value,
                    'maskGenAlgorithm' => [
                        'algorithm' => 'test',
                        'parameters' => [
                            'hashAlgorithm' => HashAlgorithm::SHA_256->value
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc',
                ]
            ],
            'cert' => [
                'value' => PemFormatter::stripPemHeaders($authCert),
                'certificateLevel' => 'QUALIFIED'
            ],
            'interactionTypeUsed' => InteractionType::CONFIRMATION_MESSAGE->value,
            'deviceIp' => null,
            'ignoredProperties' => null
        ];

        $authSession = \Vatsake\SmartIdV3\Factories\SessionFactory::createAuthSession($authSessionData, $notifSession, $this->config);

        $builder = new SessionValidatorBuilder($authSession, $this->config);
        $builder->withSignatureValidation(false)->withCertificateValidation();

        $this->expectNotToPerformAssertions();
        $builder->check();
    }

    public function testValidateRevocation(): void
    {
        $builder = new SessionValidatorBuilder($this->session, $this->config);
        $builder->withSignatureValidation(false)->withRevocationValidation();

        $this->expectException(HttpException::class);
        $builder->check();
    }
}
