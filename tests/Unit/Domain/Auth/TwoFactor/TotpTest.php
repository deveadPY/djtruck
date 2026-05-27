<?php

use App\Domain\Auth\TwoFactor\ValueObjects\Totp;

it('generates secret of correct length', function () {
    $s = Totp::generateSecret();
    expect(strlen($s))->toBe(32)
        ->and(preg_match('/^[A-Z2-7]+$/', $s))->toBe(1);
});

it('generates 6-digit code', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $code = Totp::code($secret, 1700000000);
    expect(strlen($code))->toBe(6)
        ->and(ctype_digit($code))->toBeTrue();
});

it('produces same code for same timestamp window', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $t = 1700000000;
    expect(Totp::code($secret, $t))->toBe(Totp::code($secret, $t + 5));
});

it('produces different code for different windows', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    expect(Totp::code($secret, 1700000000))
        ->not->toBe(Totp::code($secret, 1700000031));
});

it('verifies current code', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $code = Totp::code($secret);
    expect(Totp::verify($secret, $code))->toBeTrue();
});

it('verifies previous window code (clock drift)', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $t = 1700000050;
    $codeOld = Totp::code($secret, $t - 30);
    expect(Totp::verify($secret, $codeOld, 1, $t))->toBeTrue();
});

it('rejects code outside window', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    $t = 1700000200;
    $codeOld = Totp::code($secret, $t - 90);
    expect(Totp::verify($secret, $codeOld, 1, $t))->toBeFalse();
});

it('rejects non-numeric code', function () {
    $secret = 'JBSWY3DPEHPK3PXP';
    expect(Totp::verify($secret, 'ABCDEF'))->toBeFalse()
        ->and(Totp::verify($secret, '12345'))->toBeFalse()
        ->and(Totp::verify($secret, '1234567'))->toBeFalse();
});

it('matches RFC 6238 test vector (sha1)', function () {
    // RFC 6238 Appendix B test vector: secret "12345678901234567890" (ASCII)
    // En base32: GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ
    // En T=59 → counter=1 → expected code: "94287082"
    // Pero Google Authenticator usa 6 dígitos, así que esperamos "287082"
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
    expect(Totp::code($secret, 59))->toBe('287082');
});

it('generates valid provisioning URI', function () {
    $uri = Totp::provisioningUri('ABCDEF', 'admin@test.com', 'DJ Trucks');
    expect($uri)->toContain('otpauth://totp/')
        ->and($uri)->toContain('secret=ABCDEF')
        ->and($uri)->toContain('issuer=DJ%20Trucks');
});
