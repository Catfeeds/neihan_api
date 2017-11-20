# coding=utf8
from models import *


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def main():
    users = _mgr.get_users({})
    for user in users:
        if not user.parent_id:
            print user.id
            user_fission = _mgr.get_user_fission(user.id)
            _mgr.update_user_parent(user.id, user_fission['parent_id'])
    print 'Script Done'


if __name__ == '__main__':
    main()
