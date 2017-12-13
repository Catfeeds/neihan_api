# coding=utf8
import json
import requests
import wxtoken
from settings import *

'''
{u'url': u'http://mmbiz.qpic.cn/mmbiz_jpg/4YBian2HRWecFmqmqJ0icOljlO3fXKgq9AiaSfnv23nqlSExuY3BVCYHJDkpNeq1Er0PxUqqcQumssQtVasxmg5ow/0?wx_fmt=jpeg', u'media_id': u'2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'}
'''

def run():
    API = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='
    wx_access_token = wxtoken.get_token('neihan_mp')
    params = {
        "button": [{
            "type": "click",
            "name": "成为代理",
            "key": "V1001_PROMO"
        },
        {
            "type": "click",
            "name": "推广",
            "key": "V1001_QRCODE"
        },
        {
            "type": "click",
            "name": "小程序",
            "key": "V1001_APP"
        },
        {
            "type": "media_id",
            "name": "客服",
            "media_id": "2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg",
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