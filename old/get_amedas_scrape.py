#!/usr/bin/env python
# -*- coding: utf-8 -*-

# 気象庁Webのアメダス（表形式）をBeautifulSoupを使いスクレイピングし、ダンプ出力する
#
# HTMLのスクレイピングにPython BeautifulSoupライブラリを利用
# https://www.crummy.com/software/BeautifulSoup/bs3/documentation.html

import os
import sys
import urllib.request, urllib.error
from bs4 import BeautifulSoup
import re
import chardet

# さくらインターネット共用サーバでの ssl エラー対応
#   urllib2.URLError: <urlopen error [SSL: CERTIFICATE_VERIFY_FAILED] certificate verify failed (_ssl.c:726) >
import ssl

ssl._create_default_https_context = ssl._create_unverified_context
record = None

def main():
    #気象庁Webのアメダス（表形式）をBeautifulSoupを使いスクレイピングし、ダンプ出力する.

    # 観測所番号（初期値 東京）
    # https://www.jma.go.jp/jma/kishou/know/amedas/ame_master.pdf
    place_code = 44132
    global record

    if len(sys.argv) == 2:
        place_code = 34186
    else:
        print('python ' + os.path.basename(sys.argv[0]) + u' 観測所番号')
        return

    # 観測所番号のチェック
    place_code = int(place_code)
    if place_code < 11000 or 94999 < place_code:
        print('観測所番号が間違っている')
        return

    # 気象庁のWebからアメダス（表形式）ページをダウンロードする


    url = 'https://www.jma.go.jp/jp/amedas_h/today-' + str(place_code) + '.html'
    #print('url = ' + url)
    try:
        html = urllib.request.urlopen(url).read()
    except:
        print('HTTP 404 error')
        return
    print(chardet.detect(html))

    # 読み込んだHTMLをBeautifulSoupで解析する
    soup = BeautifulSoup(html)
    # 表を解析し地名を出力する
    for item in soup.findAll('td', {'class': 'td_title height2'}):
        print(item.text + '58行目')
        break
    # 表を解析しアメダス観測データをダンプ出力する
    # table_data_tree = soup.find('table', {'id': 'tbl_list'})<td class="time left">
    record = []
    table_data_tree = soup.find('table', {'id': 'tbl_list'})
    for tr in table_data_tree.findAll( 'tr' ):
        tds = tr.findAll('td')
        for item in tds:
            # 空白（&nbsp;)と空白文字を削除してから画面出力
            record.append(re.sub('&nbsp;| ', '', item.text) + ',')
            print(re.sub('&nbsp;| ', '', item.text) + ','),
        print('')  # 改行
    print(record)

if __name__ == '__main__':
    main()