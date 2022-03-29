<?php
echo analyseAccessLog('access.log');

 function analyseAccessLog(string $file) {

    $result = [
        'views'    => 0,
        'urls'     => 0,
        'traffic'  => 0,
        'crawlers' => [
            'Google' => 0,
            'Yandex' => 0,
            'Bing'   => 0,
            'Baidu'  => 0,
        ],
        'status_codes' => [],
    ];

    $remote_hosts = [];
    $status_codes = [];
    $pattern = '/^([^ ]+) ([^ ]+) ([^ ]+) (\[[^\]]+\]) "(.*) (.*) (.*)" ([0-9\-]+) ([0-9\-]+) "(.*)" "(.*)"$/';

    if ($open_file = fopen($file, 'r')) {
        $i = 1;
        while (!feof($open_file)) {
            if ($line = trim(fgets($open_file))) {
                if (preg_match($pattern, $line, $matches)) {
                    list(
                        $line,
                        $remote_host,
                        $logname,
                        $user,
                        $time,
                        $method,
                        $request,
                        $protocol,
                        $status,
                        $bytes,
                        $referer,
                        $user_agent
                    ) = $matches;

                    if (!array_search($remote_host, $remote_hosts)) {
                        $remote_hosts[] = $remote_host;
                    }

                    if (!array_key_exists($status, $status_codes)) {
                        $status_codes[$status] = 1;
                    } else {
                        $status_codes[$status]++;
                    }

                    $result['views'] = $i;
                    $result['urls'] = count($remote_hosts);
                    $result['traffic'] += $bytes;
                    $result['status_codes'] = $status_codes;

                    $bots_pattern = "/bot|google|yandex|bing|baidu/i";
                    preg_match($bots_pattern, $user_agent, $bot_result);
                    if (!empty($bot_result)) {
                        list($bot_name) = $bot_result;
                        if (!array_key_exists($bot_name, $result['crawlers'])) {
                            $result['crawlers'][$bot_name] = 1;
                        } else {
                            $result['crawlers'][$bot_name]++;
                        }
                    }
                } else {
                    error_log("Can't parse line $i: $line");
                }
            }
            $i++;
        }
    }

    return json_encode($result, JSON_PRETTY_PRINT);
}
