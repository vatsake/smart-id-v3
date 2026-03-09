<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests\Contracts;

interface DeviceLinkRequest extends ArrayableRequest
{
    /**
     * Signature protocol, ACSP_V2 or RAW_DIGEST_SIGNATURE
     */
    public function getSignatureProtocol(): string;

    /**
     * Interactions in base64
     */
    public function getInteractions(): string;

    /**
     * RpChallenge (auth) or original data to be signed (sign)
     */
    public function getSignedData(): string;

    /**
     * Session type, auth, sign or cert
     */
    public function getSessionType(): string;

    /**
     * Initial callback URL, set for App2App/Web2App flows
     */
    public function getInitialCallbackUrl(): string;
}
