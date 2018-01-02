# coding=utf8
import traceback
from multiprocessing import Pool
from time import sleep, time
from datetime import datetime, timedelta
from settings import *
from models import *

_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))

_type = ['play', 'play_end', 'member', 'display', 'share', 'comment', 'digg', 'formid']
_source = ['neihan_1', 'neihan_2', 'neihan_3']


def yesterday_ts():
    interval = 1

    yesterday = (datetime.utcnow()+timedelta(hours=8)).date() - timedelta(days=interval)
    now_ts = int(time() - 86400*interval)

    yesterday_beg_ts = now_ts - now_ts%86400 - 8*3600
    yesterday_end_ts = yesterday_beg_ts + 86400 - 1
    return [yesterday.strftime('%Y-%m-%d'), yesterday_beg_ts, yesterday_end_ts]


def summary_play():
    logging.info('summary play')
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 2 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        traceback.print_exc()
    

def summary_play_end():
    print 'summary play end'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 3 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_member():
    print 'summary member'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, source, '{}' AS date, 10 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users 
        WHERE users.create_time >= {} AND users.create_time <= {} 
        GROUP BY source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass

def summary_display():
    print 'summary display'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 1 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM videos_display_logs 
        INNER JOIN users ON videos_display_logs.user_id = users.id
        WHERE videos_display_logs.create_time >= {} AND videos_display_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_share():
    print 'summary share'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 4 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_comment():
    print 'summary comment'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 5 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_digg():
    print 'summary digg'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 6 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_formid():
    print 'summary formid'
    ts = yesterday_ts()
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 6 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_formids 
        INNER JOIN users ON users_formids.user_id = users.id
        WHERE users_formids.create_time >= {} AND users_formids.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary(t):
    eval("summary_"+t+"()")


def run():
    for i in _type:
        summary(i)
    print 'All subprocesses done.'


if __name__ == '__main__':
    run()
