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

# 1展示次数、2播放次数、3播放完成次数、4分享次数、5评论次数、6点赞次数、10新增用户、11推送ID次数、12播放用户数、13、播放完成用户数、14可推送用户数、15分享群数、16有展示的用户数、17有分享的用户数
_type = ['display', 'play', 'play_end', 'share', 'comment', 'digg', 'member', 'formid', 'member_play', 'member_play_end', 'member_formid', 'share_gid', 'member_display', 'member_share']
_source = ['neihan_1', 'neihan_2', 'neihan_3']


def yesterday_ts(interval=1):

    yesterday = (datetime.utcnow()+timedelta(hours=8)).date() - timedelta(days=interval)
    now_ts = int(time() - 86400*interval)

    yesterday_beg_ts = now_ts - now_ts%86400 - 8*3600
    yesterday_end_ts = yesterday_beg_ts + 86400 - 1
    return [yesterday.strftime('%Y-%m-%d'), yesterday_beg_ts, yesterday_end_ts]


def summary_play(interval=1):
    logging.info('summary play')
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 2 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} AND users_logs.type = 2
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        traceback.print_exc()
    

def summary_play_end(interval=1):
    print 'summary play end'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 3 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} AND users_logs.type = 3
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_member(interval=1):
    print 'summary member'
    ts = yesterday_ts(interval)
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


def summary_member_play(interval=1):
    logging.info('summary member play')
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(users_logs.user_id)) AS total, users.source, '{}' AS date, 12 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} AND users_logs.type = 2
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        traceback.print_exc()


def summary_member_play_end(interval=1):
    logging.info('summary member play')
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(users_logs.user_id)) AS total, users.source, '{}' AS date, 13 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {}  AND users_logs.type = 3
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        traceback.print_exc()


def summary_display(interval=1):
    print 'summary display'
    ts = yesterday_ts(interval)
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


def summary_member_display(interval=1):
    print 'summary member display'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(videos_display_logs.user_id)) AS total, users.source, '{}' AS date, 16 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM videos_display_logs 
        INNER JOIN users ON videos_display_logs.user_id = users.id
        WHERE videos_display_logs.create_time >= {} AND videos_display_logs.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_share(interval=1):
    print 'summary share'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 4 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {} AND users_logs.type = 4
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_share_gid(interval=1):
    print 'summary share gid'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(users_shares.gid)) AS total, users.source, '{}' AS date, 15 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_shares 
        INNER JOIN users ON users_shares.user_id = users.id
        WHERE users_shares.create_time >= {} AND users_shares.create_time <= {} AND users_shares.gid != ''
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_member_share(interval=1):
    print 'summary member share'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(users_logs.user_id)) AS total, users.source, '{}' AS date, 17 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_logs 
        INNER JOIN users ON users_logs.user_id = users.id
        WHERE users_logs.create_time >= {} AND users_logs.create_time <= {}  AND users_logs.type = 4
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_comment(interval=1):
    print 'summary comment'
    ts = yesterday_ts(interval)
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


def summary_digg(interval=1):
    print 'summary digg'
    ts = yesterday_ts(interval)
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


def summary_formid(interval=1):
    print 'summary formid'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(*) AS total, users.source, '{}' AS date, 11 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_formids 
        INNER JOIN users ON users_formids.user_id = users.id
        WHERE users_formids.create_time >= {} AND users_formids.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary_member_formid(interval=1):
    print 'summary member formid'
    ts = yesterday_ts(interval)
    try:
        sql = '''
        INSERT INTO summary (total, source, date, type, create_time, update_time)
        SELECT COUNT(DISTINCT(users_formids.user_id)) AS total, users.source, '{}' AS date, 14 AS type, unix_timestamp(now()) as create_time, unix_timestamp(now()) as update_time FROM users_formids 
        INNER JOIN users ON users_formids.user_id = users.id
        WHERE users_formids.create_time >= {} AND users_formids.create_time <= {} 
        GROUP BY users.source
        '''.format(ts[0], ts[1], ts[2])
        print sql
        _mgr.session.execute(sql)
        _mgr.session.commit()
    except Exception as e:
        pass


def summary(t, d=1):
    eval("summary_"+t+"(d)")


def run():
    for i in _type:
        summary(i)
    print 'All subprocesses done.'


if __name__ == '__main__':
    run()
