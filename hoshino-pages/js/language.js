/* 本機翻譯，不需外部翻譯服務；切頁後透過網址與 localStorage 保留語言。 */
(function () {
  'use strict';
  var dict = window.HOSHINO_ZH || {};
  var phrases = Object.keys(dict).sort(function (a, b) { return b.length - a.length; });
  var nodes = [], attrs = [];
  var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  while (walker.nextNode()) {
    var node = walker.currentNode;
    if (node.nodeValue.trim() && !node.parentElement.closest('script,style,#language-switch,#ambience-toggle')) {
      nodes.push({node: node, original: node.nodeValue, keepOriginal: !!node.parentElement.closest("#shop td:nth-child(2)")});
    }
  }
  document.querySelectorAll('[alt],[title],[aria-label]').forEach(function (el) {
    if (el.closest('#language-switch,#ambience-toggle')) return;
    ['alt','title','aria-label'].forEach(function (name) {
      if (el.hasAttribute(name)) attrs.push({el:el, name:name, original:el.getAttribute(name)});
    });
  });
  var title = document.title;
  function translate(text) {
    if (dict[text.trim()]) return text.replace(text.trim(), dict[text.trim()]);
    // 對門市營業時間及公告使用共通語句，名稱、地址、日期、電話保持原樣。
    phrases.forEach(function (key) { text = text.split(key).join(dict[key]); });
    var replacements = [
      ['オフィシャルサイト','官方網站'], ['珈琲ゼリーフェア','咖啡凍活動'],
      ['秋のおすすめメニュー','秋季推薦菜單'],['夏のおすすめメニュー','夏季推薦菜單'],
      ['春のおすすめメニュー','春季推薦菜單'],['冬のおすすめメニュー','冬季推薦菜單'],
      ['クリスマススフレパンケーキ','聖誕舒芙蕾鬆餅'],['ハロウィンスフレパンケーキ','萬聖節舒芙蕾鬆餅'],
      ['キャラメルりんごと苺のスフレパンケーキ','焦糖蘋果草莓舒芙蕾鬆餅'],
      ['ブルーマウンテンブレンド','藍山綜合咖啡'],['モカブレンド','摩卡綜合咖啡'],['マンデリンブレンド','曼特寧綜合咖啡'],
      ['季節のおすすめコーヒー','季節推薦咖啡'],['季節のコーヒー','季節咖啡'],
      ['がオープン。','開幕。'],['にオープンします。','開幕。'],['にオープン。','開幕。'],
      ['（フィリピン）','（菲律賓）'],['より','起'],['から','起'],['がさらにお得になります。','的優惠更加划算。'],
      ['を販売開始します。','開始販售。'],['を販売開始致します。','開始販售。'],['の販売を開始します。','開始販售。'],
      ['を販売いたします。','開始販售。'],['を開始します。','開始。'],['販売開始。','開始販售。'],
      ['一部店舗で福袋の販売をします。','部分門市販售福袋。'],['詳細はこちら','詳情請見此處'],['詳しくはこちら','詳情請見此處'],
      ['をご確認ください。','請參閱。'],['ロードサイド店限定','限路邊型門市'],['毎月','每月'],['にちなみ','配合'],
      ['お得なサービスが始まります。','優惠服務開始。'],['お得なサービス！','優惠服務！'],
      ['お得なキャンペーンを実施いたします。','舉辦優惠活動。'],['期間限定','期間限定'],['の特典','的優惠'],
      ['の期間休業いたします。','期間暫停營業。'],['まで御年賀珈琲を数量限定で販売いたします。','期間限量販售新年賀禮咖啡。'],
      ['平日','平日'],['休日','假日'],['全日','每日'],['土曜','週六'],['日曜','週日'],['月曜','週一'],['火曜','週二'],['水曜','週三'],['木曜','週四'],['金曜','週五'],
      ['土日祝','週末與國定假日'],['土・日・祝','週六、週日、國定假日'],['土日','週末'],['祝日','國定假日'],['祝','國定假日'],
      ['ランチ','午餐'],['喫煙ブース','吸菸室'],['喫煙室','吸菸室'],['喫煙','吸菸'],['分煙','分區吸菸'],['加熱式たばこのみ可','僅限加熱菸'],
      ['Open','開店'],['OPEN','開店'],['L.O.','最後點餐 '],['無料Wi-Fiをご利用いただけます。','可使用免費 Wi-Fi。'],
      ['青森県','青森縣'],['岩手県','岩手縣'],['宮城県','宮城縣'],['福島県','福島縣'],['秋田県','秋田縣'],['山形県','山形縣'],
      ['県','縣']
    ];
    replacements.forEach(function (pair) { text = text.split(pair[0]).join(pair[1]); });
    text = text.replace(/が(?=\d{4}\/)/g, '於').replace(/（([月火水木金土日])）/g, function (_, day) { return '（週' + {'月':'一','火':'二','水':'三','木':'四','金':'五','土':'六','日':'日'}[day] + '）'; });
    return text;
  }
  var navLabels = ['首頁','菜單','門市資訊'];
  document.querySelectorAll('#all_nav a').forEach(function (a,i) { a.setAttribute('data-zh-label',navLabels[i]); });
  function applyLanguage(lang) {
    var chinese = lang === 'zh-Hant';
    document.documentElement.lang = chinese ? 'zh-Hant' : 'ja';
    document.documentElement.setAttribute('xml:lang',document.documentElement.lang);
    nodes.forEach(function (item) { item.node.nodeValue = chinese && !item.keepOriginal ? translate(item.original) : item.original; });
    attrs.forEach(function (item) { item.el.setAttribute(item.name,chinese ? translate(item.original) : item.original); });
    document.title = chinese ? translate(title) : title;
    document.querySelectorAll('#all_nav a').forEach(function(a,i) { a.setAttribute('aria-label',chinese ? navLabels[i] : ['ホーム','メニュー','店舗情報'][i]); });
    document.querySelectorAll('[data-language]').forEach(function (button) {
      button.setAttribute('aria-pressed',String(button.getAttribute('data-language')===lang));
    });
    try { localStorage.setItem('hoshino-language',lang); } catch (e) { /* file:// 也可透過網址保留語言 */ }
    document.querySelectorAll('a[href]').forEach(function (a) {
      var raw = a.getAttribute('href');
      if (/^(index|menu|shop)\.html(?:[?#]|$)/.test(raw)) {
        var u = new URL(raw,location.href);u.searchParams.set('lang',lang);
        a.setAttribute('href',u.pathname.split('/').pop()+u.search+u.hash);
      }
    });
    try { var url = new URL(location.href);url.searchParams.set('lang',lang);history.replaceState(null,'',url.href); } catch (e) {}
  }
  document.querySelectorAll('[data-language]').forEach(function (button) {
    button.addEventListener('click',function () { applyLanguage(button.getAttribute('data-language')); });
  });
  var lang = new URLSearchParams(location.search).get('lang');
  if (!lang) { try { lang=localStorage.getItem('hoshino-language'); } catch (e) {} }
  applyLanguage(lang==='zh-Hant'?'zh-Hant':'ja');
})();
