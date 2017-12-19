# coding=utf8
from time import sleep, time
from datetime import datetime
from settings import *
from models import *

_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def main():
    while True:
        _mgr.refresh_formid()
        logging.info('清理无效的formid')
        sleep(60*10)


if __name__ == '__main__':
    main()