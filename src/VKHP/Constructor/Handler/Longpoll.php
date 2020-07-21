<?php
namespace VKHP\Constructor\Handler;
use VKHP\Constructor\Handler;
use VKHP\Constructor\Handler\AHandler;
use VKHP\Method;
use VKHP\Request;

/**
 * Longpoll handler
 */
class Longpoll extends AHandler implements Handler
{
    /**
     * @var int
     */
    private $group_id;

    /**
     * @var string
     */
    private $access_token;

    /**
     * @var array
     */
    private $updates;

    /**
     * Construct method
     *
     * @param integer $group_id     Group id
     * @param string  $access_token Access token
     */
    public function __construct(int $group_id, string $access_token)
    {
        $this->group_id = $group_id;
        $this->access_token = $access_token;
    }

    /**
     * @see Handler::getParameters
     */
    public function getParameters()
    {
        return array_shift($this->updates);
    }

    /**
     * @see Handler::run
     */
    public function run(int $microtime = 400000)
    {
        $lps = $this->getLongPollServer();
        while (true) {
            $lpurl = $this->getLongPollUrl($lps->server, $lps->key, $lps->ts);
            $request = Request::makeJson($lpurl);

            if (isset($request->failed)) {
                switch ($request->failed) {
                    case 1:
                        $lps->ts = $request->ts;
                        continue 2;
                    case 2: //
                    case 3:
                        $lps = $this->getLongPollServer();
                        continue 2;
                }
            }

            $this->updates = $request->updates;
            while (($parameters = $this->getParameters()) !== null) {
                $event = $parameters->type ?? null;
                if (array_key_exists($event, $this->events)) {
                    ($this->events[$event])($parameters->object);
                }

                usleep($microtime);
            }
            $lps->ts = $request->ts;
        }
    }

    /**
     * Get parameters from method groups.getLongPollServer
     *
     * @return object
     */
    private function getLongPollServer(): object
    {
        $req = Method::make(
            $this->access_token,
            'groups.getLongPollServer',
            [ 'group_id' => $this->group_id ]
        );

        return $req->response ?? $req->error;
    }

    /**
     * Get long poll url
     *
     * @param string $server Server
     * @param string $key    Key
     * @param string $ts     Ts
     *
     * @return string
     */
    private function getLongPollUrl(string $server, string $key, string $ts): string
    {
        return "{$server}?act=a_check&key={$key}&wait=25&ts={$ts}";
    }
}
