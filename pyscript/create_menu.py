# coding=utf8
import json
import requests
import wxtoken
from settings import *

'''
{u'url': u'http://mmbiz.qpic.cn/mmbiz_jpg/bqoASNeyzKskPErWAQ4n8YWvSW5bRBDeXaObIibh5fCvdYgK8zZEsCuORhWGM9kLOovkhJKTeuvbZuHovvDOcZQ/0?wx_fmt=jpeg', u'media_id': u'zrVy1Um2HLtEorHdlcHNsx3EP21xtcujs_pvw5ASRdg'}

{u'url': u'http://mmbiz.qpic.cn/mmbiz_jpg/bqoASNeyzKskPErWAQ4n8YWvSW5bRBDeVkOlhjOx67KBLPCRMPHlaicH4cDT54oso8YFJfUVtic0iahsZou5N3qDg/0?wx_fmt=jpeg', u'media_id': u'zrVy1Um2HLtEorHdlcHNs58USNL3sPEJFdyEB3anHpE'}

{
  "button": [
    {
      "type": "miniprogram",
      "name": "小程序",
      "appid": "wx1dda1f639e823874",
      "url": "http://www.qq.com",
      "pagepath": "page/index"
    }
  ]
}
'''

def run():
    API = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='
    wx_access_token = wxtoken.get_token('neihan_mp')
    params = {
        "button": [{
            "type": "click",
            "name": "代理教程",
            "key": "V1001_PROMO"
        },
        # {
        #     "type": "click",
        #     "name": "抢红包",
        #     "key": "V1001_HONGBAO"
        # },
        {
            "type": "miniprogram",
            "name": "抢红包",
            "appid": "wx7876c2b72fed4be6",
            "url": "http://www.qq.com",
            "pagepath": "pages/index/index"
        },
        {
            "type": "media_id",
            "name": "客服",
            "media_id": "zrVy1Um2HLtEorHdlcHNs2Yjw5fdLpatBl1lrNj1NZk",
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