<?php
/**
 * 八宅婚配計算引擎 (完整功能版)
 * 版本: v1.0
 * 說明: 包含命卦查表、婚配類型、以及命宮/東西四命/方位等輔助計算函數。
 */

if (!function_exists('getGuaFromTable')) {

    $GLOBALS['baziGuaMap'] = array(
        '1969-己酉' => array('male' => '巽', 'female' => '坤'),
        '1970-庚戌' => array('male' => '震', 'female' => '震'), '1971-辛亥' => array('male' => '坤', 'female' => '巽'),
        '1972-壬子' => array('male' => '坎', 'female' => '艮'), '1973-癸丑' => array('male' => '离', 'female' => '乾'),
        '1974-甲寅' => array('male' => '艮', 'female' => '兑'), '1975-乙卯' => array('male' => '兑', 'female' => '艮'),
        '1976-丙辰' => array('male' => '乾', 'female' => '离'), '1977-丁巳' => array('male' => '坤', 'female' => '坎'),
        '1978-戊午' => array('male' => '巽', 'female' => '坤'), '1979-己未' => array('male' => '震', 'female' => '震'),
        '1980-庚申' => array('male' => '坤', 'female' => '巽'), '1981-辛酉' => array('male' => '坎', 'female' => '艮'),
        '1982-壬戌' => array('male' => '离', 'female' => '乾'), '1983-癸亥' => array('male' => '艮', 'female' => '兑'),
        '1984-甲子' => array('male' => '兑', 'female' => '艮'), '1985-乙丑' => array('male' => '乾', 'female' => '离'),
        '1986-丙寅' => array('male' => '坤', 'female' => '坎'), '1987-丁卯' => array('male' => '巽', 'female' => '坤'),
        '1988-戊辰' => array('male' => '震', 'female' => '震'), '1989-己巳' => array('male' => '坤', 'female' => '巽'),
        '1990-庚午' => array('male' => '坎', 'female' => '艮'), '1991-辛未' => array('male' => '离', 'female' => '乾'),
        '1992-壬申' => array('male' => '艮', 'female' => '兑'), '1993-癸酉' => array('male' => '兑', 'female' => '艮'),
        '1994-甲戌' => array('male' => '乾', 'female' => '离'), '1995-乙亥' => array('male' => '坤', 'female' => '坎'),
        '1996-丙子' => array('male' => '巽', 'female' => '坤'), '1997-丁丑' => array('male' => '震', 'female' => '震'),
        '1998-戊寅' => array('male' => '坤', 'female' => '巽'), '1999-己卯' => array('male' => '坎', 'female' => '艮'),
        '2000-庚辰' => array('male' => '离', 'female' => '乾'), '2001-辛巳' => array('male' => '艮', 'female' => '兑'),
        '2002-壬午' => array('male' => '兑', 'female' => '艮'), '2003-癸未' => array('male' => '乾', 'female' => '离'),
        '2004-甲申' => array('male' => '坤', 'female' => '坎'), '2005-乙酉' => array('male' => '巽', 'female' => '坤'),
        '2006-丙戌' => array('male' => '震', 'female' => '震'), '2007-丁亥' => array('male' => '坤', 'female' => '巽'),
        '2008-戊子' => array('male' => '坎', 'female' => '艮'), '2009-己丑' => array('male' => '离', 'female' => '乾'),
        '2010-庚寅' => array('male' => '艮', 'female' => '兑'), '2011-辛卯' => array('male' => '兑', 'female' => '艮'),
        '2012-壬辰' => array('male' => '乾', 'female' => '离'), '2013-癸巳' => array('male' => '坤', 'female' => '坎'),
        '2014-甲午' => array('male' => '巽', 'female' => '坤'), '2015-乙未' => array('male' => '震', 'female' => '震'),
        '2016-丙申' => array('male' => '坤', 'female' => '巽'), '2017-丁酉' => array('male' => '坎', 'female' => '艮'),
        '2018-戊戌' => array('male' => '离', 'female' => '乾'), '2019-己亥' => array('male' => '艮', 'female' => '兑'),
        '2020-庚子' => array('male' => '兑', 'female' => '艮'), '2021-辛丑' => array('male' => '乾', 'female' => '离'),
        '2022-壬寅' => array('male' => '坤', 'female' => '坎'), '2023-癸卯' => array('male' => '巽', 'female' => '坤'),
        '2024-甲辰' => array('male' => '震', 'female' => '震')
    );

    $GLOBALS['marriageCompatibilityMap'] = array(
        '坎' => array('震' => '福德上婚', '巽' => '生氣上婚', '艮' => '天醫上婚', '离' => '絕體中婚', '乾' => '遊魂中婚', '坎' => '歸魂中婚', '兑' => '五鬼下婚', '坤' => '絕命下婚'),
        '坤' => array('兑' => '福德上婚', '艮' => '生氣上婚', '巽' => '天醫上婚', '乾' => '絕體中婚', '离' => '遊魂中婚', '坤' => '歸魂中婚', '震' => '五鬼下婚', '坎' => '絕命下婚'),
        '震' => array('坎' => '福德上婚', '离' => '生氣上婚', '乾' => '天醫上婚', '巽' => '絕體中婚', '艮' => '遊魂中婚', '震' => '歸魂中婚', '坤' => '五鬼下婚', '兑' => '絕命下婚'),
        '巽' => array('离' => '福德上婚', '坎' => '生氣上婚', '坤' => '天醫上婚', '震' => '絕體中婚', '兑' => '遊魂中婚', '巽' => '歸魂中婚', '乾' => '五鬼下婚', '艮' => '絕命下婚'),
        '乾' => array('艮' => '福德上婚', '兑' => '生氣上婚', '震' => '天醫上婚', '坤' => '絕體中婚', '坎' => '遊魂中婚', '乾' => '歸魂中婚', '巽' => '五鬼下婚', '离' => '絕命下婚'),
        '兑' => array('坤' => '福德上婚', '乾' => '生氣上婚', '离' => '天醫上婚', '艮' => '絕體中婚', '巽' => '遊魂中婚', '兑' => '歸魂中婚', '坎' => '五鬼下婚', '震' => '絕命下婚'),
        '艮' => array('乾' => '福德上婚', '坤' => '生氣上婚', '坎' => '天醫上婚', '兑' => '絕體中婚', '震' => '遊魂中婚', '艮' => '歸魂中婚', '离' => '五鬼下婚', '巽' => '絕命下婚'),
        '离' => array('巽' => '福德上婚', '震' => '生氣上婚', '兑' => '天醫上婚', '坎' => '絕體中婚', '坤' => '遊魂中婚', '离' => '歸魂中婚', '艮' => '五鬼下婚', '乾' => '絕命下婚')
    );

    function getGuaFromTable(int $solarYear, string $yearPillarName, string $gender): string {
        global $baziGuaMap;
        $key = $solarYear . '-' . $yearPillarName;
        if (isset($baziGuaMap[$key])) {
            $genderKey = ($gender === 'male' || $gender === 'man') ? 'male' : 'female';
            if (isset($baziGuaMap[$key][$genderKey])) {
                return $baziGuaMap[$key][$genderKey];
            }
        }
        return "未知";
    }

    function getMarriageType(string $maleGua, string $femaleGua): string {
        global $marriageCompatibilityMap;
        if (isset($marriageCompatibilityMap[$maleGua], $marriageCompatibilityMap[$maleGua][$femaleGua])) {
            return $marriageCompatibilityMap[$maleGua][$femaleGua];
        }
        return "未知組合";
    }
 

    function mgong(string $gua): string {
        if ($gua === "未知") return "未知宮";
        return $gua . '宮';
    }

    function dxsz(string $gua): string {
        $east_group = array('坎', '离', '震', '巽');
        $west_group = array('乾', '坤', '艮', '兑');
        if (in_array($gua, $east_group)) {
            return '東';
        }
        if (in_array($gua, $west_group)) {
            return '西';
        }
 
        return '西';
    }

    function fangwei(string $gua): string {
        $map = array(
            '坎' => '正北',
            '坤' => '西南',
            '震' => '正東',
            '巽' => '東南',
            '乾' => '西北',
            '兑' => '正西',
            '艮' => '東北',
            '离' => '正南'
        );
        return isset($map[$gua]) ? $map[$gua] : '未知方位';
    }
}