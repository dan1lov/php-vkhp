<?php
namespace VKHP;


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
