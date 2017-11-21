# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import sys
import requests
import os
import json
from time import sleep
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


def send_msg(arg):
    global total_send

    u = arg['u']
    video = arg['video']
    access_token = arg['access_token']
    formids = _mgr.get_user_formid(u['id'])
    if not formids:
        logging.info('用户{}-{}无有效的formid'.format(u['id'], u['user_name'].encode('utf8')))
        return None

    if arg['ulevel'] == 2:
        if len(formids) < 2:
            return None

    formid = formids[0]
    params = {
        "touser": u['openid'].encode('utf8'),
        "template_id": TEMPLATE_ID[u['source']],
        "page": "pages/index/index?video_id={}&from_user_id={}".format(video['group_id'].encode('utf8'), video['from_user_id']),
        "form_id": formid['form_id'].encode('utf8'),
        "data": {
            "keyword1": {
                "value": video['title'].encode('utf8'),
                "color": "#173177"
            },
            "keyword2": {
                "value": video['comment'].encode('utf8'),
                "color": "#173177"
            }
        }
    }
    _mgr.user_formid_used(formid['id'])

    api = WX_MSG_API + access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False))
    print resp
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            total_send += 1
            logging.info('用户{}-{}的消息推送成功'.format(u['openid'], u['user_name'].encode('utf8')))
        else:
            logging.info('用户{}的消息推送失败, 失败原因{}'.format(u['openid'], content['errmsg']))


def main():
    global total_send
    while True:
        tasks = _mgr.get_message_tasks({'is_send': 0, 'send_time': datetime.now()})
        if len(tasks) == 0:
            logging.info('没有消息推送任务')
            sleep(30)
            continue

        for task in tasks:
            _mgr.update_message_tasks(task['id'], {'is_send': 1})
            # video = _mgr.get_videos({'group_id': task['group_id']})
            # comment = _mgr.get_comment({'group_id': task['group_id']})
            uparams = {
                'is_active': 1,
                'source': task['app'],
                'skip_msg': 0
            }
            users = _mgr.get_users(uparams)
            if users:
                video = {
                    'from_user_id': task['from_user_id'],
                    'group_id': task['group_id'],
                    'title': task['title'],
                    'comment': task['comment']
                }
                access_token = wxtoken.get_token(task['app'])

                while len(users):
                    args = []
                    for x in xrange(WORKER_THREAD_NUM):
                        try:
                            args.append({
                                'u': users.pop(),
                                'video': video,
                                'ulevel': task['formid_level'],
                                'access_token': access_token
                            })
                        except:
                            pass
                    pools.map(send_msg, args)
                    sleep(10)


                # args = [{'u': user, 'video': video, 'ulevel': task['formid_level'], 'access_token': access_token} for user in users]
                # pools = Pool(WORKER_THREAD_NUM)
                # pools.map(send_msg, args)

                logging.info('成功发送消息给{}个用户'.format(total_send))
                _mgr.update_message_tasks(task['id'], {'send_member': total_send})
            else:
                logging.info('没有用户，暂停消息推送')
        sleep(30)


if __name__ == '__main__':
    main()
