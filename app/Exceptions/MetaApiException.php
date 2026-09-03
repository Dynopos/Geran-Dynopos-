<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ralat dari Graph API.
 *
 * Peraturan mutlak #8: mesej ralat ini TIDAK PERNAH memuatkan access token.
 * Payload yang dilampirkan sudah ditapis dalam MetaAdsService sebelum sampai sini.
 */
class MetaApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $errorCode = null,
        public readonly ?int $errorSubcode = null,
        public readonly ?string $userMessage = null,
        public readonly ?string $endpoint = null,
    ) {
        parent::__construct($message);
    }

    /** Ayat untuk dipapar kepada peniaga — utamakan error_user_msg dari Meta. */
    public function forHuman(): string
    {
        return $this->userMessage ?: $this->getMessage();
    }

    public function isCallToActionError(): bool
    {
        return in_array($this->errorSubcode, [1885183, 1487888], true)
            || str_contains(mb_strtolower($this->getMessage()), 'call_to_action');
    }
}
