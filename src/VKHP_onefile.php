<?php
namespace VKHP;


class Method
{
    private static $version = '5.103';


    public static function make(
        ?string $access_token,
        string $method,
        array $params
    ): object {
        $methodUrl = "https://api.vk.com/method/{$method}";
        $params = $params + [ 'access_token' => $access_token, 'v' => self::$version ];

        $request = \VKHP\Request::makeJson($methodUrl, $params);
        $request->ok = isset($request->response);
        return $request;
    }

    public static function messagesSend(string $access_token, array $params): object
    {
        $user_ids = $params['user_ids'] ?? null;
        if (empty($user_ids)) {
            throw new \Exception('field `user_ids` is empty');
        }


        $params['random_id'] = $params['random_id'] ?? 0;
        $user_ids = is_array($user_ids) ? $user_ids : explode(',', $user_ids);
        $user_ids = array_unique(array_filter($user_ids));
        $users_count = count($user_ids);

        [$res, $suc] = [[], 0];
        // j -- количество идов, которые будут взяты
        for ($i = 0, $j = 100, $c = ceil($users_count / 100); $i < $c; $i++) {
            $user_ids_str = implode(',', array_slice($user_ids, $i * $j, $j));
            $params['user_ids'] = $user_ids_str;

            $req = self::make($access_token, 'messages.send', $params);
            foreach ($req->response as $message) {
                if (isset($message->error)) {continue;}

                $res[] = $message;
                $suc += 1;
            }
        }
        return (object) [ 'successful' => $suc, 'response' => $res ];
    }
}

class Request
{
    public static function make(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): string {
        $ch = curl_init();
        $ch_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            CURLOPT_HTTPHEADER => $headers ?? [],
            CURLOPT_POST => $fields !== null,
            CURLOPT_POSTFIELDS => is_array($fields) && $headers === null ? http_build_query($fields) : $fields,
        ] + (array) $options;
        curl_setopt_array($ch, $ch_options);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function makeJson(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): object {
        $request = self::make($url, $fields, $headers, $options);
        if (empty($request)) {
            throw new \Exception('response is empty, it is impossible execute json_decode');
        }

        return json_decode($request);
    }
}

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
