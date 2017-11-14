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
    try:
        logging.info('check video is expired: {}'.format(video.content.encode('utf8')))
        url = video.vurl
        r = requests.head(url, allow_redirects=True, timeout=10)
        if r and r.status_code != 200:
            print r
            return None
        for h in r.history:
            if '22000000000000000000000000000000000' in h.headers['Location']:
                logging.info('this video is expired: {}'.format(video.content.encode('utf8')))
                _mgr.update_video_expired(video.id)
                break
    except:
        pass


def main():
    for i in xrange(1000000):
        params = {
            'category': [65],
            'is_expired': 0,
            'limit': 2,
            'offset': i*2
        }
        videos = _mgr.get_videos(params)
        if len(videos) == 0:
            break
        pools = Pool(5)
        pools.map(check, videos)
        sleep(2)


if __name__ == '__main__':
    main()
