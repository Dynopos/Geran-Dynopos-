<?php

use App\Livewire\Posters\Create;
use App\Support\Uploads;

/**
 * Peraturan validasi yang lebih longgar daripada php.ini adalah janji yang app
 * tidak boleh tunaikan. PHP menolak fail sebelum Laravel melihatnya, dan
 * peniaga dapat "The product failed to upload." — mesej Inggeris mentah yang
 * tidak memberitahu apa-apa.
 */
it('membaca had sebenar pelayan, bukan nombor yang kita reka', function () {
    // Yang paling ketat antara dua tetapan PHP yang menentukan.
    $expected = min(
        (int) round(ini_parse_quantity(ini_get('upload_max_filesize')) / 1024),
        (int) round(ini_parse_quantity(ini_get('post_max_size')) / 1024),
    );

    expect(Uploads::maxKilobytes(1_000_000))->toBe($expected);
});

it('tidak pernah menjanjikan lebih daripada siling yang diminta', function () {
    expect(Uploads::maxKilobytes(1))->toBe(1);
});

it('memaparkan had dalam bentuk yang peniaga faham', function () {
    expect(Uploads::maxLabel())->toMatch('/^[\d.]+ (KB|MB)$/');
});

it('mengesan bila had terlalu ketat untuk gambar telefon', function () {
    // Gambar telefon biasa 3-8 MB. Apa-apa di bawah 4 MB akan menggagalkan
    // kebanyakan muat naik, jadi peniaga patut diberitahu sebelum mencuba.
    expect(Uploads::tooTightForPhonePhotos())->toBe(Uploads::maxKilobytes() < 4096);
});

it('had validasi tidak pernah melebihi had PHP', function () {
    $rules = (new ReflectionMethod(Create::class, 'rules'))
        ->invoke(app(Create::class));

    preg_match('/max:(\d+)/', $rules['product'], $m);

    expect((int) $m[1])->toBeLessThanOrEqual(Uploads::maxKilobytes());
});

it('kegagalan muat naik dijelaskan dalam Bahasa Melayu', function () {
    $messages = (new ReflectionMethod(Create::class, 'messages'))
        ->invoke(app(Create::class));

    expect($messages)->toHaveKey('product.uploaded')
        ->and($messages['product.uploaded'])
        ->toContain('gagal dimuat naik')
        ->toContain(Uploads::maxLabel())
        ->not->toContain('failed to upload');
});
