# coding=utf8
from models import *
from settings import *


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def main():
    top_user_id = 66
    parent_users = _mgr.get_parent_users({'from_user_id': top_user_id})
    puids = [top_user_id]
    for p in parent_users:
        puids.append(p['user_id'])
    users = _mgr.get_users()
    for user in users:
        if not user['parent_user_id']:
            share_click = _mgr.get_first_share_click({'user_id': user['id']})
            if not share_click:
                continue
            if share_click['from_user_id'] in puids:
                print share_click
                _mgr.update_user_parent(user['id'], top_user_id)
                _mgr.insert_fission({
                    'from_user_id': share_click['from_user_id'],
                    'user_id': user['id'],
                    'parent_user_id': top_user_id,
                    'video_id': share_click['video_id'],
                    'create_time': share_click['create_time'],
                    'update_time': share_click['update_time']
                })
    print 'Script Done'


if __name__ == '__main__':
    main()
