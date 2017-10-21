# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import requests
import os
import json
from time import sleep
from datetime import datetime
from settings import *
from models import *
from wxtoken import get_token


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def get_users():
    ret = _mgr.get_users()
    return ret


def send_msg(u):
    params = {
      "touser": u['openid'],
      "template_id": "XXXXXXXXXXXXXXXXX",
      "page": "index",
      "form_id": 'xXXXX',
      "data": {
          "keyword1": {
              "value": "339208499",
              "color": "#173177"
          },
          "keyword2": {
              "value": "2015年01月05日 12:30",
              "color": "#173177"
          },
          "keyword3": {
              "value": "粤海喜来登酒店",
              "color": "#173177"
          },
          "keyword4": {
              "value": "广州市天河区天河路208号",
              "color": "#173177"
          }
      },
      "emphasis_keyword": "keyword1.DATA"
    }
    return None

    resp = requests.post(WX_MSG_API, params)
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] != 0:
            logging.info('用户{}的消息推送失败, 失败原因{}'.format(u['openid'], content['m']))
        else:
            logging.info('用户{}的消息推送成功'.format(u['openid']))


def is_sended(currtime=None):
    flag = True
    filename = 'res/send_{}.txt'.format(currtime.strftime('%Y%m%d%H'))
    if not os.path.isfile(filename):
        with open(filename, 'wb') as f:
            pass

    with open(filename, 'rb') as f:
        data = f.read()
        try:
            data = json.loads(data)
        except:
            data = {}
        if str(currtime.hour) not in data:
            flag = False
            data[currtime.hour] = 1
    with open(filename, 'wb') as f:
        f.write(json.dumps(data))
    return flag


def is_send_point(currtime=None):
    flag = False
    if not currtime:
        currtime = datetime.now()
    currhour = currtime.hour
    if currhour in TIME_POINTS:
        flag = True
    return flag


def should_send():
    flag = False
    currtime = datetime.now()
    if is_send_point(currtime):
        if not is_sended(currtime):
            flag = True
    return flag


def main():
    while should_send():
        users = get_users()
        if users:
            pools = Pool(WORKER_THREAD_NUM)
            pools.map(send_msg, users)
        else:
            logging.info('没有用户，暂停消息推送')
        sleep(60)
    else:
        logging.info('还未到消息推送时间点或已发送过消息')


if __name__ == '__main__':
    main()
