#!/usr/bin/env python
# encoding: utf-8
# 共用表定义
import time
from random import randint
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

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["openid"] = self.openid
        ret["user_name"] = self.user_name
        ret["user_avatar"] = self.user_avatar

        return ret


class Mgr(object):

    def __init__(self, engine):
        BaseModel.metadata.create_all(engine)
        self.session = sessionmaker(bind=engine)()
        self.engine = engine

    def get_users(self):
        try:
            ret = []
            q = self.session.query(User)
            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get users error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret
