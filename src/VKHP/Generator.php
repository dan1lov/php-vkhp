<?php
namespace VKHP;

/**
 * Class for generating keyboard and any type buttons
 */
class Generator
{
    /**
     * @var string
     */
    const WHITE = 'secondary';

    /**
     * @var string
     */
    const BLUE = 'primary';

    /**
     * @var string
     */
    const GREEN = 'positive';

    /**
     * @var string
     */
    const RED = 'negative';

    /**
     * @var int
     */
    const KM_ONETIME = 1 << 0;

    /**
     * @var int
     */
    const KM_INLINE = 1 << 1;

    /**
     * Generating keyboard
     *
     * @param array   $buttons Array of buttons
     * @param integer $mode    Keyboard mode
     *
     * @return string
     */
    public static function keyboard(array $buttons, int $mode = 0): string
    {
        return json_encode(
            [
                'one_time' => (bool) ($mode & self::KM_ONETIME),
                'inline' => (bool) ($mode & self::KM_INLINE),
                'buttons' => $buttons
            ]
        );
    }

    /**
     * Generate button with type text
     *
     * @param string     $label   Button label
     * @param string     $color   Button color
     * @param array|null $payload Button payload
     * @param string     $type    Button type (text|callback)
     *
     * @return array
     */
    public static function button(
        string $label,
        string $color = self::WHITE,
        ?array $payload = null,
        string $type = 'text'
    ): array {
        return [
            'action' => [
                'type' => $type,
                'label' => $label,
                'payload' => self::payloadEncode($payload)
            ],
            'color' => $color
        ];
    }

    /**
     * Generate button with type open_link
     *
     * @param string $label Button label
     * @param string $link  Link in button
     *
     * @return array
     */
    public static function buttonLink(string $label, string $link): array
    {
        return [
            'action' => [
                'type' => 'open_link',
                'link' => $link,
                'label' => $label
            ]
        ];
    }

    /**
     * Generate button with type location
     *
     * @param array|null $payload Button payload
     *
     * @return array
     */
    public static function buttonLocation(?array $payload = null): array
    {
        return [
            'action' => [
                'type' => 'location',
                'payload' => self::payloadEncode($payload)
            ]
        ];
    }

    /**
     * Generate button with type vkpay
     *
     * @param string $hash Hash for button
     *
     * @return array
     */
    public static function buttonVKPay(string $hash): array
    {
        return [
            'action' => [
                'type' => 'vkpay',
                'hash' => $hash
            ]
        ];
    }

    /**
     * Generate button with type open_app
     *
     * @param string  $label    Button label
     * @param integer $app_id   Application id
     * @param integer $owner_id Owner id
     * @param string  $hash     Hash for button
     *
     * @return array
     */
    public static function buttonVKApps(
        string $label,
        int $app_id,
        int $owner_id = 0,
        string $hash = ''
    ): array {
        return [
            'action' => [
                'type' => 'open_app',
                'app_id' => $app_id,
                'owner_id' => $owner_id,
                'label' => $label,
                'hash' => $hash
            ]
        ];
    }

    /**
     * @see self::button
     */
    public static function buttonCallback(
        string $label,
        string $color = self::WHITE,
        ?array $payload = null
    ): array {
        return self::button($label, $color, $payload, 'callback');
    }

    /**
     * Encode payload
     *
     * @param array|null $payload Payload
     *
     * @return string
     */
    protected static function payloadEncode(?array $payload): string
    {
        return $payload === null ? '' : json_encode($payload);
    }
}
