# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import sys
import requests
import os
import json
from time import sleep, timedelta
from datetime import datetime
from settings import *
from models import *
import wxtoken


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


_current_pwd = os.path.dirname(os.path.realpath(__file__))

total_send = 0


def send_msg(u):
    params = {
       "touser": u['openid'],
       "template_id": "NcYQ-PA22YGBKCZSCy88rwU4cx1VRyE7x5MBJ8N_JRY",
       "data":{
           "first": {
               "value": u['user_name'].encode('utf8'),
               "color":"#173177"
           },
           "keynote1":{
               "value": str(float(u['commission_avail'])),
               "color":"#173177"
           },
           "keynote2": {
               "value": u['user_name'].encode('utf8'),
               "color":"#173177"
           },
           "keynote3": {
               "value":  "实时",
               "color":"#173177"
           },
           "remark":{
               "value": "等待用户提现。",
               "color": "#173177"
           }
       }
    }

    access_token = wxtoken.get_token('neihan_mp')
    api = WX_MSG_API + access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False), timeout=120)
    print resp
    if resp and resp.status_code == 200:
        content = resp.json()

        mlog = {
            'user_id': u['id'],
            'openid': u['openid'],
            'content': json.dumps(params),
            'date': (datetime.utcnow() + timedelta(hours=8)).date(),
            'type': 'default',
            'status': 1
        }

        if content['errcode'] == 0:
            total_send += 1
            logging.info('用户{}-{}的消息推送成功'.format(u['openid'], u['user_name'].encode('utf8')))
        else:
            mlog['status'] = 0
            logging.info('用户{}-{}的消息推送失败, 失败原因{}'.format(u['openid'], u['user_name'].encode('utf8'), content['errmsg']))
        _mgr.save_user_mp_message_log(mlog)




def main():
    global total_send
    uparams = { 'user_id': 16 }

    while True:
        users = _mgr.get_users_mp(uparams)
        if users:
            pools = Pool(WORKER_THREAD_NUM)
            pools.map(send_msg, users)

            logging.info('成功发送消息给{}个用户'.format(total_send))
        else:
            logging.info('没有用户，暂停消息推送')
    else:
        logging.info('还未到消息推送时间点或已发送过消息')
    sleep(60)


if __name__ == '__main__':
    main()
    # video = _mgr.get_hot_video()
    # print video