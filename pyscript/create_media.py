# coding=utf8
import os
import requests
import wxtoken
from settings import *


def run(filename=''):
    if not os.path.isfile(filename):
        logging.info('图片不存在')
        return None

    wx_access_token = wxtoken.get_token('neihan_mp')
    params = {
        'access_token': wx_access_token['access_token'],
        'type': 'image'
    }
    api = WX['media_api_p'] + wx_access_token['access_token'] + '&type=image'
    resp = requests.post(api, params, files={'media': open(filename, 'rb')})
    if resp and resp.status_code == 200:
        content = resp.json()
        if content.get('media_id', '') != '':
            logging.info('图片上传成功')
            print content
        else:
            logging.info('图片上传失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('图片上传失败')


if __name__ == '__main__':
    run('kefu.jpeg')