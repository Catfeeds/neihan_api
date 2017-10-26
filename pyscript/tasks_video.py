# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import os
import math
from time import sleep
from settings import *
from models import *


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


_current_pwd = os.path.dirname(os.path.realpath(__file__))


def main():
    while True:
        logging.info('开始更新视频展示点击率、展示播放率、视频热度率、视频等级...')

        _mgr.update_video_display_ratio()

        _mgr.update_video_hot_ratio()

        _mgr.update_video_level()

        logging.info('完成更新视频展示点击率、展示播放率、视频热度率、视频等级!')
        logging.info('暂停{}秒后继续更新'.format(VIDEO_FRESH_INTVAL))
        sleep(VIDEO_FRESH_INTVAL)

if __name__ == '__main__':
    main()
