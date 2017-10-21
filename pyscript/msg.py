# coding=utf8
import requests
from wxtoken import get_token


def template_list():
    token = get_token()
    api = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/list?access_token='+token['access_token']
    params = {'offset': 0, 'count': 5, 'access_token': token['access_token']}
    resp = requests.post(api, params)
    if resp and resp.status_code == 200:
        data = resp.json()
        print data


def main():
    template_list()


if __name__ == '__main__':
    main()
