# coding=utf8
import requests
import os
import json
from time import sleep, time
from settings import *

_filename = '../application/extra/access_token.txt'


def get_token():
    logging.info('管理微信小程序的access token...')
    if not os.path.isfile(_filename):
        with open(_filename, 'wb') as f:
            pass

    is_expired = False
    data = {}
    with open(_filename, 'rb') as f:
        try:
            data = json.loads(f.read())
            if data['expires_time'] - int(time()) - 1000 < 0:
                is_expired = True
        except:
            data = {}
            is_expired = True
    if is_expired:
        resp = requests.get(WX_TOKEN_API)
        if resp and resp.status_code == 200:
            data = resp.json()
            if 'access_token' in data:
                data['expires_time'] = data['expires_in'] + int(time())
                logging.info('成功获取ACCESS TOKEN')

                with open(_filename, 'wb') as f:
                    f.write(json.dumps(data))
    else:
        logging.info('时间戳还在有效期内')
    return data


def main():
    retry_times = 0
    while True:
        token = get_token()
        if token and token['expires_time'] - int(time()) - 1000 > 0:
            break
        sleep(10)
        retry_times += 1

if __name__ == '__main__':
    main()
