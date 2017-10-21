# coding=utf8
import logging

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(name)s:%(levelname)s %(message)s')

# 协程池大小
WORKER_THREAD_NUM = 100

# 数据库设计
DATABASE = {
    'host': '39.108.53.90',
    'user': 'root',
    'passwd': 'lupin2008cn',
    'port': 3306,
    'db_name': 'neihanshequ'
}

# 消息推送时间点
TIME_POINTS = [15, 16]

WX_TOKEN_API = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx564a50039dd91934&secret=13340cc1f1f0c1e1cece28e2a2d826a2'

WX_MSG_API = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='
