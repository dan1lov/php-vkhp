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

    public static function uploadMessagesPhoto(
        string $access_token,
        array $files,
        array $params
    ): array {
        if (empty($params['peer_id'])) {
            throw new \Exception('field `peer_id` is empty');
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

    private static function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
