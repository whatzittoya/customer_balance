<?php

declare(strict_types=1);

namespace App;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use RuntimeException;
use ZipArchive;

/**
 * Customer QR codes.
 *
 * A QR code opens <base-url>/<slug>/c/<token>. The token carries the customer
 * code plus an HMAC over (client id, code), so a link only works for the
 * client it was made for and nobody can type in another customer's code and
 * see their balance. Changing `app_secret` invalidates every printed code.
 */
final class QrService
{
    public function __construct(private string $secret)
    {
        if (strlen($secret) < 16) {
            throw new RuntimeException('Set a long random app_secret in config/local.php (QR links are signed with it).');
        }
    }

    public function token(int $clientId, string $code): string
    {
        return self::b64url($code) . '.' . $this->signature($clientId, $code);
    }

    /** The customer code inside a valid token, or null when it was tampered with. */
    public function verify(int $clientId, string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        $code = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($code === false || $code === '') {
            return null;
        }

        return hash_equals($this->signature($clientId, $code), $parts[1]) ? $code : null;
    }

    /**
     * PNG bytes. The client logo, when there is one, sits in the middle — error
     * correction is High (30%) so the covered modules still decode.
     */
    public function png(string $url, ?string $logoFile, string $label = ''): string
    {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(600)
            ->margin(24)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin);

        if ($logoFile !== null && is_file($logoFile)) {
            $builder->logoPath($logoFile)
                ->logoResizeToWidth(130)
                ->logoPunchoutBackground(true);
        }
        if ($label !== '') {
            $builder->labelText($label)->labelFont(new OpenSans(20));
        }

        return $builder->build()->getString();
    }

    /**
     * Zip of QR PNGs, one per item, written to a temp file. Returns its path;
     * the caller streams and deletes it.
     *
     * @param array<int,array{filename:string,png:string}> $items
     */
    public function zip(array $items): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is not enabled on this server.');
        }

        $path = tempnam(sys_get_temp_dir(), 'qr');
        $zip  = new ZipArchive();
        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the zip file.');
        }

        $used = [];
        foreach ($items as $item) {
            $name = $item['filename'];
            $n = 2;
            while (isset($used[strtolower($name)])) {
                $name = preg_replace('/\.png$/', '', $item['filename']) . '_' . $n++ . '.png';
            }
            $used[strtolower($name)] = true;
            $zip->addFromString($name, $item['png']);
        }
        $zip->close();

        return $path;
    }

    /** A filesystem-safe PNG name from a customer's code and name. */
    public static function filename(string $code, string $name): string
    {
        // POS codes are often just the name again ("Andy Quinos") — don't repeat it.
        $raw  = strcasecmp(trim($code), trim($name)) === 0 || trim($name) === '' ? $code : $code . '_' . $name;
        $base = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', $raw) ?? '', '-_.');

        return ($base !== '' ? substr($base, 0, 80) : 'customer') . '.png';
    }

    private function signature(int $clientId, string $code): string
    {
        return substr(self::b64url(hash_hmac('sha256', $clientId . '|' . $code, $this->secret, true)), 0, 16);
    }

    private static function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
