# coding=utf8
import requests
import os
import json
import copy
from time import sleep, time
from settings import *
from models import *


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))

_current_pwd = os.path.dirname(os.path.realpath(__file__))

_filename = _current_pwd + '/../application/extra/access_token_{}.txt'


def get_token(source=''):
    if not source:
        source = 'neihan_1'
    logging.info('管理微信小程序的access token...')
    filename = _filename.format(source)
    if not os.path.isfile(filename):
        with open(filename, 'wb') as f:
            pass

    is_expired = False
    data = {}
    with open(filename, 'rb') as f:
        try:
            data = json.loads(f.read())
            if data['expires_time'] - int(time()) - 1000 < 0:
                is_expired = True
        except:
            data = {}
            is_expired = True
    if is_expired:
        resp = requests.get(WX_TOKEN_API[source])
        if resp and resp.status_code == 200:
            data = resp.json()
            if 'access_token' in data:
                data['expires_time'] = data['expires_in'] + int(time())
                logging.info('成功获取ACCESS TOKEN')

                with open(filename, 'wb') as f:
                    f.write(json.dumps(data))

    if data:
        wxtoken = _mgr.get_wxtoken(source)
        if not wxtoken:
            insert_data = copy.copy(data)
            insert_data['app_code'] = source 
            _mgr.save_wxtoken(insert_data)
        elif wxtoken['expires_time'] < data.get('expires_time', 0):
            _mgr.update_wxtoken(source, data)
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
