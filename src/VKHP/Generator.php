<?php
namespace VKHP;


class Generator
{
    const WHITE = 'secondary';
    const BLUE = 'primary';
    const GREEN = 'positive';
    const RED = 'negative';


    public static function keyboard(
        array $buttons,
        bool $one_time = false,
        bool $inline_mode = false
    ): string {
        return json_encode([
            'one_time' => $one_time,
            'inline' => $inline_mode,
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
