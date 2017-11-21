#!/usr/bin/env python
# encoding: utf-8
# 共用表定义
import time
from random import randrange
from sqlalchemy import *
from sqlalchemy import create_engine, Column, ForeignKey, String, Integer, Numeric, DateTime, Boolean, and_, or_, func
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, relationship, backref
from sqlalchemy.dialects.mysql import VARCHAR, BIGINT, TINYINT, DATETIME, TEXT, CHAR
from sqlalchemy.dialects.postgresql import ARRAY
from datetime import datetime
from time import time
import logging
import json
import traceback

Base = declarative_base()


class BaseModel(Base):

    __abstract__ = True
    __table_args__ = {
        'extend_existing': True,
        'mysql_engine': 'InnoDB',
        'mysql_charset': 'utf8',
    }


class User(BaseModel):

    __tablename__ = "users"

    id = Column(Integer, primary_key=True)
    openid = Column(VARCHAR(64))
    user_name = Column(VARCHAR(128))
    user_avatar = Column(VARCHAR(512))
    skip_msg = Column(Integer)
    is_active = Column(Integer)
    source = Column(VARCHAR(32))
    parent_user_id = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["openid"] = self.openid
        ret["user_name"] = self.user_name
        ret["user_avatar"] = self.user_avatar
        ret["skip_msg"] = self.skip_msg
        ret["is_active"] = self.is_active
        ret["source"] = self.source
        ret["parent_user_id"] = self.parent_user_id

        return ret


class UserFormId(BaseModel):

    __tablename__ = "users_formids"

    id = Column(Integer, primary_key=True)
    user_id = Column(Integer)
    form_id = Column(VARCHAR(128))
    is_used = Column(Integer)
    create_time = Column(Integer)
    update_time = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["user_id"] = self.user_id
        ret["form_id"] = self.form_id
        ret["is_used"] = self.is_used
        ret["create_time"] = self.create_time
        ret["update_time"] = self.update_time

        return ret


class UserFission(BaseModel):

    __tablename__ = "users_fissions"

    id = Column(Integer, primary_key=True)
    parent_user_id = Column(Integer)
    from_user_id = Column(VARCHAR(64))
    user_id = Column(VARCHAR(64))
    video_id = Column(VARCHAR(64))
    create_time = Column(Integer)
    update_time = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["parent_user_id"] = self.parent_user_id
        ret["from_user_id"] = self.from_user_id
        ret["user_id"] = self.user_id
        ret["video_id"] = self.video_id
        ret["create_time"] = self.create_time
        ret["update_time"] = self.update_time

        return ret


class Video(BaseModel):
    __tablename__ = "videos"
    id = Column("id", Integer, primary_key=True)

    group_id = Column(VARCHAR(64))
    content = Column(VARCHAR(1024))
    category_id = Column(Integer, default=0)

    play_count = Column(Integer, default=0)
    share_count = Column(Integer, default=0)
    digg_count = Column(Integer, default=0)
    comment_count = Column(Integer, default=0)

    c_display_count = Column(Integer, default=0)
    c_play_count = Column(Integer, default=0)
    c_share_count = Column(Integer, default=0)
    c_digg_count = Column(Integer, default=0)
    c_comment_count = Column(Integer, default=0)

    level = Column(Integer, default=0)
    display_click_ratio = Column(Numeric, default=0)
    display_share_ratio = Column(Numeric, default=0)
    hot_ratio = Column(Numeric, default=0)

    vurl = Column(VARCHAR(512))
    is_expired = Column(Integer, default=0)

    def conv_result(self):
        ret = {}
        ret["id"] = self.id
        ret["group_id"] = self.group_id
        ret["content"] = self.content

        ret["play_count"] = int(self.play_count)
        ret["share_count"] = int(self.share_count)
        ret["digg_count"] = int(self.digg_count)
        ret["comment_count"] = int(self.comment_count)

        ret["c_display_count"] = int(self.c_display_count)
        ret["c_play_count"] = int(self.c_play_count)
        ret["c_share_count"] = int(self.c_share_count)
        ret["c_digg_count"] = int(self.c_digg_count)
        ret["c_comment_count"] = int(self.c_comment_count)

        ret["level"] = int(self.level)
        ret["display_click_ratio"] = float(self.display_click_ratio)
        ret["display_share_ratio"] = float(self.display_share_ratio)
        ret["hot_ratio"] = float(self.hot_ratio)
        ret["vurl"] = self.vurl
        ret["is_expired"] = self.is_expired

        return ret


class MsgSendRecord(BaseModel):

    __tablename__ = "msg_send_record"

    id = Column(Integer, primary_key=True)
    group_id = Column(VARCHAR(64))
    total = Column(Integer)
    source = Column(VARCHAR(32))
    create_time = Column(Integer)
    update_time = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["group_id"] = self.group_id
        ret["total"] = self.total
        ret["source"] = self.source
        ret["create_time"] = self.create_time
        ret["update_time"] = self.update_time

        return ret


class Mgr(object):

    def __init__(self, engine):
        BaseModel.metadata.create_all(engine)
        self.session = sessionmaker(bind=engine)()
        self.engine = engine

    def get_users(self, params={}):
        try:
            ret = []
            q = self.session.query(User)
            if params.get('skip_msg', '') != '':
                q = q.filter(User.skip_msg == params['skip_msg'])
            if params.get('is_active', '') != '':
                q = q.filter(User.is_active == params['is_active'])
            if params.get('source', '') != '':
                q = q.filter(User.source == params['source'])
            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get users error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def get_user_formids(self):
        try:
            ret = []
            q = self.session.query(UserFormId, User) \
                .filter(UserFormId.user_id == User.id)
            rows = q.all()
            for row in rows:
                user = row.User.conv_result()
                user_formid = row.UserFormId.conv_result()
                user_formid['openid'] = user['openid']
                ret.append(user_formid)
        except Exception as e:
            logging.warning("get user formid error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def get_user_formid(self, user_id, is_used=0):
        try:
            ret = []
            available_time = int(time()) - 864000 * 6
            q = self.session.query(UserFormId) \
                .filter(UserFormId.user_id == int(user_id)) \
                .filter(UserFormId.is_used == int(is_used)) \
                .filter(UserFormId.create_time >= available_time) \
                .order_by(UserFormId.create_time.asc()) \
                .limit(2)

            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get users error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def user_formid_used(self, formid):
        try:
            self.session.query(UserFormId) \
                .filter(UserFormId.id == formid) \
                .update({'is_used': 1, 'update_time': int(time())}, synchronize_session='fetch')
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("user formid used error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def update_video_display_ratio(self):
        try:
            sql = 'UPDATE videos SET display_click_ratio = c_play_count*1.0/c_display_count, display_share_ratio = c_share_count*1.0/c_display_count WHERE c_display_count >= 1'
            self.session.execute(sql)
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("update video display ratio error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def update_video_hot_ratio(self):
        try:
            sql = 'UPDATE videos SET hot_ratio = (c_digg_count+c_comment_count*5)*1.0/c_play_end_count WHERE c_play_end_count >= 1'
            self.session.execute(sql)
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("update video hot ratio error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def update_video_level(self):
        try:
            sql = 'UPDATE videos SET level = level+2 WHERE hot_ratio >= 0.1 AND level NOT IN (2, 6) and c_display_count >= 100'
            self.session.execute(sql)
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("update video hot ratio error : %s" % e, exc_info=True)

    def update_video_expired(self, video_id):
        try:
            self.session.query(Video) \
                .filter(Video.id == video_id) \
                .update({'is_expired': 1}, synchronize_session='fetch')
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("update video expired error : %s" % e, exc_info=True)

    def get_hot_video(self):
        try:
            ret = {}
            rows = self.session.query(Video) \
                .filter(Video.category_id == 187) \
                .filter(Video.content != '') \
                .order_by(Video.display_click_ratio.desc()) \
                .offset(int(randrange(0, 100))).limit(1)
            for row in rows:
                ret = row.conv_result()
                break
        except Exception as e:
            logging.warning("get hot video error : %s" % e, exc_info=True)
        finally:
            self.session.close()

        return ret

    def get_videos(self, params={}):
        try:
            ret = []
            q = self.session.query(Video.id, Video.vurl, Video.content)
            if params.get('category', '') != '':
                q = q.filter(Video.category_id.in_(params['category']))
            if params.get('is_expired', '') != '':
                q = q.filter(Video.is_expired == int(params['is_expired']))

            if params.get('offset', '') != '':
                q = q.offset(params['offset'])
            if params.get('limit', '') != '':
                q = q.limit(params['limit'])
            rows = q.all()
            if rows:
                return rows
        except Exception as e:
            logging.warning("get all video error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def save_msg_send_record(self, info):
        try:
            if not info:
                return None
            info['create_time'] = int(time()) + 3600 * 8
            info['update_time'] = int(time()) + 3600 * 8
            self.session.add(MsgSendRecord(**info))
        except Exception as e:
            self.session.rollback()
            logging.warning("save msg send error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def get_user_fission(self, user_id):
        try:
            ret = {}
            q = self.session.query(UserFission) \
                .filter(UserFission.user_id == str(user_id))

            rows = q.all()
            for row in rows:
                ret = row.conv_result()
                break
        except Exception as e:
            logging.warning("get user fission error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def update_user_parent(self, user_id, parent_id):
        try:
            self.session.query(User) \
                .filter(User.id == int(user_id)) \
                .update({'parent_user_id': int(parent_id)}, synchronize_session='fetch')
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("update user parent error : %s" % e, exc_info=True)

    '''
    def count_showed_videos(self):
        try:
            ret = 0
            ret = self.session.query(Video) \
                .filter(Video.c_display_count >= 1).count()
        except Exception as e:
            logging.warning("count showed video error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def get_showed_videos(self, p=0, n=1000):
        try:
            ret = []

            q = self.session.query(Video)
            q = q.filter(Video.c_display_count >= 1)
            # q = q.order_by(Video.c_display_count.desc())
            q = q.limit(n)
            q = q.offset(p*n)

            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get show video error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def update_video(self, vid, data):
        try:
            self.session.query(Video) \
                .filter(Video.id == vid) \
                .update(data, synchronize_session='fetch')
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("video update error : %s" % e, exc_info=True)
        finally:
            self.session.close()
    '''
