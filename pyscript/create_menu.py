# coding=utf8
import json
import requests
import wxtoken
from settings import *


def run():
    API = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='
    wx_access_token = wxtoken.get_token('neihan_mp')
    params = {
        "button": [{
            "type": "view",
            "name": "成为代理",
            "url": "http://www.baidu.com"
        }, {
            "type": "media_id",
            "name": "客服",
            "media_id": "ojgSex-sKR8g6frh3NRo72EiWFxHkvOPHAS9uE5TgVM",
        }]
    }
    api = API + wx_access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False))
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            logging.info('菜单创建成功！')
        else:
            logging.info('菜单创建失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('菜单创建失败')


if __name__ == '__main__':
    run()
~              