# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
from time import sleep
import requests
from settings import *
from models import *


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def check(video):
    logging.info('check video is expired: {}'.format(video.content.encode('utf8')))
    url = video.vurl
    r = requests.head(url, allow_redirects=True)
    for h in r.history:
        if '22000000000000000000000000000000000' in h.headers['Location']:
            logging.info('this video is expired: {}'.format(video.content.encode('utf8')))
            _mgr.update_video_expired(video.id)
            break


def main():
    for i in xrange(2):
        params = {
            'category': [65, 1111],
            'is_expired': 0,
            'limit': 1000,
            'offset': i*1000
        }
        videos = _mgr.get_videos(params)
        pools = Pool(100)
        pools.map(check, videos)

        sleep(5)


if __name__ == '__main__':
    main()
