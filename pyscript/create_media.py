# coding=utf8
import os
import requests
import wxtoken
from settings import *

'''
{u'url': u'http://mmbiz.qpic.cn/mmbiz_png/bqoASNeyzKskPErWAQ4n8YWvSW5bRBDeLDibaFW1iaGmL0uia5Ebl0jZfMiczcoaIp6AjPYwQpJD2wpTRY1NQOYKcA/0?wx_fmt=png', u'media_id': u'zrVy1Um2HLtEorHdlcHNs8sVJ8-2Gm4vqAvR-B4jKJk'}

{u'url': u'http://mmbiz.qpic.cn/mmbiz_png/bqoASNeyzKskPErWAQ4n8YWvSW5bRBDetMNOE0CrnpxUficW3aM4fgLJkGsunBFNW6iaZX9P5PzgUuyDU3OVMrDQ/0?wx_fmt=png', u'media_id': u'zrVy1Um2HLtEorHdlcHNsyzoBLViAdlidUy4hNgX2gM'}

{u'url': u'http://mmbiz.qpic.cn/mmbiz_png/bqoASNeyzKskyf2wB16Wv2icrMwUOqAlFCQqf5QXY1xfhMWgibRmKhThsMJ1oib0diafVFAQP7xxRMldMDVr2e11bQ/0?wx_fmt=png', u'media_id': u'zrVy1Um2HLtEorHdlcHNs5M1r6CL_ta-gjOwGasBlZ8'} fm_dl.png

{u'url': u'http://mmbiz.qpic.cn/mmbiz_png/bqoASNeyzKskyf2wB16Wv2icrMwUOqAlFvpjmyAulEtia784teB3ZES10ziaGIJTX0BPgpyUbwQ6R7FKEIFMXibPWA/0?wx_fmt=png', u'media_id': u'zrVy1Um2HLtEorHdlcHNswiYQMDY2JIScVZkNW2SZDM'} fm_qhb.png

{u'url': u'http://mmbiz.qpic.cn/mmbiz_jpg/bqoASNeyzKudmwzFtWulw7drzjfLLCNNxS1BpJvc2Ym21bDT8mM2YA5Jljxib03qL4r2sibYLMj55V9LzhMtiajJg/0?wx_fmt=jpeg', u'media_id': u'zrVy1Um2HLtEorHdlcHNs2Yjw5fdLpatBl1lrNj1NZk'} kf_02.jpeg
'''
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
    run('kf_01.jpeg')