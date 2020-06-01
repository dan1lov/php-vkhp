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
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            CURLOPT_HTTPHEADER => $headers ?? [],
            CURLOPT_POST => $fields !== null,
        ] + (array) $options;
        if ($ch_options[CURLOPT_POST]) {
            $ch_options[CURLOPT_POSTFIELDS] = !$headers ? http_build_query($fields) : $fields;
        }
        curl_setopt_array($ch, $ch_options);

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
