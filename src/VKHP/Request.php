<?php
namespace VKHP;

/**
 * Class for making curl requests
 */
class Request
{
    /**
     * Make curl request
     *
     * @param string     $url     URL
     * @param array|null $fields  Post fields
     * @param array|null $headers Headers
     * @param array|null $options Additional options
     *
     * @return string
     */
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
            CURLOPT_USERAGENT => 'php-vkhp library',
            CURLOPT_HTTPHEADER => $headers ?? [],
        ] + ($options ?? []);
        curl_setopt_array($ch, $ch_options);

        if (! empty($fields)) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => !$headers
                    ? http_build_query($fields) : $fields
            ]);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * Same as make(), but returned value goes through json_decode
     *
     * @param string     $url     URL
     * @param array|null $fields  Post fields
     * @param array|null $headers Headers
     * @param array|null $options Additional options
     *
     * @throws Exception if esponse is empty or cannot be decoded
     *
     * @return object
     */
    public static function makeJson(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): object {
        $request = self::make($url, $fields, $headers, $options);
        $decoded = json_decode($request);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('response is empty or cannot be decoded');
        }

        return $decoded;
    }
}
