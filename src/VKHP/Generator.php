<?php
namespace VKHP;


class Generator
{
    const WHITE = 'secondary';
    const BLUE = 'primary';
    const GREEN = 'positive';
    const RED = 'negative';

    // keyboard-mode
    const KM_ONETIME = 1 << 0; // one_time
    const KM_INLINE = 1 << 1; // inline


    public static function keyboard(
        array $buttons,
        int $mode = 0
    ): string {
        return json_encode([
            'one_time' => (bool) ($mode & self::KM_ONETIME),
            'inline' => (bool) ($mode & self::KM_INLINE),
            'buttons' => $buttons
        ]);
    }

    public static function button(
        string $label,
        string $color = self::WHITE,
        ?array $payload = null
    ): array {
        return [
            'action' => [
                'type' => 'text',
                'label' => $label,
                'payload' => self::jEncode($payload)
            ],
            'color' => $color
        ];
    }

    public static function buttonLocation(array $payload): array
    {
        return [
            'action' => [
                'type' => 'location',
                'payload' => self::jEncode($payload)
            ]
        ];
    }

    public static function buttonVKPay(string $hash, array $payload): array
    {
        return [
            'action' => [
                'type' => 'vkpay',
                'hash' => $hash,
                'payload' => self::jEncode($payload)
            ]
        ];
    }

    public static function buttonVKApps(
        string $label,
        int $app_id,
        int $owner_id,
        string $hash,
        array $payload
    ): array {
        return [
            'action' => [
                'type' => 'open_app',
                'label' => $label,
                'app_id' => $app_id,
                'owner_id' => $owner_id,
                'hash' => $hash,
                'payload' => self::jEncode($payload)
            ]
        ];
    }


    private static function jEncode($payload)
    {
        return $payload === null ? $payload : json_encode($payload);
    }
}
