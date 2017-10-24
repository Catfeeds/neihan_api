# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
from concurrent.futures import ThreadPoolExecutor
import requests
import traceback
from tomorrow import threads


total_success, total_fail = 0, 0


def get_page(api):
    global total_success
    global total_fail
    try:
        resp = requests.get(api, timeout=10)
        total_success += 1
        print resp
    except:
        traceback.print_exc()
        total_fail += 1
        print 'timeout'


def main():
    api = 'https://wx.js101.wang/api/videos?p=1n=10&user_id=6'
    pools = Pool(15)
    apis = [api for x in range(1000)]
    pools.map(get_page, apis)
    print 'Total Success {}, Total Fail {}'.format(total_success, total_fail)
    print 'Script Done'


if __name__ == '__main__':
    main()
