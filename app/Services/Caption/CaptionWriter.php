<?php

namespace App\Services\Caption;

interface CaptionWriter
{
    /**
     * Hantar prompt, pulangkan teks mentah model.
     *
     * Prompt, penghuraian JSON dan caption ganti semuanya duduk dalam
     * CaptionService — hanya panggilan HTTP yang berbeza antara pembekal.
     * Jadi menukar pembekal tidak menukar kualiti copy.
     */
    public function write(string $systemPrompt, string $userPrompt): string;

    /** Nama untuk dipapar kepada peniaga bila sesuatu tidak kena. */
    public function name(): string;

    /** Adakah kunci API sudah diisi? */
    public function configured(): bool;

    /** Nama pemboleh ubah .env untuk kunci — dipakai dalam mesej ralat. */
    public function keyName(): string;
}
