<?php
namespace VKHP;


/**
 * Class for making queries to VK API
 */
class Method
{
    /**
     * @var string
     */
    private static $version = '5.107';

    /**
     * Make query to VK API
     *
     * @param string $access_token Access token
     * @param string $method       Method name
     * @param array  $params       Parameters for method
     * 
     * @return object
     */
    public static function make(
        string $access_token,
        string $method,
        array $params
    ): object {
        $methodUrl = "https://api.vk.com/method/{$method}";
        $params = $params + [ 'access_token' => $access_token, 'v' => self::$version ];

        $request = \VKHP\Request::makeJson($methodUrl, $params);
        $request->ok = isset($request->response);
        return $request;
    }

    /**
     * Sending message to community users
     *
     * @param string $access_token Access token
     * @param array  $params       Parameters for messages.send method
     *
     * @return object
     */
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
        for ($i = 0, $j = 100, $c = ceil($users_count / $j); $i < $c; $i++) {
            $user_ids_str = implode(',', array_slice($user_ids, $i * $j, $j));
            $params['user_ids'] = $user_ids_str;

            $req = self::make($access_token, 'messages.send', $params);
            if ($req->ok === false) { return $req; }
            foreach ($req->response as $message) {
                if (isset($message->error)) {continue;}

                $res[] = $message;
                $suc += 1;
            }
        }
        return (object) [ 'successful' => $suc, 'response' => $res ];
    }

    /**
     * Uploading photos to VK
     *
     * @param string $access_token Access token
     * @param array  $files        Files to upload
     * @param array  $params       Parameters for uploading method
     *
     * @return array
     */
    public static function uploadMessagesPhoto(
        string $access_token,
        array $files,
        array $params
    ): array {
        if (empty($files)) { return []; }
        if (empty($params['peer_id'])) {
            throw new \Exception('field `peer_id` is empty');
        }
        if (count($files) > 5) {
            throw new \Exception('too much files (>5)');
        }


        $gurl = self::make($access_token, 'photos.getMessagesUploadServer', $params);
        if ($gurl->ok === false) { return (array) $gurl; }

        $saved_files = self::saveFiles($files);
        $upload_files = \VKHP\Request::makeJson($gurl->response->upload_url,
            $saved_files['cfiles'], [ 'Content-type: multipart/form-data;charset=utf-8' ]);
        self::deleteFiles($saved_files['paths']);
        if (isset($upload_files->error)) { return (array) $upload_files; }

        $save_files = self::make($access_token, 'photos.saveMessagesPhoto', [
            'server' => $upload_files->server,
            'photo' => $upload_files->photo,
            'hash' => $upload_files->hash
        ] + $params);
        if ($save_files->ok === false) { return (array) $save_files; }

        $attachment = [];
        foreach ($save_files->response as $photo) {
            $attachment[] = "photo{$photo->owner_id}_{$photo->id}";
        }
        return $attachment;
    }

    /**
     * Uploading documents to VK
     *
     * @param string $access_token Access token
     * @param array  $files        Files to upload
     * @param array  $params       Parameters for uploading method
     *
     * @return array
     */
    public static function uploadMessagesDoc(
        string $access_token,
        array $files,
        array $params
    ): array {
        $required_fields = [ 'peer_id', 'type' ];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                throw new \Exception("field `{$field}` is required");
            }
        }

        $gurl = self::make($access_token, 'docs.getMessagesUploadServer', $params);
        if ($gurl->ok === false) { return (array) $gurl; }

        $attachment = [];
        foreach ($files as $file) {
            $saved_file = self::saveFiles([ $file ], true);
            $upload_file = \VKHP\Request::makeJson($gurl->response->upload_url,
                $saved_file['cfiles'], [ 'Content-type: multipart/form-data;charset=utf-8' ]);
            self::deleteFiles($saved_file['paths']);
            if (isset($upload_file->error)) { return (array) $upload_file; }

            $save_file = self::make($access_token, 'docs.save', [
                'file' => $upload_file->file
            ] + $params);
            if ($save_file->ok === false) { return (array) $save_files; }
            if (array_key_exists(0, $save_file->response)) {
                $save_file->response = (object) [ $params['type'] => $save_file->response[0] ];
            }

            $file = $save_file->response->{$params['type']};
            $attachment[] = "doc{$file->owner_id}_{$file->id}";
        }
        return $attachment;
    }

    /**
     * Saving files in temporary folder
     *
     * @param array   $files  Files to saving
     * @param boolean $single Flag for single uploading
     *
     * @return array
     */
    private static function saveFiles(array $files, bool $single = false): array
    {
        [$paths, $cfiles, $i] = [[], [], 1];
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            if (! file_exists($file)) {
                $paths[] = $fpath = tempnam(sys_get_temp_dir(), 'VKHP');
                if (($contents = file_get_contents($file)) === false) {
                    throw new \Exception("can't retrieve file contents for path '{$file}'");
                }

                file_put_contents($fpath, $contents);
            } else { $fpath = realpath($file); }

            $mime_type = mime_content_type($fpath);
            $cfile = new \CURLFile($fpath, $mime_type, $pathinfo['basename']);

            $cfkey = $single ? 'file' : ('file' . $i++);
            $cfiles[$cfkey] = $cfile;
            if ($single) {break;}
        }
        return [ 'paths' => $paths, 'cfiles' => $cfiles ];
    }

    /**
     * Delete files from paths in $paths array
     *
     * @param array $paths Array of paths to deleting
     *
     * @return void
     */
    private static function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

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
            CURLOPT_POSTFIELDS => is_array($fields) && $headers === null ? http_build_query($fields) : $fields,
        ] + (array) $options;
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
     * @return object
     */
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

    /**
     * Generate button with type text
     *
     * @param string     $label   Button label
     * @param string     $color   Button color
     * @param array|null $payload Button payload
     *
     * @return array
     */
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

    /**
     * Generate button with type location
     *
     * @param array $payload Button payload
     *
     * @return array
     */
    public static function buttonLocation(array $payload): array
    {
        return [
            'action' => [
                'type' => 'location',
                'payload' => self::jEncode($payload)
            ]
        ];
    }

    /**
     * Generate button with type vkpay
     *
     * @param string $hash    Hash for button
     * @param array  $payload Button payload
     *
     * @return array
     */
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

    /**
     * Generate button with type open_app
     *
     * @param string  $label    Button label
     * @param integer $app_id   Application id
     * @param integer $owner_id Owner id
     * @param string  $hash     Hash for button
     * @param array   $payload  Button payload
     *
     * @return array
     */
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

    /**
     * Encode payload
     *
     * @param mixed $payload Payload
     *
     * @return void
     */
    private static function jEncode($payload)
    {
        return $payload === null ? $payload : json_encode($payload);
    }
}
