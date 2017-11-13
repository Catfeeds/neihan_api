# coding=utf8
import requests

url = 'http://i.snssdk.com/neihan/video/playback/1510344201.46/?video_id=8ec7d4fcf1f94764aed20b281d937089&quality=origin&line=0&is_gif=0.mp4'

url2 = 'http://ic.snssdk.com/neihan/video/playback/1510540850.21/?video_id=b3093b0198d64fb4b60f2fb0367118ab&quality=720p&line=1&is_gif=0&device_platform=android'

r = requests.head(url2, allow_redirects=True)
for h in r.history:
    if '22000000000000000000000000000000000' in h.headers['Location']:
        print 'this video is expired'
        break
