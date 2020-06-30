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
    protected static $version = '5.110';

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
        $method_url = "https://api.vk.com/method/{$method}";
        $params += [ 'access_token' => $access_token, 'v' => self::$version ];

        $request = \VKHP\Request::makeJson($method_url, $params);
        $request->ok = isset($request->response);
        return $request;
    }

    /**
     * Send message to users
     *
     * @param string $access_token Access token
     * @param array  $params       Parameters for messages.send method
     *
     * @return object
     */
    public static function messagesSend(string $access_token, array $params): object
    {
        $params['random_id'] = $params['random_id'] ?? 0;
        if (! isset($params['user_ids'])) {
            return self::make($access_token, 'messages.send', $params);
        }

        $user_ids = is_array($params['user_ids'])
            ? $params['user_ids'] : explode(',', $params['user_ids']);
        $user_ids = array_unique(array_filter($user_ids));

        $successful = 0;
        $responses = [];

        $chunked_ids = array_chunk($user_ids, 100);
        foreach ($chunked_ids as $user_ids) {
            $params['user_ids'] = implode(',', $user_ids);

            $request = self::make($access_token, 'messages.send', $params);
            if ($request->ok === false) {return $request;}

            $responses += $request->response;
            $successful += count(array_column($request->response, 'message_id'));
        }
        return (object) [
            'ok' => true,
            'successful' => $successful,
            'response' => $responses
        ];
    }

    /**
     * Uploading photos to VK
     *
     * @param string $access_token Access token
     * @param array  $files        Files to upload
     * @param array  $params       Parameters for uploading method
     *
     * @throws Exception if peer_id parameter is not specified in $params array
     * @throws Exception if count of files in $files array more than 5
     *
     * @return array
     */
    public static function uploadMessagesPhoto(
        string $access_token,
        array $files,
        array $params
    ): array {
        if (empty($files)) {
            return array();
        }
        if (empty($params['peer_id'])) {
            throw new \Exception('field `peer_id` is empty');
        }
        if (count($files) > 5) {
            throw new \Exception('too much files (>5)');
        }


        $gurl = self::make($access_token, 'photos.getMessagesUploadServer', $params);
        if ($gurl->ok === false) {
            return (array) $gurl;
        }

        $saved_files = self::_saveFiles($files);
        $upload_files = \VKHP\Request::makeJson(
            $gurl->response->upload_url,
            self::_createCURLFiles($saved_files),
            [ 'Content-type: multipart/form-data;charset=utf-8' ]
        );
        self::_deleteFiles($saved_files);
        if (isset($upload_files->error)) {
            return (array) $upload_files;
        }

        $save_files = self::make(
            $access_token,
            'photos.saveMessagesPhoto',
            [
                'server' => $upload_files->server,
                'photo' => $upload_files->photo,
                'hash' => $upload_files->hash
            ] + $params
        );
        if ($save_files->ok === false) {
            return (array) $save_files;
        }

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
     * @throws Exception if field peer_id/type is not specified in $params array
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
        if ($gurl->ok === false) {
            return (array) $gurl;
        }

        $attachment = [];
        foreach ($files as $file) {
            $saved_file = self::_saveFiles([ $file ]);
            $upload_file = \VKHP\Request::makeJson(
                $gurl->response->upload_url,
                self::_createCURLFiles($saved_file, true),
                [ 'Content-type: multipart/form-data;charset=utf-8' ]
            );
            self::_deleteFiles($saved_file);
            if (isset($upload_file->error)) {
                return (array) $upload_file;
            }

            $save_file = self::make(
                $access_token,
                'docs.save',
                [
                    'file' => $upload_file->file
                ] + $params
            );
            if ($save_file->ok === false) {
                return (array) $save_files;
            }
            if (array_key_exists(0, $save_file->response)) {
                $save_file->response = (object) [
                    $params['type'] => $save_file->response[0]
                ];
            }

            $file = $save_file->response->{$params['type']};
            $attachment[] = "doc{$file->owner_id}_{$file->id}";
        }
        return $attachment;
    }

    /**
     * Saving files in temporary folder
     *
     * @param array $files Files to saving
     * @param array $paths Array of paths
     *
     * @throws Exception if can't retrieve file contents for a certain path
     *
     * @return array
     */
    public static function _saveFiles(array $files, array $paths = []): array
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                $paths[] = realpath($file);
                continue;
            }

            $paths[] = $fpath = tempnam(sys_get_temp_dir(), 'VKHP');
            if (($contents = file_get_contents($file)) === false) {
                throw new \Exception("can't retrieve file contents for path '{$file}'");
            }
            file_put_contents($fpath, $contents);
        }
        return $paths;
    }

    /**
     * Creating CURLFile objects for sending in CURLOPT_POSTFIELDS
     *
     * @param array   $paths      Array of paths
     * @param boolean $single     Flag for single uploading
     * @param string  $field_name Field name in post fields
     *
     * @return array
     */
    public static function _createCURLFiles(
        array $paths,
        bool $single = false,
        string $field_name = 'file'
    ): array {
        [$cfiles, $i] = [[], 1];
        $mime_types = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'text/plain' => '.txt'
        ];

        foreach ($paths as $path) {
            $pathinfo = pathinfo($path);
            $mime_type = mime_content_type($path);

            $basename = in_array($mime_type, array_flip($mime_types))
                ? $pathinfo['filename'] . $mime_types[$mime_type]
                : $pathinfo['basename'];

            $cfile = new \CURLFile($path, $mime_type, $basename);
            $cfkey = $single ? $field_name : ($field_name . $i++);

            $cfiles[$cfkey] = $cfile;
            if ($single) {
                break;
            }
        }
        return $cfiles;
    }

    /**
     * Delete files from paths in $paths array.
     *
     * If $delete_all is set to TRUE, then even files that
     * were not saved to the temporary directory will be deleted
     *
     * @param array   $paths      Array of paths to deleting
     * @param boolean $delete_all Flag to delete all files in $paths
     *
     * @return void
     */
    public static function _deleteFiles(
        array $paths,
        bool $delete_all = false
    ): void {
        foreach ($paths as $path) {
            // realpath нужен во избежание проблем на windows,
            // чтобы обратные слеши (\) заменились на обычные слеши (/)
            $temp_path = realpath(sys_get_temp_dir());
            if (! $delete_all && mb_strpos($path, $temp_path) !== 0) {
                continue;
            }

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
