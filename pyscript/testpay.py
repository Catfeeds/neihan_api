# coding=utf8
import hashlib
import requests
import json


def main():
    api = 'http://api.le6ss.cn/api/precreatetrade'

    key = '888888'
    data = {
        'uid': 'test',
        'orderNo': '20171213001250101',
        'mchName': '测试支付商品',
        'price': 1,
        'backUrl': 'http://www.zyo69.cn/pay/success',
        'postUrl': 'http://www.zyo69.cn/pay/notify',
        'payType': 'wgpay'
    }

    str_sign = 'backUrl=http://www.zyo69.cn/pay/success&mchName=测试支付商品&orderNo=20171213001250101&payType=wgpay&postUrl=http://www.zyo69.cn/pay/notify&price=1&uid=test&key=888888'
    m = hashlib.md5(str_sign)
    data['sign'] = m.hexdigest().upper()
    # headers = {}
    headers = {
        'content-type': 'application/x-www-form-urlencoded',
        'content-encoding': "UTF8"
    }
    resp = requests.post(api, data=data, headers=headers)
    print resp
    content = resp.json()
    print content
    print content['message']

if __name__ == '__main__':
    main()