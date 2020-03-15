#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys
from datetime import datetime

now = datetime.now().strftime('%a %b %d')
#  time = datetime.now().strftime('%I¯%M')

hour = "{0:X}".format(int(datetime.now().strftime('%I')))
minute = datetime.now().strftime('%M')

weekday = now[0:2]  # datetime.now().strftime('%w')
month = now[4:5]  # "{0:x}".format(int(datetime.now().strftime('%m')))
day = now[8:10].lstrip('0')

hour = hour.replace("A", "T")
hour = hour.replace("B", "L")
hour = hour.replace("C", "0")

query = weekday + month + day + '˙' + hour + minute
# query = weekday + '|' + month + '¯' + day + '˙' + hour + '¯' + minute

sys.stdout.write(query)