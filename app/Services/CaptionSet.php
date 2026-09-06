<?php

namespace App\Services;

/**
 * Caption berserta asal-usulnya.
 *
 * Peniaga mesti tahu bila AI tidak berjalan. Versi pertama jatuh ke acuan asas
 * secara senyap, jadi caption nampak lemah dan tiada siapa tahu sebabnya —
 * kunci API yang tidak diisi kelihatan sama seperti AI yang menulis dengan
 * teruk.
 */
readonly class CaptionSet
{
    /** @param  array<int, string>  $captions */
    public function __construct(
        public array $captions,
        public bool $fromFallback = false,
        public ?string $reason = null,
    ) {}

    public static function fromAi(array $captions): self
    {
        return new self($captions);
    }

    public static function fromFallback(array $captions, string $reason): self
    {
        return new self($captions, true, $reason);
    }
}
