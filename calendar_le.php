<?php
// 使用台灣時間，讓「今天」與台灣日期一致
date_default_timezone_set('Asia/Taipei');

// 沒有指定年月時，就顯示現在的年月
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$year = ($year !== false && $year !== null && $year >= 1 && $year <= 9999) ? $year : (int)date('Y');
$month = ($month !== false && $month !== null && $month >= 1 && $month <= 12) ? $month : (int)date('n');

// 和老師的範例一樣：先找出當月 1 號、星期和總天數
$firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$firstDayWeek = (int)$firstDay->format('w'); // 星期日是 0，星期六是 6
$theMonthDays = (int)$firstDay->format('t');
// 固定排 6 週，每個月的月曆一樣高，翻頁時頁面不會忽長忽短；多出來的格子用前後月份的日期補上
$theCalendarWeeks = 6;
$prev = $firstDay->modify('-1 month');
$next = $firstDay->modify('+1 month');
$prevMonthDays = (int)$prev->format('t'); // 用來補上前一個月月底的淡色日期
$todayYear = (int)date('Y');
$todayMonth = (int)date('n');
$isThisMonth = $year === $todayYear && $month === $todayMonth;
$months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
// 每個月份有自己的一幅紙雕場景和配色
// icon：舞台上的圖案；sky：天空上方顏色；glow：天空下方顏色；hill：前方山丘顏色
// light：天上掛的是月亮 moon、太陽 sun 或雲 cloud；fall：天空飄下來的雪 snow、雨 rain、花瓣 petal、落葉 leaf，空字串代表沒有
$scenes = [
    ['icon' => '❄', 'name' => '雪夜唱片屋', 'en' => 'SNOWY RECORD SHOP', 'sky' => '#7c94a1', 'glow' => '#b5c7c5', 'hill' => '#526974', 'light' => 'moon', 'fall' => 'snow'],
    ['icon' => '♥', 'name' => '心意花店', 'en' => 'LITTLE FLOWER SHOP', 'sky' => '#a97278', 'glow' => '#dbad9b', 'hill' => '#7c4f60', 'light' => 'sun', 'fall' => 'petal'],
    ['icon' => '✿', 'name' => '春日花園', 'en' => 'SPRING GARDEN', 'sky' => '#8aa49a', 'glow' => '#e0bcb1', 'hill' => '#617b70', 'light' => 'sun', 'fall' => 'petal'],
    ['icon' => '☂', 'name' => '雨天咖啡館', 'en' => 'RAINY DAY CAFE', 'sky' => '#82939c', 'glow' => '#c9b3a3', 'hill' => '#617784', 'light' => 'cloud', 'fall' => 'rain'],
    ['icon' => '♪', 'name' => '森林音樂會', 'en' => 'FOREST CONCERT', 'sky' => '#829b7b', 'glow' => '#d3c498', 'hill' => '#536b56', 'light' => 'sun', 'fall' => 'leaf'],
    ['icon' => '☀', 'name' => '夏日海邊', 'en' => 'SUMMER BY THE SEA', 'sky' => '#719ca0', 'glow' => '#e5c49e', 'hill' => '#4b7a7d', 'light' => 'sun', 'fall' => ''],
    ['icon' => '♫', 'name' => '夏夜音樂祭', 'en' => 'SUMMER MUSIC FEST', 'sky' => '#997b91', 'glow' => '#dcac91', 'hill' => '#695875', 'light' => 'moon', 'fall' => ''],
    ['icon' => '⛵', 'name' => '海邊小旅行', 'en' => 'A LITTLE SEA VOYAGE', 'sky' => '#749ca6', 'glow' => '#e8c79e', 'hill' => '#4e7a83', 'light' => 'sun', 'fall' => ''],
    ['icon' => '☾', 'name' => '月光唱片屋', 'en' => 'MOONLIGHT RECORD SHOP', 'sky' => '#7a6265', 'glow' => '#d39e72', 'hill' => '#564449', 'light' => 'moon', 'fall' => ''],
    ['icon' => '♬', 'name' => '秋日唱片行', 'en' => 'AUTUMN RECORDS', 'sky' => '#a97864', 'glow' => '#e2bd89', 'hill' => '#76564d', 'light' => 'sun', 'fall' => 'leaf'],
    ['icon' => '✦', 'name' => '星空演出', 'en' => 'UNDER THE STARS', 'sky' => '#706981', 'glow' => '#bf9a94', 'hill' => '#4f4b64', 'light' => 'moon', 'fall' => ''],
    ['icon' => '★', 'name' => '冬日禮物屋', 'en' => 'WINTER GIFT SHOP', 'sky' => '#806c72', 'glow' => '#d0aaa0', 'hill' => '#564c58', 'light' => 'moon', 'fall' => 'snow']
];
$sceneInfo = $scenes[$month - 1];
$weekdays = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
$today = date('Y-m-d');

// ===== 農曆 =====
// 1900～2100 年每一年的農曆資料，每個數字用二進位記錄：
// 最後 4 位是閏幾月（0 代表沒閏月）、往前 12 位是 1～12 月各是大月(30天)還是小月(29天)、最前面 1 位是閏月大小
const LUNAR_INFO = [
    0x04bd8, 0x04ae0, 0x0a570, 0x054d5, 0x0d260, 0x0d950, 0x16554, 0x056a0, 0x09ad0, 0x055d2, // 1900
    0x04ae0, 0x0a5b6, 0x0a4d0, 0x0d250, 0x1d255, 0x0b540, 0x0d6a0, 0x0ada2, 0x095b0, 0x14977, // 1910
    0x04970, 0x0a4b0, 0x0b4b5, 0x06a50, 0x06d40, 0x1ab54, 0x02b60, 0x09570, 0x052f2, 0x04970, // 1920
    0x06566, 0x0d4a0, 0x0ea50, 0x16a95, 0x05ad0, 0x02b60, 0x186e3, 0x092e0, 0x1c8d7, 0x0c950, // 1930
    0x0d4a0, 0x1d8a6, 0x0b550, 0x056a0, 0x1a5b4, 0x025d0, 0x092d0, 0x0d2b2, 0x0a950, 0x0b557, // 1940
    0x06ca0, 0x0b550, 0x15355, 0x04da0, 0x0a5b0, 0x14573, 0x052b0, 0x0a9a8, 0x0e950, 0x06aa0, // 1950
    0x0aea6, 0x0ab50, 0x04b60, 0x0aae4, 0x0a570, 0x05260, 0x0f263, 0x0d950, 0x05b57, 0x056a0, // 1960
    0x096d0, 0x04dd5, 0x04ad0, 0x0a4d0, 0x0d4d4, 0x0d250, 0x0d558, 0x0b540, 0x0b6a0, 0x195a6, // 1970
    0x095b0, 0x049b0, 0x0a974, 0x0a4b0, 0x0b27a, 0x06a50, 0x06d40, 0x0af46, 0x0ab60, 0x09570, // 1980
    0x04af5, 0x04970, 0x064b0, 0x074a3, 0x0ea50, 0x06b58, 0x05ac0, 0x0ab60, 0x096d5, 0x092e0, // 1990
    0x0c960, 0x0d954, 0x0d4a0, 0x0da50, 0x07552, 0x056a0, 0x0abb7, 0x025d0, 0x092d0, 0x0cab5, // 2000
    0x0a950, 0x0b4a0, 0x0baa4, 0x0ad50, 0x055d9, 0x04ba0, 0x0a5b0, 0x15176, 0x052b0, 0x0a930, // 2010
    0x07954, 0x06aa0, 0x0ad50, 0x05b52, 0x04b60, 0x0a6e6, 0x0a4e0, 0x0d260, 0x0ea65, 0x0d530, // 2020
    0x05aa0, 0x076a3, 0x096d0, 0x04afb, 0x04ad0, 0x0a4d0, 0x1d0b6, 0x0d250, 0x0d520, 0x0dd45, // 2030
    0x0b5a0, 0x056d0, 0x055b2, 0x049b0, 0x0a577, 0x0a4b0, 0x0aa50, 0x1b255, 0x06d20, 0x0ada0, // 2040
    0x14b63, 0x09370, 0x049f8, 0x04970, 0x064b0, 0x168a6, 0x0ea50, 0x06b20, 0x1a6c4, 0x0aae0, // 2050
    0x092e0, 0x0d2e3, 0x0c960, 0x0d557, 0x0d4a0, 0x0da50, 0x05d55, 0x056a0, 0x0a6d0, 0x055d4, // 2060
    0x052d0, 0x0a9b8, 0x0a950, 0x0b4a0, 0x0b6a6, 0x0ad50, 0x055a0, 0x0aba4, 0x0a5b0, 0x052b0, // 2070
    0x0b273, 0x06930, 0x07337, 0x06aa0, 0x0ad50, 0x14b55, 0x04b60, 0x0a570, 0x054e4, 0x0d160, // 2080
    0x0e968, 0x0d520, 0x0daa0, 0x16aa6, 0x056d0, 0x04ae0, 0x0a9d4, 0x0a2d0, 0x0d150, 0x0f252, // 2090
    0x0d520 // 2100
];

// 從 1970-01-01 起算的第幾天（純數學計算，不受時區和夏令時間影響）
function dayNumber(int $y, int $m, int $d): int
{
    $y -= $m <= 2 ? 1 : 0;
    $era = intdiv($y >= 0 ? $y : $y - 399, 400);
    $yoe = $y - $era * 400;
    $doy = intdiv(153 * ($m + ($m > 2 ? -3 : 9)) + 2, 5) + $d - 1;
    $doe = $yoe * 365 + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;
    return $era * 146097 + $doe - 719468;
}

function lunarLeapMonth(int $y): int
{
    return LUNAR_INFO[$y - 1900] & 0xf;
}

function lunarLeapDays(int $y): int
{
    return lunarLeapMonth($y) ? ((LUNAR_INFO[$y - 1900] & 0x10000) ? 30 : 29) : 0;
}

function lunarMonthDays(int $y, int $m): int
{
    return (LUNAR_INFO[$y - 1900] & (0x10000 >> $m)) ? 30 : 29;
}

function lunarYearDays(int $y): int
{
    $sum = 348;
    for ($bit = 0x8000; $bit > 0x8; $bit >>= 1) {
        $sum += (LUNAR_INFO[$y - 1900] & $bit) ? 1 : 0;
    }
    return $sum + lunarLeapDays($y);
}

// 國曆轉農曆；超出 1900-01-31～2100-12-31 的範圍時回傳 null
function solarToLunar(int $y, int $m, int $d): ?array
{
    $offset = dayNumber($y, $m, $d) - dayNumber(1900, 1, 31); // 1900-01-31 是農曆 1900 年正月初一
    if ($offset < 0 || $y > 2100) return null;
    // 先一年一年扣，找出是農曆哪一年
    for ($lunarYear = 1900; $lunarYear <= 2100; $lunarYear++) {
        $days = lunarYearDays($lunarYear);
        if ($offset < $days) break;
        $offset -= $days;
    }
    // 再一個月一個月扣，閏月接在同名月份後面
    $leap = lunarLeapMonth($lunarYear);
    for ($lunarMonth = 1; $lunarMonth <= 12; $lunarMonth++) {
        $days = lunarMonthDays($lunarYear, $lunarMonth);
        if ($offset < $days) return ['year' => $lunarYear, 'month' => $lunarMonth, 'day' => $offset + 1, 'leap' => false];
        $offset -= $days;
        if ($lunarMonth === $leap) {
            $days = lunarLeapDays($lunarYear);
            if ($offset < $days) return ['year' => $lunarYear, 'month' => $lunarMonth, 'day' => $offset + 1, 'leap' => true];
            $offset -= $days;
        }
    }
    return null;
}

// 格子裡的農曆小字：初一顯示月份（例如「臘月」），其他天顯示「初五」「廿三」
// $full 為 true 時回傳完整寫法，例如「臘月廿三」，給便條紙標題用
function lunarText(array $lunar, bool $full = false): string
{
    $monthNames = ['正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '臘'];
    $digits = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
    $monthName = ($lunar['leap'] ? '閏' : '') . $monthNames[$lunar['month'] - 1] . '月';
    $d = $lunar['day'];
    if ($d === 1 && !$full) return $monthName;
    $dayName = [10 => '初十', 20 => '二十', 30 => '三十'][$d] ?? ['初', '十', '廿'][intdiv($d, 10)] . $digits[$d % 10];
    return ($full ? $monthName : '') . $dayName;
}

// ===== 二十四節氣 =====
// 用天文公式算出太陽在天空中的位置（黃經），每走 15 度就是一個節氣
function sunLongitude(float $jd): float
{
    $t = ($jd - 2451545.0) / 36525;
    $l0 = 280.46646 + 36000.76983 * $t + 0.0003032 * $t * $t;
    $m = deg2rad(357.52911 + 35999.05029 * $t - 0.0001537 * $t * $t);
    $c = (1.914602 - 0.004817 * $t - 0.000014 * $t * $t) * sin($m) + (0.019993 - 0.000101 * $t) * sin(2 * $m) + 0.000289 * sin(3 * $m);
    $omega = deg2rad(125.04 - 1934.136 * $t);
    return fmod($l0 + $c - 0.00569 - 0.00478 * sin($omega) + 3600, 360);
}

// 回傳某個月的節氣，格式是 [日 => 名稱]；每個國曆月剛好有兩個節氣
function monthSolarTerms(int $y, int $m): array
{
    static $cache = [];
    if (isset($cache["$y-$m"])) return $cache["$y-$m"];
    // 依黃經 0、15、30…度排列，0 度是春分
    $names = ['春分', '清明', '穀雨', '立夏', '小滿', '芒種', '夏至', '小暑', '大暑', '立秋', '處暑', '白露', '秋分', '寒露', '霜降', '立冬', '小雪', '大雪', '冬至', '小寒', '大寒', '立春', '雨水', '驚蟄'];
    $first = (285 + ($m - 1) * 30) % 360; // 1 月的第一個節氣「小寒」在 285 度
    $targets = [$first, ($first + 15) % 360];
    $terms = [];
    $days = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m)))->format('t');
    for ($d = 1; $d <= $days; $d++) {
        // 台灣時間當天 24:00 等於世界時 16:00
        $end = dayNumber($y, $m, $d) + 2440587.5 + 16 / 24;
        foreach ($targets as $target) {
            $before = fmod(sunLongitude($end - 1) - $target + 540, 360) - 180;
            $after = fmod(sunLongitude($end) - $target + 540, 360) - 180;
            if ($before < 0 && $after >= 0) $terms[$d] = $names[$target / 15];
        }
    }
    return $cache["$y-$m"] = $terms;
}

// ===== 節日 =====
// off 為 true 代表國定假日（放假），false 只是節日或紀念日；可以在這裡自行增加固定日期的節日
// 放假規定以現行法規為準，沒有計算補假與調整放假
$solarFestivals = [
    '01-01' => ['元旦', true],
    '02-14' => ['情人節', false],
    '02-28' => ['和平紀念日', true],
    '04-04' => ['兒童節', true],
    '05-01' => ['勞動節', true],
    '08-08' => ['父親節', false],
    '09-28' => ['教師節', true],
    '10-10' => ['國慶日', true],
    '10-25' => ['光復節', true],
    '11-11' => ['光棍紀念日', false],
    '12-25' => ['行憲紀念日', true]
];
$lunarFestivals = [
    '01-01' => ['春節', true],
    '01-02' => ['春節', true],
    '01-03' => ['春節', true],
    '01-15' => ['元宵節', false],
    '05-05' => ['端午節', true],
    '07-07' => ['七夕', false],
    '07-15' => ['中元節', false],
    '08-15' => ['中秋節', true],
    '09-09' => ['重陽節', false]
];

// 回傳某一天的農曆、節氣與節日
function dayInfo(int $y, int $m, int $d): array
{
    global $solarFestivals, $lunarFestivals;
    $lunar = $y >= 1900 && $y <= 2100 ? solarToLunar($y, $m, $d) : null;
    $term = $lunar ? (monthSolarTerms($y, $m)[$d] ?? null) : null;
    $events = [];
    $key = sprintf('%02d-%02d', $m, $d);
    if (isset($solarFestivals[$key])) $events[] = $solarFestivals[$key];
    // 母親節是五月的第二個星期日
    if ($m === 5 && $d >= 8 && $d <= 14 && (dayNumber($y, $m, $d) + 4) % 7 === 0) $events[] = ['母親節', false];
    if ($lunar && !$lunar['leap']) {
        $lunarKey = sprintf('%02d-%02d', $lunar['month'], $lunar['day']);
        if (isset($lunarFestivals[$lunarKey])) $events[] = $lunarFestivals[$lunarKey];
        if ($lunar['month'] === 12 && $lunar['day'] === lunarMonthDays($lunar['year'], 12)) $events[] = ['除夕', true];
    }
    if ($term === '清明') $events[] = ['掃墓節', true];
    return ['lunar' => $lunar, 'term' => $term, 'events' => $events, 'off' => in_array(true, array_column($events, 1), true)];
}

// 從今天往後找下一個國定假日，最多找一年
function nextHoliday(DateTimeImmutable $from): ?array
{
    for ($i = 0; $i <= 366; $i++) {
        $date = $from->modify("+$i day");
        $info = dayInfo((int)$date->format('Y'), (int)$date->format('n'), (int)$date->format('j'));
        foreach ($info['events'] as [$name, $off]) {
            if ($off) return ['name' => $name, 'date' => $date, 'days' => $i];
        }
    }
    return null;
}
$holiday = nextHoliday(new DateTimeImmutable('today'));

// 干支紀年與生肖，例如 2026 年是丙午馬年
$stems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
$branches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
$zodiac = ['鼠', '牛', '虎', '兔', '龍', '蛇', '馬', '羊', '猴', '雞', '狗', '豬'];
$cycle = (($year - 4) % 60 + 60) % 60;
$ganzhi = $stems[$cycle % 10] . $branches[$cycle % 12] . $zodiac[$cycle % 12] . '年';

// 背景粒子依季節變化：冬雪、春花、夏螢火蟲、秋落葉
$season = ['winter', 'winter', 'spring', 'spring', 'spring', 'summer', 'summer', 'summer', 'autumn', 'autumn', 'autumn', 'winter'][$month - 1];
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $year ?> 年 <?= $month ?> 月｜月光唱片屋萬年曆</title>
    <style>
        /* 書頁中線、紙張堆疊與翻頁動畫 */
        /* 書頁以中線為軸，正反兩面各顯示實際的月曆畫面 */
        .ticket {
            perspective: 1800px;
            position: relative
        }

        .paper {
            transform-style: preserve-3d
        }

        .layout {
            position: relative
        }

        /* 中線是真正的格子，位置會跟著版面寬度自動對齊 */
        .layout-divider {
            width: 22px;
            justify-self: center;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, #6b514019, transparent)
        }

        .flipping-page {
            position: absolute;
            z-index: 20;
            top: 0;
            width: 50%;
            height: 100%;
            transform-style: preserve-3d;
            pointer-events: none;
            will-change: transform
        }

        .flipping-page.next {
            left: 50%;
            transform-origin: left center;
            animation: turnNext .9s cubic-bezier(.45, 0, .3, 1) forwards
        }

        .flipping-page.prev {
            left: 0;
            transform-origin: right center;
            animation: turnPrev .9s cubic-bezier(.45, 0, .3, 1) forwards
        }

        .page-face {
            position: absolute;
            inset: 0;
            overflow: hidden;
            backface-visibility: hidden;
            background: #f3e8d8;
            box-shadow: 0 0 20px #44302a38
        }

        .page-face.back {
            transform: rotateY(180deg)
        }

        .page-face .paper {
            position: absolute;
            top: 0;
            max-width: none;
            margin: 0;
            pointer-events: none;
            mask: none
        }

        @keyframes turnNext {
            from {
                transform: rotateY(0)
            }

            to {
                transform: rotateY(-180deg)
            }
        }

        @keyframes turnPrev {
            from {
                transform: rotateY(0)
            }

            to {
                transform: rotateY(180deg)
            }
        }

        @media(max-width:700px) {
            .layout-divider {
                display: none
            }
        }

        @media(prefers-reduced-motion:reduce) {
            .flipping-page {
                animation-duration: .01ms !important
            }
        }

        /* 先替捲軸留好位置，捲軸出現或消失時版面不會左右跳動 */
        html {
            scrollbar-gutter: stable
        }

        /* 滑鼠游標換成賓士貓的貓掌：黑毛、粉紅肉球、米色外框（在深色背景上也看得到）
           --paw 是平常的貓掌；--paw-click 用在可以點的地方，貓掌微微歪斜、肉球更粉紅 */
        :root {
            --paw: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'><g fill='%23fff0dc'><ellipse cx='16' cy='20.5' rx='11.5' ry='10'/><circle cx='6.5' cy='11.5' r='5.2'/><circle cx='12.5' cy='6.2' r='5.2'/><circle cx='19.5' cy='6.2' r='5.2'/><circle cx='25.5' cy='11.5' r='5.2'/></g><g fill='%232b2629'><ellipse cx='16' cy='20.5' rx='10' ry='8.5'/><circle cx='6.5' cy='11.5' r='3.8'/><circle cx='12.5' cy='6.2' r='3.8'/><circle cx='19.5' cy='6.2' r='3.8'/><circle cx='25.5' cy='11.5' r='3.8'/></g><g fill='%23e9a3a8'><ellipse cx='16' cy='21.5' rx='5.8' ry='4.6'/><circle cx='6.5' cy='11.5' r='2.1'/><circle cx='12.5' cy='6.2' r='2.1'/><circle cx='19.5' cy='6.2' r='2.1'/><circle cx='25.5' cy='11.5' r='2.1'/></g></svg>") 16 16, auto;
            --paw-click: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'><g transform='rotate(-18 16 16)'><g fill='%23fff0dc'><ellipse cx='16' cy='20.5' rx='11.5' ry='10'/><circle cx='6.5' cy='11.5' r='5.2'/><circle cx='12.5' cy='6.2' r='5.2'/><circle cx='19.5' cy='6.2' r='5.2'/><circle cx='25.5' cy='11.5' r='5.2'/></g><g fill='%232b2629'><ellipse cx='16' cy='20.5' rx='10' ry='8.5'/><circle cx='6.5' cy='11.5' r='3.8'/><circle cx='12.5' cy='6.2' r='3.8'/><circle cx='19.5' cy='6.2' r='3.8'/><circle cx='25.5' cy='11.5' r='3.8'/></g><g fill='%23ff8fa3'><ellipse cx='16' cy='21.5' rx='6.2' ry='5'/><circle cx='6.5' cy='11.5' r='2.4'/><circle cx='12.5' cy='6.2' r='2.4'/><circle cx='19.5' cy='6.2' r='2.4'/><circle cx='25.5' cy='11.5' r='2.4'/></g></g></svg>") 16 16, pointer
        }

        body {
            cursor: var(--paw)
        }

        /* 輸入文字的地方保留原本的文字游標，比較好對準 */
        input[type="number"],
        textarea {
            cursor: text
        }

        a,
        button,
        select {
            cursor: var(--paw-click)
        }

        * {
            box-sizing: border-box
        }

        /* 只給螢幕閱讀器看的文字，視覺上隱藏 */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0
        }

        :root {
            --ink: #30252a;
            --red: #9c3f45;
            --paper: #f3e8d8;
            --bg: #d2bfb1;
            --line: #cbb5a5;
            --muted: #987b6c
        }

        body {
            margin: 0;
            isolation: isolate;
            min-height: 100vh;
            background: #65443f;
            color: var(--ink);
            font-family: "Noto Sans TC", "PingFang TC", "Microsoft JhengHei", sans-serif;
            padding: 40px 16px 55px
        }

        .topline {
            max-width: 1010px;
            margin: 0 auto 15px;
            display: flex;
            justify-content: space-between;
            color: #f5e9d2;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .25em
        }

        .ticket {
            max-width: 1010px;
            margin: auto;
            filter: drop-shadow(0 22px 24px #523b3038)
        }

        .paper {
            /* 疊一層極淡的噪點，模擬紙張纖維 */
            background-color: var(--paper);
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 .35  0 0 0 0 .25  0 0 0 0 .2  0 0 0 .09 0'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>");
            padding: 38px 48px 32px;
            position: relative;
            mask: radial-gradient(circle 7px at 0 9px, transparent 98%, #000 105%) 0 0/51% 18px repeat-y, radial-gradient(circle 7px at 100% 9px, transparent 98%, #000 105%) 100% 0/51% 18px repeat-y;
        }

        .paper:before {
            content: "";
            position: absolute;
            inset: 13px 20px;
            border: 1px solid #b99e8e;
            pointer-events: none
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: start;
            border-bottom: 1px solid #cbb5a5;
            padding-bottom: 18px;
            gap: 12px
        }

        .brand {
            font-family: Georgia, serif;
            letter-spacing: .19em;
            font-size: 12px
        }

        .brand small {
            display: block;
            font-family: sans-serif;
            letter-spacing: .13em;
            margin-top: 7px;
            color: #8c7167;
            font-size: 10px
        }

        .serial {
            color: #987b6c;
            font-size: 10px;
            letter-spacing: .15em;
            text-align: right
        }

        .layout {
            display: grid;
            grid-template-columns: 42% 1px 1fr;
            gap: 35px;
            padding-top: 20px
        }

        .art-title {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-bottom: 12px
        }

        .art-title p {
            margin: 0;
            color: var(--red);
            font-size: 10px;
            letter-spacing: .2em
        }

        .art-title span {
            font: italic 12px Georgia;
            color: #987b6c
        }

        /* 多層房屋形狀與陰影，模擬紙張挖洞後的景深 */
        .window {
            position: relative;
            height: 305px;
            background: #c7ae94;
            clip-path: polygon(50% 0, 100% 34%, 100% 100%, 0 100%, 0 34%);
            filter: drop-shadow(0 7px 4px #806959);
            padding: 12px 13px 0
        }

        .scene {
            position: absolute;
            inset: 14px 13px 0;
            clip-path: polygon(50% 0, 100% 34%, 100% 100%, 0 100%, 0 34%);
            overflow: hidden;
            background: linear-gradient(#7a6265, #a97967 58%, #d39e72);
            box-shadow: inset 0 14px 25px #392e35aa, inset 12px 0 16px #4c343b88, inset -12px 0 16px #4c343b88
        }

        .moon {
            position: absolute;
            top: 50px;
            right: 55px;
            width: 48px;
            height: 48px;
            background: #f3d9a8;
            border-radius: 50%;
            box-shadow: 0 0 30px #ffdfab9c
        }

        .stars {
            position: absolute;
            top: 50px;
            left: 24%;
            color: #f2dcb5;
            letter-spacing: 16px;
            font-size: 12px
        }

        /* 白天的月份把月亮換成太陽，外圈一層淡淡的光暈 */
        .sun {
            position: absolute;
            top: 44px;
            right: 50px;
            width: 54px;
            height: 54px;
            background: #ffe3a3;
            border-radius: 50%;
            box-shadow: 0 0 0 9px #ffe3a333, 0 0 0 19px #ffe3a31a, 0 0 42px #ffd98acc
        }

        /* 雨天的雲：三個圓疊在一條圓角長條上 */
        .cloud {
            position: absolute;
            top: 70px;
            right: 36px;
            width: 92px;
            height: 26px;
            background: #e4dcd4;
            border-radius: 13px;
            box-shadow: -58px 22px 0 -4px #d3cac2
        }

        .cloud:before,
        .cloud:after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: inherit
        }

        .cloud:before {
            width: 42px;
            height: 42px;
            left: 14px;
            top: -22px
        }

        .cloud:after {
            width: 30px;
            height: 30px;
            left: 46px;
            top: -13px
        }

        /* 從天空飄下來的雪、雨、花瓣、落葉；位置與速度由 PHP 寫在 style 上 */
        .fall {
            position: absolute;
            inset: 0;
            z-index: 3;
            pointer-events: none
        }

        .fall i {
            position: absolute;
            top: -16px;
            left: var(--x);
            animation: fallDown var(--t) linear var(--d) infinite
        }

        .fall-snow i {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #fffaf2;
            opacity: .85
        }

        .fall-rain i {
            width: 1.5px;
            height: 14px;
            background: #eef3f5aa;
            animation-name: rainDown
        }

        .fall-petal i {
            width: 8px;
            height: 5px;
            border-radius: 60% 0;
            background: #f4c6c0
        }

        .fall-leaf i {
            width: 9px;
            height: 6px;
            border-radius: 0 70%;
            background: #d98b4f
        }

        @keyframes fallDown {
            from {
                transform: translate(0, 0) rotate(0)
            }

            50% {
                transform: translate(14px, 160px) rotate(160deg)
            }

            to {
                transform: translate(-6px, 330px) rotate(330deg)
            }
        }

        @keyframes rainDown {
            from {
                transform: translate(0, 0) rotate(12deg)
            }

            to {
                transform: translate(-60px, 330px) rotate(12deg)
            }
        }

        /* 不想看動畫的人：粒子停在畫面中各自的位置 */
        @media(prefers-reduced-motion:reduce) {
            .fall i {
                animation: none;
                transform: translateY(var(--y))
            }
        }

        .hill {
            position: absolute;
            bottom: 0;
            left: -20%;
            width: 150%;
            height: 110px;
            background: #564449;
            border-radius: 50% 50% 0 0
        }

        .hill.back {
            bottom: 40px;
            left: -40%;
            background: #876968;
            height: 90px
        }

        .tree {
            position: absolute;
            bottom: 13px;
            width: 10px;
            height: 76px;
            background: #47383b
        }

        .tree:before {
            content: '';
            position: absolute;
            width: 53px;
            height: 73px;
            bottom: 25px;
            left: -21px;
            border-radius: 50%;
            background: #55534a;
            box-shadow: inset -10px -6px #444641
        }

        .tree.one {
            left: 10%
        }

        .tree.two {
            right: 9%;
            transform: scale(.8);
            transform-origin: bottom
        }

        .art-caption {
            display: flex;
            justify-content: space-between;
            padding-top: 14px;
            color: #856a60;
            font-size: 11px;
            letter-spacing: .13em
        }

        .art-note {
            font: italic 13px Georgia;
            color: #947d71;
            margin: 15px 0 0;
            line-height: 1.5
        }

        /* 滑鼠在整張月曆紙上移動時，出現跟著游標移動的暖色反光 */
        .paper:after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 4;
            pointer-events: none;
            opacity: 0;
            transition: opacity .35s ease;
            background: radial-gradient(520px circle at var(--gx, 50%) var(--gy, 50%), rgba(255, 190, 110, .55), rgba(255, 160, 90, .15) 40%, transparent 70%);
            mix-blend-mode: screen
        }

        .paper.glare-on:after {
            opacity: 1
        }

        .calendar-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 8px
        }

        .month-number {
            font: normal 85px/.8 Georgia;
            color: var(--red);
            letter-spacing: -.08em
        }

        .date-title {
            text-align: right
        }

        .date-title strong {
            display: block;
            font: 24px Georgia;
            letter-spacing: .08em
        }

        .date-title span {
            font-size: 11px;
            color: #967b70;
            letter-spacing: .16em
        }

        /* 告訴第一次來的人：日期可以點，用來寫筆記 */
        .date-title .date-tip {
            display: block;
            margin-top: 2px;
            line-height: 1.3;
            color: var(--red);
            letter-spacing: .08em
        }

        .controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin: 16px 0 8px
        }

        .controls a,
        .controls button,
        .controls .is-off {
            border: 1px solid #b99d8b;
            background: transparent;
            color: #70564f;
            text-decoration: none;
            padding: 7px 10px;
            font: 12px sans-serif;
            cursor: var(--paw-click)
        }

        .controls a:hover,
        .controls button:hover {
            background: #9c3f45;
            color: #fff
        }

        /* 已經在今天的月份、或到了年份盡頭時，按鈕變淡、不能按 */
        .controls .is-off {
            opacity: .45;
            cursor: var(--paw)
        }

        /* 鍵盤操作時也要看得到目前焦點 */
        .controls a:focus-visible,
        .controls button:focus-visible,
        .controls input:focus-visible,
        .controls select:focus-visible {
            outline: 2px solid var(--red);
            outline-offset: 2px
        }

        .controls form {
            display: flex;
            gap: 4px;
            align-items: center
        }

        .controls input,
        .controls select {
            min-width: 0;
            background: #f9f2e9;
            color: #493b37;
            border: 1px solid #c8b2a3;
            padding: 7px 4px;
            font-size: 12px
        }

        .controls input {
            width: 66px
        }

        .controls select {
            width: 57px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed
        }

        th {
            padding: 8px 0;
            border-bottom: 1px solid #bfa99b;
            font-size: 10px;
            letter-spacing: .08em;
            color: #8c7167
        }

        th:first-child,
        th:last-child {
            color: var(--red)
        }

        /* 日期、農曆、節日各一行，固定高度讓每一列一樣高 */
        td {
            text-align: center;
            height: 60px;
            vertical-align: top;
            padding: 6px 1px 2px;
            font: 15px Georgia;
            border-bottom: 1px solid #dfd0c3
        }

        td.weekend {
            color: var(--red)
        }

        td:hover {
            background: #e9dac9
        }

        /* 前後月份的日期只當作陪襯，顏色調淡 */
        td.other-month {
            opacity: .32
        }

        td.other-month:hover {
            background: none
        }

        .day {
            display: inline-grid;
            place-items: center;
            width: 25px;
            height: 25px;
            border-radius: 50%
        }

        .today .day {
            background: var(--red);
            color: #fff;
            box-shadow: 0 0 0 3px var(--paper), 0 0 0 4px var(--red)
        }

        .special {
            display: block;
            font: 10px/1.3 sans-serif;
            color: #a45f58;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .footer {
            border-top: 1px solid var(--line);
            margin-top: 18px;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            color: var(--muted);
            letter-spacing: .12em;
            font-size: 11px
        }

        .footer b {
            color: #9c3f45
        }

        @media(max-width:700px) {
            body {
                padding: 22px 10px
            }

            .paper {
                padding: 30px 32px
            }

            .layout {
                grid-template-columns: 1fr;
                gap: 28px
            }

            .window {
                height: 260px;
                max-width: 360px;
                margin: auto
            }

            .art-title,
            .art-caption,
            .art-note,
            .countdown {
                max-width: 360px;
                margin-left: auto;
                margin-right: auto
            }

            .month-number {
                font-size: 68px
            }

            .controls a,
            .controls button {
                padding: 7px 6px
            }

            /* 手機上：上月、下月在第一行兩端，輸入框和「今天」獨立一行 */
            .controls form {
                order: 3;
                width: 100%;
                justify-content: center
            }

            .controls input {
                width: 58px
            }

            .serial {
                font-size: 8px
            }

            .topline {
                padding: 0 8px
            }
        }

        /* 整個畫面的網點底紋，從上往下逐漸淡出 */
        body:before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background: radial-gradient(circle, #fff0dc 1px, transparent 1.6px) 0 0/6px 6px;
            opacity: .14;
            -webkit-mask-image: linear-gradient(to bottom, #000 0%, transparent 85%);
            mask-image: linear-gradient(to bottom, #000 0%, transparent 85%)
        }

        /* 背景像微小的暖光在空中慢慢漂浮 */
        /* 夕陽房間、窗光和兩隻賓士貓 */
        .room {
            position: fixed;
            inset: 0;
            z-index: -2;
            overflow: hidden;
            pointer-events: none
        }

        .room svg {
            display: block;
            width: 100%;
            height: 100%
        }

        .room-light {
            animation: windowGlow 10s ease-in-out infinite alternate
        }

        @keyframes windowGlow {
            from {
                opacity: .42
            }

            to {
                opacity: .7
            }
        }

        .cat {
            position: fixed;
            z-index: -1;
            bottom: 7vh;
            width: clamp(125px, 12vw, 188px);
            height: auto;
            pointer-events: none;
            overflow: visible
        }

        .cat-one {
            left: max(12px, calc((100vw - 1440px)/2 + 8px))
        }

        .cat-two {
            right: max(12px, calc((100vw - 1440px)/2 + 8px));
            bottom: 10vh
        }

        .ambient {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: -1
        }

        .ambient i {
            position: absolute;
            bottom: 7%;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #ffe2a5;
            box-shadow: 0 0 10px #ffd28b;
            opacity: 0;
            left: var(--x);
            animation: floatLight var(--t) linear var(--d) infinite
        }

        @keyframes floatLight {
            0% {
                transform: translate(0, 0);
                opacity: 0
            }

            15% {
                opacity: .55
            }

            80% {
                opacity: .35
            }

            100% {
                transform: translate(48px, -75vh);
                opacity: 0
            }
        }

        @media(max-width:1290px) {
            body {
                padding-top: 218px
            }

            .cat {
                position: absolute;
                top: 32px;
                bottom: auto;
                width: 125px
            }

            .cat-one {
                left: calc(50% - 134px)
            }

            .cat-two {
                right: calc(50% - 134px);
                top: 39px
            }
        }

        @media(prefers-reduced-motion:reduce) {

            .room-light,
            .ambient i {
                animation: none
            }
        }

        .scene-ornament {
            position: absolute;
            left: 50%;
            bottom: 59px;
            transform: translateX(-50%);
            font: normal 105px/1 Georgia, serif;
            color: #f5d8a2;
            text-shadow: 0 4px #ab7165, 0 9px 15px #35282b88;
            z-index: 2
        }

        .mini-stage {
            position: absolute;
            left: 15%;
            right: 15%;
            bottom: 24px;
            height: 32px;
            background: #c99a77;
            border-radius: 50%;
            box-shadow: 0 8px 0 #806051, 0 13px 15px #382d2c66;
            text-align: center;
            z-index: 1
        }

        .mini-stage span {
            position: relative;
            top: 12px;
            font: bold 7px Georgia;
            color: #503e3a;
            letter-spacing: .08em
        }

        /* ===== 季節背景粒子：冬雪、春花瓣、夏螢火蟲、秋落葉 ===== */
        body[data-season="winter"] .ambient i,
        body[data-season="spring"] .ambient i,
        body[data-season="autumn"] .ambient i {
            top: -3%;
            bottom: auto;
            box-shadow: none;
            animation-name: ambientFall
        }

        body[data-season="winter"] .ambient i {
            width: 6px;
            height: 6px;
            background: #fff8ee
        }

        body[data-season="spring"] .ambient i {
            width: 10px;
            height: 7px;
            border-radius: 60% 0;
            background: #f2bdb8
        }

        body[data-season="autumn"] .ambient i {
            width: 11px;
            height: 7px;
            border-radius: 0 70%;
            background: #d98b4f
        }

        body[data-season="summer"] .ambient i {
            background: #eef7a0;
            box-shadow: 0 0 12px 2px #d9ec7a;
            animation-name: firefly
        }

        @keyframes ambientFall {
            0% {
                transform: translate(0, 0) rotate(0);
                opacity: 0
            }

            10% {
                opacity: .7
            }

            50% {
                transform: translate(40px, 52vh) rotate(200deg)
            }

            90% {
                opacity: .6
            }

            100% {
                transform: translate(-10px, 104vh) rotate(400deg);
                opacity: 0
            }
        }

        @keyframes firefly {
            0% {
                transform: translate(0, 0);
                opacity: 0
            }

            20% {
                opacity: .9
            }

            30% {
                opacity: .2
            }

            45% {
                transform: translate(-30px, -30vh);
                opacity: .9
            }

            70% {
                transform: translate(25px, -50vh);
                opacity: .3
            }

            100% {
                transform: translate(-10px, -75vh);
                opacity: 0
            }
        }

        /* ===== 房間光線依真實時間變化：白天、黃昏（預設）、夜晚 ===== */
        .room stop,
        .room circle,
        .room path {
            transition: stop-color 2s ease, fill 2s ease, r 2s ease
        }

        .room svg {
            transition: filter 2s ease
        }

        body[data-time="day"] .wall-a {
            stop-color: #c4927a
        }

        body[data-time="day"] .wall-b {
            stop-color: #7b5650
        }

        body[data-time="day"] .sky-a {
            stop-color: #9cc3d3
        }

        body[data-time="day"] .sky-b {
            stop-color: #f4e3c3
        }

        body[data-time="day"] .room-sun {
            fill: #fff3d2
        }

        body[data-time="day"] .win-hill-a {
            fill: #8fa78f
        }

        body[data-time="day"] .win-hill-b {
            fill: #6d8a76
        }

        body[data-time="night"] .wall-a {
            stop-color: #4f4152
        }

        body[data-time="night"] .wall-b {
            stop-color: #261f2b
        }

        body[data-time="night"] .sky-a {
            stop-color: #1d2442
        }

        body[data-time="night"] .sky-b {
            stop-color: #4a4d72
        }

        body[data-time="night"] .room-sun {
            fill: #f4ecd2;
            r: 30px
        }

        body[data-time="night"] .win-hill-a {
            fill: #3a3550
        }

        body[data-time="night"] .win-hill-b {
            fill: #2a2639
        }

        body[data-time="night"] .room-floor {
            fill: #2b2026
        }

        body[data-time="night"] .room-light {
            animation: none;
            opacity: .12
        }

        body[data-time="night"] {
            background: #2e2229
        }

        /* ===== 上方的音效與列印按鈕 ===== */
        .topline {
            align-items: center;
            gap: 10px
        }

        .cat-tips {
            max-width: 1010px;
            margin: 20px auto 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px 22px;
            color: #f5e9d2cc;
            font-size: 12px;
            letter-spacing: .08em
        }

        .cat-tips span:first-child {
            color: #f5e9d2;
            font-weight: bold
        }

        .cat-tips b {
            color: #ffd9a0;
            font-weight: normal
        }

        .topline-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end
        }

        .topline button {
            border: 1px solid #f5e9d266;
            background: transparent;
            color: #f5e9d2;
            font: inherit;
            letter-spacing: .1em;
            padding: 4px 9px;
            cursor: var(--paw-click)
        }

        .topline button:hover,
        .topline button[aria-pressed="true"] {
            background: #f5e9d2;
            color: #65443f
        }

        .topline button:focus-visible,
        .play-record:focus-visible,
        .cell:focus-visible,
        .note-dialog button:focus-visible,
        .note-dialog textarea:focus-visible {
            outline: 2px solid var(--red);
            outline-offset: 2px
        }

        /* ===== 日期格子：農曆、節氣、節日 ===== */
        .cell {
            appearance: none;
            display: block;
            position: relative;
            width: 100%;
            min-height: 40px;
            padding: 0;
            border: 0;
            background: none;
            color: inherit;
            font: inherit;
            cursor: var(--paw-click)
        }

        .lunar {
            display: block;
            white-space: nowrap;
            margin-top: 0;
            font: 10px/1.2 sans-serif;
            letter-spacing: .03em;
            color: #a08a7e
        }

        .lunar.month-start {
            color: var(--red)
        }

        .lunar.term {
            color: #5f7d6b;
            font-weight: bold
        }

        td.holiday {
            color: var(--red)
        }

        .special {
            color: #9a8076
        }

        .special.off {
            color: #a45f58;
            font-weight: bold
        }

        /* 有筆記的日子，右上角出現一張小黑膠唱片 */
        .cell.has-note:after {
            content: "";
            position: absolute;
            top: -4px;
            right: 4px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: radial-gradient(circle, #c9534e 0 1.5px, #352b30 2px)
        }

        .countdown {
            margin: 14px 0 0;
            font-size: 12px;
            letter-spacing: .08em;
            color: var(--muted)
        }

        .countdown b {
            color: var(--red)
        }

        /* ===== 唱片機按鈕 ===== */
        .play-record {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            border: 1px solid #b99d8b;
            background: transparent;
            color: #70564f;
            padding: 6px 12px 6px 7px;
            font: 12px sans-serif;
            letter-spacing: .08em;
            cursor: var(--paw-click)
        }

        .play-record:hover {
            background: #9c3f45;
            color: #fff
        }

        .play-vinyl {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: radial-gradient(circle, #d9b982 0 2px, #b9534e 2.5px 5px, #352b30 5.5px), repeating-radial-gradient(circle, #352b30 0 1px, #4d3f46 1.5px 2px)
        }

        body.record-playing .play-vinyl {
            animation: spin 1.8s linear infinite
        }

        body.record-playing .scene-ornament {
            animation: bob 1.7s ease-in-out infinite
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        @keyframes bob {
            50% {
                transform: translate(-50%, -8px) rotate(-4deg)
            }
        }

        /* ===== 會互動的貓 ===== */
        .cat {
            pointer-events: auto;
            cursor: var(--paw-click);
            -webkit-tap-highlight-color: transparent
        }

        .cat:focus-visible {
            outline: 2px dashed #f5e9d2;
            outline-offset: 4px;
            border-radius: 12px
        }

        .cat-eyes {
            transform-box: fill-box;
            transform-origin: center;
            animation: blink 5.5s infinite
        }

        .cat-two .cat-eyes {
            animation-duration: 7.3s;
            animation-delay: -2s
        }

        .cat-look {
            transition: transform .25s ease-out
        }

        @keyframes blink {

            0%,
            93%,
            100% {
                transform: scaleY(1)
            }

            95.5% {
                transform: scaleY(.1)
            }
        }

        .cat.hop {
            animation: hop .45s ease-out
        }

        @keyframes hop {
            40% {
                transform: translateY(-14px)
            }
        }

        .meow {
            position: absolute;
            z-index: 30;
            padding: 6px 12px;
            border-radius: 14px;
            background: #f5e9d2;
            color: #65443f;
            font-size: 14px;
            font-weight: bold;
            pointer-events: none;
            transform: translateX(-50%);
            animation: meowUp 1.4s ease-out forwards
        }

        .meow:after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: -6px;
            border: 6px solid transparent;
            border-bottom: 0;
            border-top-color: #f5e9d2;
            transform: translateX(-50%)
        }

        @keyframes meowUp {
            from {
                opacity: 0;
                transform: translate(-50%, 8px)
            }

            20% {
                opacity: 1;
                transform: translate(-50%, 0)
            }

            75% {
                opacity: 1
            }

            to {
                opacity: 0;
                transform: translate(-50%, -18px)
            }
        }

        /* ----- 貓的新互動：尾巴、睡覺、被摸、逗貓棒 ----- */
        /* 尾巴以連著身體的那一端為軸，偶爾甩一下 */
        .cat-tail {
            transform-box: view-box;
            animation: tailSwish 6s ease-in-out infinite
        }

        .cat-one .cat-tail {
            transform-origin: 119px 194px
        }

        .cat-two .cat-tail {
            transform-origin: 150px 168px;
            animation-duration: 7.5s;
            animation-delay: -3s
        }

        @keyframes tailSwish {

            0%,
            70%,
            100% {
                transform: rotate(0)
            }

            78% {
                transform: rotate(-14deg)
            }

            86% {
                transform: rotate(5deg)
            }

            94% {
                transform: rotate(-7deg)
            }
        }

        /* 頭以脖子為軸，被摸時歪頭、睡覺時低頭 */
        .cat-head {
            transform-box: view-box;
            transition: transform .45s ease
        }

        .cat-one .cat-head {
            transform-origin: 95px 145px
        }

        .cat-two .cat-head {
            transform-origin: 80px 150px
        }

        .eyes-sleep,
        .eyes-happy {
            fill: none;
            stroke: #d5a962;
            stroke-width: 3.5;
            stroke-linecap: round;
            opacity: 0;
            transition: opacity .2s
        }

        .cat-blush {
            fill: #e58f8f;
            opacity: 0;
            transition: opacity .3s
        }

        .cat-eyes {
            transition: opacity .2s
        }

        .cat-zzz {
            display: none;
            fill: #f5e9d2;
            font: bold 16px Georgia, serif
        }

        .cat-zzz text {
            opacity: 0;
            animation: zzz 3s ease-out infinite
        }

        .cat-zzz text:nth-child(2) {
            animation-delay: 1s
        }

        .cat-zzz text:nth-child(3) {
            animation-delay: 2s
        }

        @keyframes zzz {
            0% {
                opacity: 0;
                transform: translate(-6px, 8px)
            }

            30% {
                opacity: .9
            }

            100% {
                opacity: 0;
                transform: translate(6px, -8px)
            }
        }

        /* 睡覺：閉眼、低頭、冒 Zzz，尾巴不動 */
        .cat.is-sleeping .cat-eyes {
            opacity: 0
        }

        .cat.is-sleeping .eyes-sleep {
            opacity: 1
        }

        .cat.is-sleeping .cat-zzz {
            display: inline
        }

        .cat.is-sleeping .cat-tail {
            animation: none
        }

        .cat-one.is-sleeping .cat-head {
            transform: rotate(4deg) translateY(5px)
        }

        .cat-two.is-sleeping .cat-head {
            transform: rotate(-4deg) translateY(5px)
        }

        /* 被摸：瞇眼、臉紅、歪頭蹭手，尾巴開心地慢慢擺 */
        .cat.is-happy .cat-eyes {
            opacity: 0
        }

        .cat.is-happy .eyes-happy,
        .cat.is-happy .cat-blush {
            opacity: 1
        }

        .cat-one.is-happy .cat-head {
            transform: rotate(-7deg)
        }

        .cat-two.is-happy .cat-head {
            transform: rotate(7deg)
        }

        .cat.is-happy .cat-tail {
            animation: tailHappy 1.1s ease-in-out infinite alternate
        }

        @keyframes tailHappy {
            from {
                transform: rotate(-9deg)
            }

            to {
                transform: rotate(6deg)
            }
        }

        /* 滑鼠移到貓頭上時換成可以點的貓掌，提示可以摸 */
        .cat.on-head {
            cursor: var(--paw-click)
        }

        .heart {
            position: fixed;
            z-index: 30;
            color: #e07a7a;
            font-size: 18px;
            pointer-events: none;
            text-shadow: 0 2px 4px #5a2f2f55;
            animation: heartUp 1.1s ease-out forwards
        }

        @keyframes heartUp {
            from {
                opacity: 1;
                transform: translate(-50%, -50%) scale(.6)
            }

            to {
                opacity: 0;
                transform: translate(calc(-50% + var(--dx)), -60px) scale(1.15)
            }
        }

        /* 逗貓棒：按住背景空白處拖曳時出現，取代滑鼠游標 */
        .wand {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            width: 120px;
            height: 110px;
            pointer-events: none;
            display: none
        }

        body.wand-mode .wand {
            display: block
        }

        body.wand-mode,
        body.wand-mode * {
            cursor: none !important;
            user-select: none
        }

        .wand-string {
            transform-box: view-box;
            transform-origin: 98px 8px;
            animation: wandSway 1.1s ease-in-out infinite alternate
        }

        @keyframes wandSway {
            from {
                transform: rotate(-7deg)
            }

            to {
                transform: rotate(7deg)
            }
        }

        /* 盯著逗貓棒：瞳孔放大、反應更快，靠近時屁股扭一扭準備撲 */
        .cat-pupils ellipse {
            transform-box: fill-box;
            transform-origin: center;
            transition: transform .3s ease
        }

        body.wand-mode .cat-pupils ellipse {
            transform: scale(1.2)
        }

        body.wand-mode .cat-look {
            transition-duration: .08s
        }

        .cat.is-ready {
            animation: wiggle .32s ease-in-out infinite
        }

        .cat.is-ready .cat-tail {
            animation: tailHappy .35s ease-in-out infinite alternate
        }

        @keyframes wiggle {

            0%,
            100% {
                transform: rotate(0)
            }

            50% {
                transform: rotate(1.6deg)
            }
        }

        .cat.pounce {
            animation: pounce .55s cubic-bezier(.3, .7, .4, 1)
        }

        @keyframes pounce {
            45% {
                transform: translate(var(--px), var(--py)) rotate(var(--pr))
            }
        }

        /* ===== 小筆記便條紙 ===== */
        .note-dialog {
            width: min(360px, calc(100vw - 32px));
            border: 0;
            padding: 26px 24px 20px;
            background: var(--paper);
            color: var(--ink);
            box-shadow: 0 20px 50px #2b1c1c66
        }

        .note-dialog::backdrop {
            background: #2b1c1c80
        }

        .note-kicker {
            margin: 0;
            color: var(--red);
            font-size: 10px;
            letter-spacing: .2em
        }

        .note-dialog h2 {
            margin: 6px 0 4px;
            font: 22px Georgia, serif;
            letter-spacing: .05em
        }

        .note-detail {
            margin: 0 0 14px;
            min-height: 1em;
            font-size: 12px;
            color: var(--muted);
            letter-spacing: .05em
        }

        .note-dialog textarea {
            width: 100%;
            resize: vertical;
            border: 1px solid #c8b2a3;
            background: #f9f2e9;
            color: #493b37;
            padding: 10px;
            font: 14px/1.6 inherit;
            font-family: inherit
        }

        .note-actions {
            display: flex;
            gap: 8px;
            margin-top: 14px
        }

        .note-actions button {
            border: 1px solid #b99d8b;
            background: transparent;
            color: #70564f;
            padding: 7px 14px;
            font: 12px sans-serif;
            cursor: var(--paw-click)
        }

        .note-actions .note-delete {
            margin-right: auto
        }

        .note-actions .note-save {
            background: var(--red);
            border-color: var(--red);
            color: #fff
        }

        @media(prefers-reduced-motion:reduce) {

            .cat-eyes,
            .cat.hop,
            .cat.pounce,
            .cat.is-ready,
            .cat .cat-tail,
            .cat-zzz text,
            .wand-string,
            .heart,
            .meow,
            body.record-playing .play-vinyl,
            body.record-playing .scene-ornament {
                animation: none
            }

            .cat-zzz text {
                opacity: .8
            }
        }

        /* ===== 列印成一張票根 ===== */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact
            }

            body {
                background: #fff;
                padding: 0;
                min-height: 0
            }

            body:before,
            .room,
            .cat,
            .ambient,
            .topline,
            .cat-tips,
            .controls,
            .play-record,
            .countdown,
            .note-dialog,
            .meow,
            .paper:after {
                display: none !important
            }

            .ticket {
                filter: none;
                max-width: none
            }

            .fall i,
            .scene-ornament {
                animation: none !important
            }

            td {
                height: 50px
            }
        }
    </style>
</head>

<body data-season="<?= $season ?>">
    <!-- 色塊插畫：夕陽從窗戶投進房間 -->
    <div class="room" aria-hidden="true">
        <svg viewBox="0 0 1440 900" preserveAspectRatio="xMinYMid slice" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="roomWall" x2="1" y2=".8">
                    <stop class="wall-a" stop-color="#aa6852" />
                    <stop class="wall-b" offset="1" stop-color="#593b39" />
                </linearGradient>
                <linearGradient id="roomSky" x2="0" y2="1">
                    <stop class="sky-a" stop-color="#ca6d68" />
                    <stop class="sky-b" offset="1" stop-color="#ffbf7b" />
                </linearGradient>
                <linearGradient id="roomBeam" x2="1" y2=".8">
                    <stop stop-color="#ffd496" />
                    <stop offset="1" stop-color="#e98a56" stop-opacity=".18" />
                </linearGradient>
            </defs>
            <rect width="1440" height="900" fill="url(#roomWall)" />
            <path class="room-floor" d="M0 604H1440V900H0Z" fill="#4c3333" />
            <path d="M0 594H1440V612H0Z" fill="#795047" />
            <g stroke="#775048" stroke-width="3" opacity=".7">
                <path d="M130 612 0 766M394 612 261 900M665 612 619 900M946 612 1032 900M1225 612 1439 863M0 708H1440M0 822H1440" />
            </g>
            <rect x="50" y="88" width="326" height="351" rx="5" fill="#593638" />
            <rect x="65" y="103" width="295" height="317" fill="url(#roomSky)" />
            <circle class="room-sun" cx="161" cy="302" r="58" fill="#ffdf9c" />
            <path class="win-hill-a" d="M65 372Q145 314 224 365T360 353V420H65Z" fill="#aa5b61" />
            <path class="win-hill-b" d="M65 387Q159 363 242 392T360 382V420H65Z" fill="#824752" />
            <path d="M211 103V421M65 255H360M46 432H380" stroke="#593638" stroke-width="13" />
            <path d="M29 71H386" stroke="#543338" stroke-width="8" stroke-linecap="round" />
            <path d="M44 77H111Q98 226 124 445H31Q52 242 44 77Z" fill="#733e44" />
            <path d="M58 80Q69 249 54 436M86 79Q83 232 100 435" fill="none" stroke="#9b5050" stroke-width="8" opacity=".65" />
            <g class="room-light" fill="url(#roomBeam)">
                <path d="M109 112H207L1038 763 625 865 111 247Z" />
                <path d="M222 112H352L1430 754 1117 752 222 247Z" />
                <path d="M110 264H210L609 867 323 900 117 408Z" />
                <path d="M224 264H352L1109 766 1051 791 225 408Z" />
            </g>
            <path d="M1229 180h127v166h-127z" fill="#54363b" />
            <path d="M1238 189h109v148h-109z" fill="#c59273" />
            <circle cx="1291" cy="242" r="22" fill="#e5b184" />
            <path d="M1238 322l45-60 26 30 38-38v83h-109z" fill="#8a584e" />
            <path d="M1268 629q90-44 172-21v190q-107 14-239-39z" fill="#804844" />
            <path d="M1268 641q83-39 172-23v20q-76-17-172 23z" fill="#ad6453" />
        </svg>
    </div>

    <!-- 黑臉賓士貓：白斑只在照片左側的鼻口，鼻樑與下巴保留黑色 -->
    <!-- 貓可以點：眼睛會跟著滑鼠、偶爾眨眼，點一下會喵 -->
    <svg class="cat cat-one" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 190 214" role="button" tabindex="0" aria-label="黑臉賓士貓，按一下會喵喵叫">
        <ellipse cx="97" cy="201" rx="84" ry="10" fill="#3d2929" opacity=".32" />
        <path d="M54 191C37 176 45 141 63 123H126C144 142 158 182 141 193Z" fill="#292528" />
        <path d="M61 123H126C136 141 132 172 119 187H62C46 173 48 143 61 123Z" fill="#fff0dc" />
        <ellipse cx="74" cy="190" rx="17" ry="11" fill="#fff0dc" />
        <ellipse cx="107" cy="191" rx="17" ry="11" fill="#fff0dc" />
        <path d="M78 153q10-9 27-4 8 4 6 11-8 9-23 6-11-2-10-13Z" fill="#30282b" />
        <!-- 尾巴、頭各自包成一組，才能分開擺動 -->
        <g class="cat-tail">
            <path d="M164 165c3 22-13 33-45 29" fill="none" stroke="#292528" stroke-width="13" stroke-linecap="round" />
        </g>
        <g class="cat-head">
        <path d="M36 70C29 54 23 22 34 15C46 11 63 37 73 49C86 44 105 44 117 49C130 32 147 10 157 14C168 17 162 51 155 70C169 87 170 109 156 126C145 141 124 145 107 150C96 156 83 151 76 147C55 146 37 135 28 122C18 106 21 85 36 70Z" fill="#2b2629" />
        <path d="M37 25C44 25 57 43 62 54L44 63C39 53 34 33 37 25ZM154 25C157 33 152 53 147 62L129 54C137 42 147 25 154 25Z" fill="#9f746d" />
        <path d="M42 34q10 11 14 21M150 33q-6 12-12 22" fill="none" stroke="#bf9082" stroke-width="2.5" stroke-linecap="round" />
        <path d="M43 127C54 126 57 122 64 122C72 121 70 113 80 111C85 111 86 116 84 120C76 125 80 132 87 134L84 139C74 142 71 144 76 147C62 146 52 138 43 127Z" fill="#fff0dc" />
        <!-- cat-eyes 負責眨眼，cat-look 是會跟著滑鼠移動的瞳孔 -->
        <g class="cat-eyes">
            <g fill="#d5a962">
                <ellipse cx="63" cy="96" rx="16" ry="17" />
                <ellipse cx="124" cy="94" rx="16" ry="17" />
            </g>
            <g class="cat-look">
                <g class="cat-pupils" fill="#15151b">
                    <ellipse cx="64" cy="96" rx="11.5" ry="13" />
                    <ellipse cx="123" cy="94" rx="11.5" ry="13" />
                </g>
                <g fill="#fff7df">
                    <circle cx="59" cy="90" r="4" />
                    <circle cx="119" cy="88" r="4" />
                    <circle cx="69" cy="102" r="1.7" />
                    <circle cx="128" cy="100" r="1.7" />
                </g>
            </g>
        </g>
        <path d="M84 114Q94 110 104 114Q103 119 95 124Q87 120 84 114Z" fill="#18161a" />
        <path d="M95 123v5m0 0q-4 5-10 3m10-3q6 4 12 1" fill="none" stroke="#665050" stroke-width="1.5" stroke-linecap="round" />
        <path d="M66 120Q43 113 17 116M66 124Q41 123 16 132M118 119Q146 108 175 112M120 124Q149 121 176 128" fill="none" stroke="#e1c6b1" stroke-width="1.2" stroke-linecap="round" />
        <!-- 睡覺時的閉眼、被摸時的瞇眼和腮紅，平常隱藏 -->
        <path class="eyes-sleep" d="M50 96q13 9 26 0M111 94q13 9 26 0" />
        <path class="eyes-happy" d="M50 100q13-12 26 0M111 98q13-12 26 0" />
        <g class="cat-blush">
            <ellipse cx="50" cy="115" rx="8" ry="4.5" />
            <ellipse cx="137" cy="112" rx="8" ry="4.5" />
        </g>
        </g>
        <g class="cat-zzz">
            <text x="150" y="40">z</text>
            <text x="163" y="22">z</text>
            <text x="176" y="2">Z</text>
        </g>
    </svg>

    <!-- 白鼻樑賓士貓：圓臉、招牌白鼻樑和趴著露出來的白爪 -->
    <svg class="cat cat-two" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 190 214" role="button" tabindex="0" aria-label="白鼻樑賓士貓，按一下會喵喵叫">
        <ellipse cx="97" cy="199" rx="86" ry="10" fill="#3d2929" opacity=".33" />
        <g class="cat-tail">
            <path d="M150 168q24-9 27-30" fill="none" stroke="#302729" stroke-width="12" stroke-linecap="round" />
        </g>
        <path d="M34 170q11-34 58-38 59-6 77 32 13 29-14 30H41q-20-3-7-24Z" fill="#fff0dc" />
        <path d="M118 134q37 2 50 28-14 12-34 1-15-8-16-29Z" fill="#31292c" />
        <path d="M63 148q10-3 22 1 3 14-11 20-12-3-11-21Z" fill="#34282b" />
        <ellipse cx="55" cy="190" rx="18" ry="9" fill="#fff6e8" />
        <ellipse cx="93" cy="191" rx="17" ry="9" fill="#fff6e8" />
        <g class="cat-head">
        <path d="M30 83C22 67 17 37 25 30C36 24 52 44 64 56C75 53 88 53 99 56C109 43 123 24 134 27C144 30 139 65 133 81C150 99 147 121 133 136C119 151 98 156 78 155C56 155 37 148 27 133C15 117 17 100 30 83Z" fill="#30282b" />
        <path d="M29 39Q39 38 53 60L37 70Q30 56 29 39ZM130 38Q133 52 126 69L110 60Q122 40 130 38Z" fill="#bb827c" />
        <path d="M79 71q7 4 9 20 3 25 46 25-4 38-55 39-42 0-51-32 38 0 45-28z" fill="#fff0dc" />
        <g class="cat-eyes">
            <g fill="#d5a962">
                <ellipse cx="53" cy="100" rx="15" ry="16" />
                <ellipse cx="106" cy="98" rx="15" ry="16" />
            </g>
            <g class="cat-look">
                <g class="cat-pupils" fill="#17151a">
                    <ellipse cx="54" cy="100" rx="10.7" ry="12" />
                    <ellipse cx="105" cy="98" rx="10.7" ry="12" />
                </g>
                <g fill="#fff7df">
                    <circle cx="49" cy="94" r="3.7" />
                    <circle cx="101" cy="92" r="3.7" />
                    <circle cx="58" cy="105" r="1.6" />
                    <circle cx="109" cy="103" r="1.6" />
                </g>
            </g>
        </g>
        <path d="M71 121q9-5 18-1-2 6-9 9-6-2-9-8Z" fill="#654445" />
        <path d="M80 128v4m0 0q-4 5-11 2m11-2q5 5 11 1" fill="none" stroke="#99706a" stroke-width="1.5" stroke-linecap="round" />
        <path d="M55 125Q31 116 12 121m43 9q-20-1-37 6m88-12q20-10 41-8m-40 13q21-3 41 2" fill="none" stroke="#c89983" stroke-width="1.2" stroke-linecap="round" />
        <path class="eyes-sleep" d="M41 100q12 8 24 0M94 98q12 8 24 0" />
        <path class="eyes-happy" d="M41 104q12-11 24 0M94 102q12-11 24 0" />
        <g class="cat-blush">
            <ellipse cx="40" cy="118" rx="7.5" ry="4.2" />
            <ellipse cx="121" cy="115" rx="7.5" ry="4.2" />
        </g>
        </g>
        <g class="cat-zzz">
            <text x="136" y="44">z</text>
            <text x="149" y="26">z</text>
            <text x="162" y="6">Z</text>
        </g>
    </svg>
    <div class="ambient" aria-hidden="true">
        <?php
        // 背景粒子：位置、速度、起始時間用固定公式分散開來
        for ($k = 0; $k < 14; $k++) {
            printf('<i style="--x:%d%%;--t:%ds;--d:%ds"></i>', ($k * 29 + 5) % 100, 11 + $k % 5 * 2, -($k * 13) % 17);
        }
        ?>
    </div>
    <div class="topline">
        <span>THE LITTLE PAPER CALENDAR</span>
        <span class="topline-tools">
            <span class="topline-vol">VOL. 01 / EVERY DAY</span>
            <button type="button" id="sound-toggle" aria-pressed="false">♪ 音效 OFF</button>
            <button type="button" id="print-ticket">列印票根</button>
        </span>
    </div>
    <main class="ticket">
        <div class="paper" data-year="<?= $year ?>" data-month="<?= $month ?>">
            <header class="head">
                <div class="brand">THE MOONLIGHT RECORD SHOP<small>月光唱片屋・一張寄給日子的票</small></div>
                <div class="serial">ADMIT ONE<br>NO. <?= sprintf('%04d.%02d', $year, $month) ?></div>
            </header>
            <div class="layout">
                <section aria-label="月光唱片屋立體紙雕插畫">
                    <div class="art-title">
                        <p>A LITTLE SCENE FOR YOU</p><span><?= sprintf('%02d', $month) ?> / 12</span>
                    </div>
                    <div class="window" aria-hidden="true">
                        <div class="scene" style="background:linear-gradient(<?= $sceneInfo['sky'] ?>,<?= $sceneInfo['glow'] ?>)">
                            <div class="<?= $sceneInfo['light'] ?>"></div>
                            <?php if ($sceneInfo['light'] === 'moon'): ?><div class="stars">✦ · ✦</div><?php endif; ?>
                            <div class="hill back"></div>
                            <div class="hill" style="background:<?= $sceneInfo['hill'] ?>"></div>
                            <div class="scene-ornament"><?= $sceneInfo['icon'] ?></div>
                            <div class="mini-stage"><span><?= $sceneInfo['en'] ?></span></div>
                            <div class="tree one"></div>
                            <div class="tree two"></div>
                            <?php if ($sceneInfo['fall'] !== ''): ?>
                                <div class="fall fall-<?= $sceneInfo['fall'] ?>">
                                    <?php
                                    // 用固定的公式分散每顆粒子的位置、速度與起始時間，每次重新整理都一樣
                                    $speed = $sceneInfo['fall'] === 'rain' ? 1.2 : 7;
                                    for ($k = 0; $k < 14; $k++) {
                                        $x = ($k * 37 + 11) % 100;
                                        $y = ($k * 53 + 20) % 260;
                                        $t = $speed + ($k % 4) * $speed * .18;
                                        $d = -($k * $speed / 14);
                                        printf('<i style="--x:%d%%;--y:%dpx;--t:%.2fs;--d:%.2fs"></i>', $x, $y, $t, $d);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="art-caption"><span><?= $sceneInfo['name'] ?> · <?= $sceneInfo['en'] ?></span><span>✦ <?= sprintf('%02d', $month) ?></span></div>
                    <p class="art-note">A little music, a little magic,<br>and a new page for every day.</p>
                    <button type="button" class="play-record" aria-pressed="false"><span class="play-vinyl" aria-hidden="true"></span><span class="play-label">放一張唱片</span></button>
                    <?php if ($holiday): ?>
                        <p class="countdown">
                            <?php if ($holiday['days'] === 0): ?>
                                今天是 <b><?= $holiday['name'] ?></b>，好好休息吧！
                            <?php else: ?>
                                下一個國定假日 <b><?= $holiday['name'] ?></b>（<?= $holiday['date']->format('n/j') ?>）還有 <b><?= $holiday['days'] ?></b> 天
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </section>
                <div class="layout-divider" aria-hidden="true"></div>
                <section aria-label="月曆">
                    <div class="calendar-head">
                        <div class="month-number"><?= sprintf('%02d', $month) ?></div>
                        <div class="date-title"><strong><?= $months[$month - 1] ?></strong><span><?= $year ?> 年 · <?= $month ?> 月 · <?= $ganzhi ?></span><span class="date-tip">✎ 點一下日期，可以寫下當天的小筆記</span></div>
                    </div>
                    <nav class="controls" aria-label="切換月份">
                        <?php if ($year > 1 || $month > 1): ?><a id="prev-month" href="?year=<?= $prev->format('Y') ?>&amp;month=<?= $prev->format('n') ?>" aria-label="上一個月">← 上月</a><?php else: ?><span class="is-off">← 上月</span><?php endif; ?>
                        <form method="get"><label for="year" class="sr-only">年份</label><input id="year" type="number" name="year" min="1" max="9999" value="<?= $year ?>" required><label for="month" class="sr-only">月份</label><select id="month" name="month"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= $m ?> 月</option><?php endfor; ?></select><button type="submit">前往</button><?php if ($isThisMonth): ?><span class="is-off" aria-current="date">今天</span><?php else: ?><a id="today-month" href="?year=<?= $todayYear ?>&amp;month=<?= $todayMonth ?>" aria-label="回到今天">今天</a><?php endif; ?></form>
                        <?php if ($year < 9999 || $month < 12): ?><a id="next-month" href="?year=<?= $next->format('Y') ?>&amp;month=<?= $next->format('n') ?>" aria-label="下一個月">下月 →</a><?php else: ?><span class="is-off">下月 →</span><?php endif; ?>
                    </nav>
                    <table aria-label="<?= $year ?> 年 <?= $month ?> 月月曆">
                        <thead>
                            <tr><?php foreach ($weekdays as $weekday): ?><th scope="col"><?= $weekday ?></th><?php endforeach; ?></tr>
                        </thead>
                        <tbody>
                            <?php
                            // 外層迴圈產生週，內層迴圈產生每週七天
                            for ($i = 0; $i < $theCalendarWeeks; $i++) {
                                echo '<tr>';
                                for ($j = 0; $j < 7; $j++) {
                                    $day = $i * 7 + $j - $firstDayWeek + 1;
                                    if ($day < 1 || $day > $theMonthDays) {
                                        // 空格補上前後月份的日期，只是陪襯，螢幕閱讀器略過不唸
                                        $otherDay = $day < 1 ? $prevMonthDays + $day : $day - $theMonthDays;
                                        $weekend = $j === 0 || $j === 6 ? ' weekend' : '';
                                        echo "<td class='other-month$weekend'><span class='day' aria-hidden='true'>$otherDay</span></td>";
                                        continue;
                                    }
                                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $isToday = $date === $today;
                                    $info = dayInfo($year, $month, $day);
                                    $class = trim(($j === 0 || $j === 6 ? 'weekend ' : '') . ($info['off'] ? 'holiday ' : '') . ($isToday ? 'today' : ''));
                                    // aria-current 讓螢幕閱讀器也知道哪一天是今天
                                    $current = $isToday ? " aria-current='date'" : '';
                                    // 便條紙標題下方要顯示的完整資訊，例如「農曆正月初二 · 雨水 · 春節」
                                    $eventNames = implode('・', array_column($info['events'], 0));
                                    $detail = implode(' · ', array_filter([$info['lunar'] ? '農曆' . lunarText($info['lunar'], true) : '', $info['term'], $eventNames]));
                                    // 整格是一個按鈕，點下去可以寫當天的小筆記
                                    echo "<td class='$class'$current><button type='button' class='cell' data-date='$date' data-detail='" . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . "'><span class='day'>$day</span>";
                                    // 有節氣就顯示節氣，否則顯示農曆日期；農曆範圍外（1900 年以前、2100 年以後）不顯示
                                    if ($info['term']) {
                                        echo "<span class='lunar term'>{$info['term']}</span>";
                                    } elseif ($info['lunar']) {
                                        $monthStart = $info['lunar']['day'] === 1 ? ' month-start' : '';
                                        echo "<span class='lunar$monthStart'>" . lunarText($info['lunar']) . '</span>';
                                    }
                                    // 同一天的節日併成一行，太長時以「…」結尾，每格才會一樣高
                                    if ($eventNames !== '') {
                                        $eventNames = htmlspecialchars($eventNames, ENT_QUOTES, 'UTF-8');
                                        echo '<span class="special' . ($info['off'] ? ' off' : '') . "\" title=\"$eventNames\">$eventNames</span>";
                                    }
                                    echo '</button></td>';
                                }
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
            <footer class="footer"><span>KEEP THIS TICKET FOR A GOOD DAY</span><span>MADE WITH <b>♥</b> &amp; MUSIC</span></footer>
        </div>
    </main>
    <!-- 貓咪互動的小提示，讓第一次來的人知道可以怎麼玩 -->
    <p class="cat-tips">
        <span>🐾 和貓咪玩</span>
        <span><b>點一下</b>貓咪會喵喵叫</span>
        <span>滑鼠在貓咪<b>頭上來回移動</b>可以摸摸牠</span>
        <span>在背景空白處<b>按住拖曳</b>拿逗貓棒逗牠</span>
        <span>放著不動 <b>15 秒</b>牠們會睡著</span>
    </p>
    <!-- 點日期跳出的小筆記便條紙；筆記只存在這台電腦的瀏覽器裡 -->
    <dialog id="note-dialog" class="note-dialog" aria-labelledby="note-title">
        <form method="dialog">
            <p class="note-kicker">A NOTE FOR THE DAY</p>
            <h2 id="note-title"></h2>
            <p id="note-detail" class="note-detail"></p>
            <label for="note-text" class="sr-only">筆記內容</label>
            <textarea id="note-text" maxlength="200" rows="5" placeholder="寫下這天想記住的事…"></textarea>
            <div class="note-actions">
                <button value="delete" class="note-delete">刪除</button>
                <button value="cancel">取消</button>
                <button value="save" class="note-save">儲存</button>
            </div>
        </form>
    </dialog>
    <script>
        // 先載入下個月份，準備完成後再翻頁，避免翻到一半畫面就跳走。
        let turning = false;
        // 翻頁途中按了瀏覽器上一頁／下一頁，等這次翻完再補翻一次
        let pendingPop = false;
        // 把年月換成一個數字，方便比較誰在前、誰在後
        function monthIndex(paper) {
            return Number(paper.dataset.year) * 12 + Number(paper.dataset.month);
        }
        // 已經抓過的月份先存起來，翻頁時就不用等網路；最多留 24 個月
        const pageCache = new Map();
        function loadPage(url) {
            if (pageCache.has(url)) return pageCache.get(url);
            const request = fetch(url, {
                headers: {
                    'X-Requested-With': 'calendar'
                }
            }).then(response => {
                if (!response.ok) throw new Error('月份載入失敗');
                return response.text();
            });
            // 失敗的不留在快取裡，下次還能重抓
            request.catch(() => pageCache.delete(url));
            pageCache.set(url, request);
            if (pageCache.size > 24) pageCache.delete(pageCache.keys().next().value);
            return request;
        }
        // 趁瀏覽器有空時，先把上個月和下個月抓好
        function prefetchNeighbors() {
            const run = () => document.querySelectorAll('#prev-month, #next-month').forEach(link => loadPage(link.href).catch(() => {}));
            if ('requestIdleCallback' in window) requestIdleCallback(run);
            else setTimeout(run, 300);
        }
        async function turnPage(url, addHistory = true) {
            if (!url || turning) return;
            turning = true;
            try {
                const html = await loadPage(url);
                const nextDocument = new DOMParser().parseFromString(html, 'text/html');
                const nextPaper = nextDocument.querySelector('.paper');
                const ticket = document.querySelector('.ticket');
                const currentPaper = ticket.querySelector('.paper');
                if (!nextPaper || !currentPaper) throw new Error('找不到月曆');

                // 依照新舊月份的先後自動決定翻頁方向；同一個月就不必翻
                const diff = monthIndex(nextPaper) - monthIndex(currentPaper);
                const direction = diff > 0 ? 'next' : 'prev';
                if (diff === 0) {
                    if (addHistory && url !== location.href) history.replaceState({ calendar: true }, '', url);
                    return;
                }
                // 新頁面先標好筆記和唱片按鈕狀態，翻頁背面的複本才會一致
                preparePaper(nextPaper);
                // 換上新頁面，背景粒子也跟著換季節
                function swapPaper() {
                    currentPaper.replaceWith(nextPaper);
                    document.body.dataset.season = nextDocument.body.dataset.season || '';
                }
                playFlipSound();

                if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    swapPaper();
                } else {
                    const width = ticket.getBoundingClientRect().width;
                    const page = document.createElement('div');
                    page.className = 'flipping-page ' + direction;
                    page.setAttribute('aria-hidden', 'true');
                    page.inert = true;
                    // 複製目前頁面與目標頁面，分別貼在翻頁的正面和背面。
                    function makeFace(paper, side, offset) {
                        const face = document.createElement('div');
                        face.className = 'page-face ' + side;
                        const copy = paper.cloneNode(true);
                        copy.style.width = width + 'px';
                        copy.style.left = (-offset) + 'px';
                        copy.querySelectorAll('[id]').forEach(element => element.removeAttribute('id'));
                        face.appendChild(copy);
                        return face;
                    }
                    const nextDirection = direction === 'next';
                    page.appendChild(makeFace(currentPaper, 'front', nextDirection ? width / 2 : 0));
                    page.appendChild(makeFace(nextPaper, 'back', nextDirection ? 0 : width / 2));
                    ticket.appendChild(page);
                    // 書頁翻過中線時才更換底下內容，動畫繼續翻到另一側。
                    await new Promise(resolve => setTimeout(resolve, 450));
                    swapPaper();
                    await new Promise(resolve => setTimeout(resolve, 450));
                    page.remove();
                }
                if (addHistory) history.pushState({
                    calendar: true
                }, '', url);
                document.title = nextDocument.title;
            } catch (error) {
                // 若瀏覽器無法預先載入，仍可用一般方式切換月份。
                window.location.href = url;
            } finally {
                turning = false;
                if (pendingPop) {
                    pendingPop = false;
                    turnPage(location.href, false);
                } else {
                    prefetchNeighbors();
                }
            }
        }
        prefetchNeighbors();
        // 事件綁在 document，因此更換月曆內容後按鈕仍然有效。
        document.addEventListener('click', event => {
            const link = event.target.closest('#prev-month, #next-month, #today-month');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            turnPage(link.href);
        });
        // 「前往」表單也改用翻頁動畫，不再整頁重新載入
        document.addEventListener('submit', event => {
            const form = event.target.closest('.controls form');
            if (!form) return;
            event.preventDefault();
            const url = new URL(location.href);
            url.search = new URLSearchParams(new FormData(form)).toString();
            turnPage(url.href);
        });
        document.addEventListener('keydown', event => {
            if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.isComposing) return;
            if (event.target.closest('input, select, textarea, button:not(.cell), [contenteditable="true"]')) return;
            const id = event.key === 'ArrowLeft' ? 'prev-month' : event.key === 'ArrowRight' ? 'next-month' : '';
            const link = id && document.getElementById(id);
            if (link) {
                event.preventDefault();
                turnPage(link.href);
            }
        });
        // 滑鼠在整張月曆紙上移動時，記錄游標位置讓反光跟著移動（觸控裝置不啟用）
        document.addEventListener('pointermove', event => {
            if (event.pointerType === 'touch' || !(event.target instanceof Element)) return;
            const paper = event.target.closest('.ticket > .paper');
            document.querySelectorAll('.ticket > .paper').forEach(item => item.classList.toggle('glare-on', item === paper));
            if (!paper) return;
            const rect = paper.getBoundingClientRect();
            paper.style.setProperty('--gx', (event.clientX - rect.left) + 'px');
            paper.style.setProperty('--gy', (event.clientY - rect.top) + 'px');
        });
        // 瀏覽器上一頁／下一頁也用翻頁動畫，且不再額外新增歷史紀錄
        window.addEventListener('popstate', () => {
            if (turning) {
                pendingPop = true;
                return;
            }
            turnPage(location.href, false);
        });

        // ===== 小工具：瀏覽器可能封鎖 localStorage（例如無痕模式），讀寫都包在 try 裡 =====
        function storageGet(key) {
            try {
                return localStorage.getItem(key);
            } catch (error) {
                return null;
            }
        }
        function storageSet(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (error) {}
        }
        const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');

        // ===== 每日小筆記：存在這台電腦的瀏覽器裡 =====
        const NOTE_KEY = 'moonlight-calendar-notes';
        const noteDialog = document.getElementById('note-dialog');
        const noteText = document.getElementById('note-text');
        let noteDate = '';
        function readNotes() {
            try {
                return JSON.parse(storageGet(NOTE_KEY)) || {};
            } catch (error) {
                return {};
            }
        }
        // 有筆記的日期加上小唱片記號，滑鼠停留時顯示內容
        function markNotes(root) {
            const notes = readNotes();
            root.querySelectorAll('.cell[data-date]').forEach(cell => {
                const note = notes[cell.dataset.date];
                cell.classList.toggle('has-note', Boolean(note));
                if (note) cell.title = note;
                else cell.removeAttribute('title');
                let hint = cell.querySelector('.note-hint');
                if (note && !hint) {
                    hint = document.createElement('span');
                    hint.className = 'sr-only note-hint';
                    hint.textContent = '，有筆記';
                    cell.appendChild(hint);
                }
                if (!note && hint) hint.remove();
            });
        }
        document.addEventListener('click', event => {
            const cell = event.target.closest('.ticket > .paper .cell');
            if (!cell) return;
            noteDate = cell.dataset.date;
            const [y, m, d] = noteDate.split('-').map(Number);
            document.getElementById('note-title').textContent = `${y} 年 ${m} 月 ${d} 日`;
            document.getElementById('note-detail').textContent = cell.dataset.detail || '';
            noteText.value = readNotes()[noteDate] || '';
            noteDialog.querySelector('.note-delete').hidden = !noteText.value;
            noteDialog.returnValue = '';
            noteDialog.showModal();
        });
        // 按 Esc、取消或點便條紙外面都不會存檔
        noteDialog.addEventListener('click', event => {
            if (event.target !== noteDialog) return;
            const rect = noteDialog.getBoundingClientRect();
            const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
            if (outside) noteDialog.close('cancel');
        });
        noteDialog.addEventListener('close', () => {
            const action = noteDialog.returnValue;
            if (action !== 'save' && action !== 'delete') return;
            const notes = readNotes();
            const text = noteText.value.trim();
            if (action === 'save' && text) notes[noteDate] = text;
            else delete notes[noteDate];
            storageSet(NOTE_KEY, JSON.stringify(notes));
            markNotes(document.querySelector('.ticket > .paper'));
        });

        // ===== 音效：全部用 Web Audio 即時合成，不需要任何音檔；預設關閉 =====
        const SOUND_KEY = 'moonlight-calendar-sound';
        const soundButton = document.getElementById('sound-toggle');
        let soundOn = storageGet(SOUND_KEY) === 'on';
        let audio = null;
        function getAudio() {
            if (!audio) {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return null;
                audio = new AudioContextClass();
            }
            if (audio.state === 'suspended') audio.resume();
            return audio;
        }
        // 一段隨機雜訊：一般的用來做翻紙聲，crackle 版本是唱片的沙沙與爆裂聲
        function noiseBuffer(ctx, seconds, crackle = false) {
            const buffer = ctx.createBuffer(1, Math.floor(ctx.sampleRate * seconds), ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < data.length; i++) {
                const value = Math.random() * 2 - 1;
                data[i] = crackle ? (Math.random() < 0.0004 ? value * 0.9 : value * 0.015) : value;
            }
            return buffer;
        }
        // 翻頁聲：兩下由高到低的沙沙聲，對應書頁翻起與落下
        function playFlipSound() {
            if (!soundOn) return;
            const ctx = getAudio();
            if (!ctx) return;
            const now = ctx.currentTime;
            [0, 0.42].forEach((start, index) => {
                const source = ctx.createBufferSource();
                source.buffer = noiseBuffer(ctx, 0.4);
                const filter = ctx.createBiquadFilter();
                filter.type = 'bandpass';
                filter.Q.value = 0.8;
                filter.frequency.setValueAtTime(3200, now + start);
                filter.frequency.exponentialRampToValueAtTime(700, now + start + 0.35);
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0.0001, now + start);
                gain.gain.exponentialRampToValueAtTime(index ? 0.12 : 0.22, now + start + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + start + 0.38);
                source.connect(filter).connect(gain).connect(ctx.destination);
                source.start(now + start);
                source.stop(now + start + 0.4);
            });
        }
        // 貓叫聲：音高先升後降，再用濾波器模仿嘴巴開合的「喵」
        function playMeowSound(pitch) {
            if (!soundOn) return;
            const ctx = getAudio();
            if (!ctx) return;
            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(420 * pitch, now);
            osc.frequency.exponentialRampToValueAtTime(820 * pitch, now + 0.14);
            osc.frequency.exponentialRampToValueAtTime(500 * pitch, now + 0.55);
            const mouth = ctx.createBiquadFilter();
            mouth.type = 'bandpass';
            mouth.Q.value = 3;
            mouth.frequency.setValueAtTime(900, now);
            mouth.frequency.linearRampToValueAtTime(1600, now + 0.15);
            mouth.frequency.linearRampToValueAtTime(800, now + 0.55);
            const gain = ctx.createGain();
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.35, now + 0.06);
            gain.gain.setValueAtTime(0.35, now + 0.3);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.6);
            osc.connect(mouth).connect(gain).connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.62);
        }
        function renderSoundButton() {
            soundButton.textContent = soundOn ? '♪ 音效 ON' : '♪ 音效 OFF';
            soundButton.setAttribute('aria-pressed', String(soundOn));
        }
        renderSoundButton();
        soundButton.addEventListener('click', () => {
            soundOn = !soundOn;
            storageSet(SOUND_KEY, soundOn ? 'on' : 'off');
            renderSoundButton();
            playFlipSound(); // 打開時馬上聽到一下，確認有聲音
        });

        // ===== 唱片機：即時合成一段原創的 lo-fi 小曲（約 27 秒）=====
        let record = null; // 播放中時存放音訊節點，停止後是 null
        function midiToHz(note) {
            return 440 * Math.pow(2, (note - 69) / 12);
        }
        function startRecord() {
            const ctx = getAudio();
            if (!ctx) return;
            const now = ctx.currentTime + 0.1;
            const beat = 60 / 72; // 每分鐘 72 拍
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.0001, now);
            master.gain.exponentialRampToValueAtTime(0.5, now + 1.2);
            // 把高音削掉一些，聽起來比較舊、比較暖
            const warm = ctx.createBiquadFilter();
            warm.type = 'lowpass';
            warm.frequency.value = 1500;
            master.connect(warm).connect(ctx.destination);
            const crackle = ctx.createBufferSource();
            crackle.buffer = noiseBuffer(ctx, 3, true);
            crackle.loop = true;
            crackle.connect(master);
            crackle.start(now);
            // 讓音高微微晃動，像老唱片轉速不太穩
            const wow = ctx.createOscillator();
            wow.frequency.value = 0.6;
            const wowDepth = ctx.createGain();
            wowDepth.gain.value = 7;
            wow.connect(wowDepth);
            wow.start(now);
            const drumNoise = noiseBuffer(ctx, 0.3);

            function note(midi, start, length, volume, type = 'triangle') {
                const osc = ctx.createOscillator();
                osc.type = type;
                osc.frequency.value = midiToHz(midi);
                wowDepth.connect(osc.detune);
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(volume, start + 0.06);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
                osc.connect(gain).connect(master);
                osc.start(start);
                osc.stop(start + length + 0.05);
            }
            function kick(start) {
                const osc = ctx.createOscillator();
                osc.frequency.setValueAtTime(120, start);
                osc.frequency.exponentialRampToValueAtTime(45, start + 0.15);
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0.35, start);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.25);
                osc.connect(gain).connect(master);
                osc.start(start);
                osc.stop(start + 0.3);
            }
            function hit(start, type, frequency, volume, length) {
                const source = ctx.createBufferSource();
                source.buffer = drumNoise;
                const filter = ctx.createBiquadFilter();
                filter.type = type;
                filter.frequency.value = frequency;
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(volume, start);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
                source.connect(filter).connect(gain).connect(master);
                source.start(start);
                source.stop(start + length + 0.02);
            }

            // 和弦：Fmaj7 → Em7 → Dm7 → Cmaj7，旋律從第二小節開始
            const chords = [[53, 57, 60, 64], [52, 55, 59, 62], [50, 53, 57, 60], [48, 52, 55, 59]];
            const melody = [
                [[0.5, 72], [1.5, 69], [2.5, 67], [3, 69]],
                [[0.5, 71], [1.5, 67], [2.5, 64]],
                [[0.5, 69], [1, 72], [2, 74], [3, 72]],
                [[0.5, 67], [1.5, 64], [2.5, 67], [3.5, 60]]
            ];
            const bars = 8;
            for (let bar = 0; bar < bars; bar++) {
                const start = now + bar * 4 * beat;
                const chord = chords[bar % 4];
                chord.forEach(midi => note(midi, start, 4 * beat, 0.05));
                note(chord[0] - 12, start, 1.8 * beat, 0.16, 'sine');
                note(chord[0] - 12, start + 2 * beat, 1.8 * beat, 0.12, 'sine');
                if (bar > 0) melody[bar % 4].forEach(([at, midi]) => note(midi, start + at * beat, 0.9 * beat, 0.07, 'sine'));
                for (let b = 0; b < 4; b++) {
                    if (b % 2) hit(start + b * beat, 'bandpass', 1800, 0.12, 0.18);
                    else kick(start + b * beat);
                    hit(start + (b + 0.5) * beat, 'highpass', 6000, 0.06, 0.05);
                }
            }
            const total = bars * 4 * beat;
            master.gain.setValueAtTime(0.5, now + total - 1.5);
            master.gain.exponentialRampToValueAtTime(0.0001, now + total + 0.5);
            record = {
                ctx,
                master,
                crackle,
                wow,
                timer: setTimeout(stopRecord, (total + 0.8) * 1000)
            };
            syncRecordButton(document);
        }
        function stopRecord() {
            if (!record) return;
            const { ctx, master, crackle, wow, timer } = record;
            record = null;
            clearTimeout(timer);
            const now = ctx.currentTime;
            master.gain.cancelScheduledValues(now);
            master.gain.setValueAtTime(master.gain.value, now);
            master.gain.linearRampToValueAtTime(0, now + 0.3);
            setTimeout(() => {
                master.disconnect();
                crackle.stop();
                wow.stop();
            }, 400);
            syncRecordButton(document);
        }
        function syncRecordButton(root) {
            root.querySelectorAll('.play-record').forEach(button => {
                button.setAttribute('aria-pressed', String(Boolean(record)));
                button.querySelector('.play-label').textContent = record ? '停止播放' : '放一張唱片';
            });
            document.body.classList.toggle('record-playing', Boolean(record));
        }
        document.addEventListener('click', event => {
            if (!event.target.closest('.ticket > .paper .play-record')) return;
            if (record) stopRecord();
            else startRecord();
        });

        // 新月份的頁面放上去之前，先補上筆記記號與唱片按鈕狀態
        function preparePaper(paper) {
            markNotes(paper);
            syncRecordButton(paper);
        }
        preparePaper(document.querySelector('.ticket > .paper'));

        // ===== 會互動的貓 =====
        // 點一下：跳起來喵一聲，另一隻會轉頭看，有時還會回一聲
        // 摸摸頭：滑鼠在貓頭上來回移動，貓會瞇眼、臉紅、歪頭、呼嚕、冒愛心
        // 睡覺：一陣子沒動滑鼠就會睡著（夜晚睡得比較快），滑鼠一動就醒
        // 逗貓棒：按住背景空白處拖曳，貓會盯著看，靠近時撲過去
        const cats = [...document.querySelectorAll('.cat')];
        const meowWords = ['喵～', '喵嗚？', '咪！', '呼嚕嚕…', '喵喵！'];
        const replyWords = ['喵？', '嗯？', '喵嗚～', '咪。'];
        const sleepyWords = ['喵…？', '……呼？', '嗯…'];
        const pounceWords = ['喵！', '抓到了！', '嘿！'];
        function pick(list) {
            return list[Math.floor(Math.random() * list.length)];
        }
        function otherCat(cat) {
            return cats.find(item => item !== cat);
        }
        function catPitch(cat) {
            return cat.classList.contains('cat-two') ? 1.25 : 1;
        }
        // 先移除再加回 class，連續觸發時動畫也會重新播放
        function restartAnimation(cat, name) {
            cat.classList.remove(name);
            void cat.getBoundingClientRect();
            cat.classList.add(name);
        }
        // 在貓頭上冒出一個對話泡泡
        function say(cat, text) {
            const rect = cat.getBoundingClientRect();
            const bubble = document.createElement('div');
            bubble.className = 'meow';
            bubble.setAttribute('aria-hidden', 'true');
            bubble.textContent = text;
            bubble.style.left = (rect.left + scrollX + rect.width / 2) + 'px';
            bubble.style.top = (rect.top + scrollY - 36) + 'px';
            document.body.appendChild(bubble);
            setTimeout(() => bubble.remove(), 1500);
        }
        // 讓貓的眼睛看向另一隻貓一段時間，這段時間不跟著滑鼠
        function lookAt(cat, target, duration) {
            const from = cat.getBoundingClientRect();
            const to = target.getBoundingClientRect();
            const direction = Math.sign((to.left + to.width / 2) - (from.left + from.width / 2));
            cat.querySelector('.cat-look').style.transform = `translate(${direction * 3.5}px, .5px)`;
            cat.dataset.lookUntil = Date.now() + duration;
        }
        function meow(cat, isReply = false) {
            const wasSleeping = cat.classList.contains('is-sleeping');
            cat.classList.remove('is-sleeping');
            say(cat, pick(wasSleeping ? sleepyWords : isReply ? replyWords : meowWords));
            if (!reduceMotion.matches) restartAnimation(cat, 'hop');
            playMeowSound(catPitch(cat) * (wasSleeping ? 0.85 : 1));
            if (isReply) return;
            // 另一隻醒著的話，會轉頭看過來，有六成機會回一聲
            const other = otherCat(cat);
            if (other.classList.contains('is-sleeping')) return;
            setTimeout(() => lookAt(other, cat, 1800), 350);
            if (Math.random() < 0.6) setTimeout(() => meow(other, true), 900);
        }
        cats.forEach(cat => {
            cat.addEventListener('click', () => meow(cat));
            cat.addEventListener('keydown', event => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                meow(cat);
            });
            cat.addEventListener('pointerleave', () => cat.classList.remove('on-head'));
            cat.addEventListener('animationend', event => {
                if (event.animationName === 'hop') cat.classList.remove('hop');
                if (event.animationName === 'pounce') cat.classList.remove('pounce');
            });
        });

        // ----- 睡覺 -----
        let lastActive = Date.now();
        // 夜晚打開頁面時，貓一開始就是睡著的
        setTimeout(() => {
            if (document.body.dataset.time === 'night') lastActive = 0;
        }, 0);
        function noteActivity() {
            lastActive = Date.now();
            cats.forEach(cat => cat.classList.remove('is-sleeping'));
        }
        ['pointermove', 'pointerdown', 'keydown', 'wheel'].forEach(type => document.addEventListener(type, noteActivity, {
            passive: true
        }));
        // 15 秒沒動靜就睡著，第二隻晚 5 秒才睡
        const SLEEP_AFTER = 15000;
        setInterval(() => {
            if (wandOn || happyCat) return;
            const idle = Date.now() - lastActive;
            cats.forEach((cat, index) => {
                if (idle > SLEEP_AFTER + index * 5000) cat.classList.add('is-sleeping');
            });
        }, 1000);

        // ----- 摸摸頭 -----
        let happyCat = null;
        let petCat = null;
        let petDistance = 0;
        let lastPet = null;
        let happyTimer = 0;
        let lastHeart = 0;
        let purr = null;
        function isOnHead(cat, event) {
            const rect = cat.getBoundingClientRect();
            return event.clientY < rect.top + rect.height * 0.68;
        }
        // 呼嚕聲：低沉的雜訊每秒顫動 25 次，再加上慢慢的一吸一吐
        function startPurr() {
            if (!soundOn || purr) return;
            const ctx = getAudio();
            if (!ctx) return;
            const now = ctx.currentTime;
            const source = ctx.createBufferSource();
            source.buffer = noiseBuffer(ctx, 2);
            source.loop = true;
            const low = ctx.createBiquadFilter();
            low.type = 'lowpass';
            low.frequency.value = 320;
            const pulse = ctx.createGain();
            pulse.gain.value = 0.5;
            const rumble = ctx.createOscillator();
            rumble.frequency.value = 25;
            const rumbleDepth = ctx.createGain();
            rumbleDepth.gain.value = 0.5;
            rumble.connect(rumbleDepth).connect(pulse.gain);
            const volume = ctx.createGain();
            volume.gain.setValueAtTime(0.0001, now);
            volume.gain.exponentialRampToValueAtTime(0.7, now + 0.3);
            const breath = ctx.createOscillator();
            breath.frequency.value = 0.45;
            const breathDepth = ctx.createGain();
            breathDepth.gain.value = 0.25;
            breath.connect(breathDepth).connect(volume.gain);
            source.connect(low).connect(pulse).connect(volume).connect(ctx.destination);
            source.start(now);
            rumble.start(now);
            breath.start(now);
            purr = { ctx, volume, nodes: [source, rumble, breath] };
        }
        function stopPurr() {
            if (!purr) return;
            const { ctx, volume, nodes } = purr;
            purr = null;
            const now = ctx.currentTime;
            volume.gain.cancelScheduledValues(now);
            volume.gain.setValueAtTime(volume.gain.value, now);
            volume.gain.linearRampToValueAtTime(0, now + 0.35);
            nodes.forEach(node => node.stop(now + 0.4));
        }
        function spawnHeart(x, y) {
            const heart = document.createElement('span');
            heart.className = 'heart';
            heart.setAttribute('aria-hidden', 'true');
            heart.textContent = '♥';
            heart.style.left = x + 'px';
            heart.style.top = (y - 10) + 'px';
            heart.style.setProperty('--dx', (Math.random() * 30 - 15) + 'px');
            document.body.appendChild(heart);
            setTimeout(() => heart.remove(), 1200);
        }
        function endHappy() {
            if (!happyCat) return;
            happyCat.classList.remove('is-happy');
            happyCat = null;
            petDistance = 0;
            stopPurr();
        }
        // 在同一隻貓的頭上連續移動超過一段距離，就算是在摸牠
        function onPetMove(event) {
            const cat = event.target instanceof Element ? event.target.closest('.cat') : null;
            const onHead = Boolean(cat) && !wandOn && isOnHead(cat, event);
            cats.forEach(item => item.classList.toggle('on-head', onHead && item === cat));
            if (!onHead) {
                petCat = null;
                return;
            }
            const now = performance.now();
            if (cat !== petCat || !lastPet || now - lastPet.time > 500) {
                petCat = cat;
                petDistance = 0;
                lastPet = { x: event.clientX, y: event.clientY, time: now };
                return;
            }
            petDistance += Math.hypot(event.clientX - lastPet.x, event.clientY - lastPet.y);
            lastPet = { x: event.clientX, y: event.clientY, time: now };
            if (petDistance < 140) return;
            if (happyCat !== cat) {
                endHappy();
                happyCat = cat;
                cat.classList.add('is-happy');
                startPurr();
            }
            // 停止撫摸 0.9 秒後才恢復原狀
            clearTimeout(happyTimer);
            happyTimer = setTimeout(endHappy, 900);
            if (now - lastHeart > 280) {
                lastHeart = now;
                spawnHeart(event.clientX, event.clientY);
            }
        }

        // ----- 逗貓棒 -----
        let wandOn = false;
        const wand = document.createElement('div');
        wand.className = 'wand';
        wand.setAttribute('aria-hidden', 'true');
        // 羽毛中心在 (22, 82)，會對準滑鼠位置；棍子從右上角伸進來
        wand.innerHTML = `<svg viewBox="0 0 120 110" width="120" height="110">
            <path d="M120 0 98 8" stroke="#7a5236" stroke-width="5" stroke-linecap="round" />
            <g class="wand-string">
                <path d="M98 8Q60 30 22 76" fill="none" stroke="#f5e9d2" stroke-width="1.5" />
                <g transform="translate(22 82)">
                    <ellipse rx="5" ry="15" fill="#e07a7a" transform="rotate(25)" />
                    <ellipse rx="4.5" ry="14" fill="#f2c36b" transform="rotate(-10)" />
                    <ellipse rx="4" ry="12" fill="#7fb3c8" transform="rotate(50)" />
                    <circle cy="-6" r="4" fill="#e8c35a" stroke="#a37b2c" />
                </g>
            </g>
        </svg>`;
        document.body.appendChild(wand);
        function clamp(value, min, max) {
            return Math.max(min, Math.min(max, value));
        }
        // 撲向逗貓棒的方向，1.6 秒內不會再撲
        function pounce(cat, dx, dy) {
            cat.dataset.pounceReady = Date.now() + 1600;
            cat.classList.remove('is-ready');
            cat.style.setProperty('--px', clamp(dx * 0.6, -80, 80) + 'px');
            cat.style.setProperty('--py', clamp(dy * 0.6 - 25, -90, 10) + 'px');
            cat.style.setProperty('--pr', (dx > 0 ? 8 : -8) + 'deg');
            if (!reduceMotion.matches) restartAnimation(cat, 'pounce');
            say(cat, pick(pounceWords));
            playMeowSound(catPitch(cat) * 1.15);
        }
        function moveWand(x, y) {
            wand.style.transform = `translate(${x - 22}px, ${y - 82}px)`;
            cats.forEach(cat => {
                if (cat.classList.contains('pounce')) return;
                const rect = cat.getBoundingClientRect();
                const dx = x - (rect.left + rect.width / 2);
                const dy = y - (rect.top + rect.height * 0.45);
                const distance = Math.hypot(dx, dy);
                // 進入 360px 內開始扭屁股準備，150px 內就撲過去
                cat.classList.toggle('is-ready', distance < 360 && !reduceMotion.matches);
                if (distance < 150 && Date.now() > Number(cat.dataset.pounceReady || 0)) pounce(cat, dx, dy);
            });
        }
        document.addEventListener('pointerdown', event => {
            if (event.button !== 0 || event.pointerType === 'touch') return;
            if (event.target.closest('.ticket, .topline, .cat, dialog, a, button, input, select, textarea')) return;
            event.preventDefault(); // 拖曳時不要選取到文字
            endHappy();
            wandOn = true;
            document.body.classList.add('wand-mode');
            moveWand(event.clientX, event.clientY);
        });
        function stopWand() {
            if (!wandOn) return;
            wandOn = false;
            document.body.classList.remove('wand-mode');
            cats.forEach(cat => cat.classList.remove('is-ready'));
        }
        window.addEventListener('pointerup', stopWand);
        window.addEventListener('pointercancel', stopWand);
        window.addEventListener('blur', stopWand);

        // ----- 眼睛跟著滑鼠（或逗貓棒） -----
        let pointer = null;
        let lookFrame = 0;
        function updateLook() {
            lookFrame = 0;
            cats.forEach(cat => {
                if (Date.now() < Number(cat.dataset.lookUntil || 0)) return;
                const rect = cat.getBoundingClientRect();
                const dx = pointer.x - (rect.left + rect.width / 2);
                const dy = pointer.y - (rect.top + rect.height * 0.45);
                const distance = Math.hypot(dx, dy) || 1;
                const reach = Math.min(distance / 80, 1) * 3.5; // 瞳孔最多移動 3.5 個單位，不會跑出眼眶
                cat.querySelector('.cat-look').style.transform = `translate(${dx / distance * reach}px, ${dy / distance * reach}px)`;
            });
        }
        document.addEventListener('pointermove', event => {
            onPetMove(event);
            if (wandOn) moveWand(event.clientX, event.clientY);
            pointer = { x: event.clientX, y: event.clientY };
            if (!reduceMotion.matches && !lookFrame) lookFrame = requestAnimationFrame(updateLook);
        });

        // ===== 房間光線跟著真實時間：6～16 點白天、16～19 點黃昏、其他時間夜晚 =====
        // 網址加上 ?time=day / dusk / night 可以直接預覽某個時段
        const forcedTime = new URLSearchParams(location.search).get('time');
        function updateTimeOfDay() {
            const hour = new Date().getHours();
            const period = hour >= 6 && hour < 16 ? 'day' : hour >= 16 && hour < 19 ? 'dusk' : 'night';
            document.body.dataset.time = ['day', 'dusk', 'night'].includes(forcedTime) ? forcedTime : period;
        }
        updateTimeOfDay();
        setInterval(updateTimeOfDay, 60000);

        // ===== 列印票根 =====
        document.getElementById('print-ticket').addEventListener('click', () => window.print());
    </script>
</body>

</html>