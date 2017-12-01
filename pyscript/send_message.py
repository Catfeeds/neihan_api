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
_videos = [
    {
        'from_user_id': u'1',
        'group_id': u'hyhuo_333707',
        'title': u"究极爆乳美女",
        'comment': u"【喑天使】 我感觉看到了**😱😱😱\n【累嘞类】自从下了这个软件身体一天不如一天了\n【钟离逸言】 你这是在犯罪\n【我看看你活着没】 奶头凸出了\n【社会你曜哥】 这歌真白哦不这奶真好听"
    }
]


def get_hot_video():
    # return _mgr.get_hot_video()
    return _videos[0]


def send_msg(arg):
    global total_send

    u = arg['u']
    video = arg['video']
    formids = _mgr.get_user_formid(u['id'])
    if not formids:
        logging.info('用户{}-{}无有效的formid'.format(u['id'], u['user_name'].encode('utf8')))
        return None

    if arg['ulevel'] > 0:
        if len(formids) < 2:
            return None

    formid = formids[0]
    params = {
        "touser": u['openid'].encode('utf8'),
        "template_id": 'Vpq9PCekMsNMr8zQKC6JporcztCg55RNZUZUizgq5HA',
        "page": "pages/index/index?video_id={}&from_user_id={}".format(video['group_id'].encode('utf8'), video['from_user_id'].encode('utf8')),
        "form_id": formid['form_id'].encode('utf8'),
        "emphasis_keyword": "keyword1.DATA",
        "data": {
            "keyword1": {
                "value": "小编强力推荐！",
                "color": "#FF0000",
            },
            "keyword2": {
                "value": video['title'].encode('utf8'),
                "color": "#FF0000",
            },
            "keyword3": {
                "value": video['comment'].encode('utf8'),
                "color": "#173177"
            }
        }
    }
    _mgr.user_formid_used(formid['id'])

    access_token = wxtoken.get_token(u['source'])
    api = WX_MSG_API + access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False), timeout=120)
    print resp
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            total_send += 1
            logging.info('用户{}-{}的消息推送成功'.format(u['openid'], u['user_name'].encode('utf8')))
        else:
            logging.info('用户{}-{}的消息推送失败, 失败原因{}'.format(u['openid'], u['user_name'].encode('utf8'), content['errmsg']))


def is_sended(currtime=None):
    flag = True
    filename = _current_pwd + '/res/send_{}.txt'.format(currtime.strftime('%Y%m%d%H'))
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
    return True
    if not MSG_SEND_SWITCH:
        return False
    flag = False
    currtime = datetime.now()
    if is_send_point(currtime):
        if not is_sended(currtime):
            flag = True
    return flag


def main():
    global total_send
    try:
        uparams = {
            'user_id': 10
        }
        if len(sys.argv) >= 2:
            uparams['user_id'] = int(sys.argv[1])
    except:
        logging.info('参数不完整')
        return None

    ulevel = 0

    while True:
        if should_send():
            users = _mgr.get_users(uparams)
            if users:
                video = get_hot_video()
                args = [{'u': user, 'video': video, 'ulevel': ulevel} for user in users]
                pools = Pool(WORKER_THREAD_NUM)
                pools.map(send_msg, args)

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
