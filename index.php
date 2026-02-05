
<?php
// --- 程式邏輯區塊 ---

// 設置一個標記，判斷表單是否已提交
$is_submitted = ($_SERVER["REQUEST_METHOD"] == "POST");
$error_message = null;
$man_chart_data = [];
$woman_chart_data = [];
$man_description_html = '';
$woman_description_html = '';
$man_day_pillar = '';
$woman_day_pillar = '';
$bazhai_report = null; 
$shensha_report = null;

// --- 接收表單資料 (無論是否提交，都先定義變數以用於"Sticky Form") ---
$current_time = new DateTime();
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '男命';
$year = isset($_POST['year']) ? (int)$_POST['year'] : 1987; // 修改預設值以匹配 index8
$month = isset($_POST['month']) ? (int)$_POST['month'] : 1;
$day = isset($_POST['day']) ? (int)$_POST['day'] : 20;
$hour = isset($_POST['t_ime']) ? (int)$_POST['t_ime'] : 10;

$name_a = isset($_POST['name_a']) ? htmlspecialchars($_POST['name_a']) : '女命';
$year_a = isset($_POST['year_a']) ? (int)$_POST['year_a'] : 1990; // 修改預設值以匹配 index8
$month_a = isset($_POST['month_a']) ? (int)$_POST['month_a'] : 2;
$day_a = isset($_POST['day_a']) ? (int)$_POST['day_a'] : 5;
$hour_a = isset($_POST['t_ime_a']) ? (int)$_POST['t_ime_a'] : 15;


// 只有在表單提交後，才執行計算
if ($is_submitted) {
    try {
        // 1. 引入所有必要的檔案
        require_once 'tyme.php';
        require_once 'algorithm.php';        // 引入獨立的八宅計算檔
        require_once 'hehun_calculator.php'; // 引入獨立的神煞計算檔
        $male_descriptions = require 'm60.php';
        $female_descriptions = require 'w60.php';

        // --- 生成八字排盤數據的輔助函數 ---
        function generateBaziChart(\com\tyme\solar\SolarTime $solarTime): array
        {
            $chart = [];
            $eightChar = $solarTime->getSixtyCycleHour()->getEightChar();
            if (!$eightChar) return [];
            $lunarHour = $solarTime->getLunarHour();
            try {
                $chart['lunar_datetime'] = $lunarHour ? $lunarHour->getLunarDay()->__toString() . $lunarHour->getSixtyCycle()->getEarthBranch()->getName() . '時' : '農曆轉換失敗';
            } catch (\Exception $e) {
                $chart['lunar_datetime'] = '農曆轉換出錯';
            }
            $chart['bazi_str'] = $eightChar->getName();
            $dayMaster = $eightChar->getDay()->getHeavenStem();
            $pillarsRaw = [
                'year'  => ['name' => '年柱', 'object' => $eightChar->getYear()],
                'month' => ['name' => '月柱', 'object' => $eightChar->getMonth()],
                'day'   => ['name' => '日柱', 'object' => $eightChar->getDay()],
                'hour'  => ['name' => '时柱', 'object' => $eightChar->getHour()]
            ];
            $pillars = [];
            foreach ($pillarsRaw as $key => $p) {
                $pillarObj = $p['object'];
                $gan = $pillarObj->getHeavenStem();
                $zhi = $pillarObj->getEarthBranch();
                $hiddenStemObjects = $zhi->getHideHeavenStems();
                $hiddenStemNames = array_map(fn($hs) => $hs->getHeavenStem()->getName(), $hiddenStemObjects);
                $secondaryStars = array_map(fn($hs) => $dayMaster->getTenStar($hs->getHeavenStem())->getName(), $hiddenStemObjects);
                $extraBranches = array_map(fn($eb) => $eb->getName(), $pillarObj->getExtraEarthBranches());
                $pillars[$key] = [
                    'ten_god'        => ($key === 'day') ? '日主' : $dayMaster->getTenStar($gan)->getName(),
                    'gan'            => $gan->getName(),
                    'zhi'            => $zhi->getName(),
                    'hidden_stems'   => implode(' ', $hiddenStemNames),
                    'secondary_star' => implode(' ', $secondaryStars),
                    'sound'          => $pillarObj->getSound()->getName(),
                    'kong_wang'      => implode(',', $extraBranches),
                ];
            }
            $chart['pillars'] = $pillars;
            $chart['fetal_origin'] = $eightChar->getFetalOrigin()->getName() . ' (' . $eightChar->getFetalOrigin()->getSound()->getName() . ')';
            $chart['own_sign'] = $eightChar->getOwnSign()->getName() . ' (' . $eightChar->getOwnSign()->getSound()->getName() . ')';
            $chart['body_sign'] = $eightChar->getBodySign()->getName() . ' (' . $eightChar->getBodySign()->getSound()->getName() . ')';
            return $chart;
        }

        // --- 輔助函數：渲染單個排盤 ---
        function render_bazi_chart($title, $chart_data, $solar_year, $solar_month, $solar_day, $solar_hour) {
            if (empty($chart_data)) return;
            $solar_datetime_str = sprintf('%d年 %d月 %d日 %02d時', $solar_year, $solar_month, $solar_day, $solar_hour);
            ob_start();
            ?>
            <div class="result-section">
                <div class="result-title"><?php echo htmlspecialchars($title); ?></div>
                <p><span class="info-label">西元生日：</span><span class="info-value"><?php echo htmlspecialchars($solar_datetime_str); ?></span></p>
                <p><span class="info-label">農曆生日：</span><span class="info-value"><?php echo htmlspecialchars($chart_data['lunar_datetime']); ?></span></p>
                <p><span class="info-label">八字：</span><span class="bazi-string"><?php echo htmlspecialchars($chart_data['bazi_str']); ?></span></p>
                <table class="bazi-table">
                    <thead><tr><th></th><th>年柱</th><th>月柱</th><th>日柱</th><th>时柱</th></tr></thead>
                    <tbody>
                        <?php
                        $rows = array('ten_god' => '主星', 'gan' => '天干', 'zhi' => '地支', 'hidden_stems' => '藏干', 'secondary_star' => '副星', 'sound' => '纳音', 'kong_wang' => '空亡');
                        foreach ($rows as $key => $label) {
                            echo '<tr><th class="row-header">' . $label . '</th>';
                            foreach ($chart_data['pillars'] as $pillar) {
                                $class = $key;
                                if ($key == 'ten_god' && $pillar[$key] == '日主') $class .= ' day-master';
                                echo '<td class="' . $class . '">' . htmlspecialchars($pillar[$key]) . '</td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <div class="bazi-extra-info">
                    <div><span class="info-label">胎元：</span><?php echo htmlspecialchars($chart_data['fetal_origin']); ?></div>
                    <div><span class="info-label">命宮：</span><?php echo htmlspecialchars($chart_data['own_sign']); ?></div>
                    <div><span class="info-label">身宮：</span><?php echo htmlspecialchars($chart_data['body_sign']); ?></div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // --- 2. 生日由 tyme 轉成四柱八字 ---
        $solarTime_man = \com\tyme\solar\SolarTime::fromYmdHms($year, $month, $day, $hour, 0, 0);
        $man_chart_data = generateBaziChart($solarTime_man);
        if (empty($man_chart_data)) throw new \Exception("無法生成男命八字排盤");

        $solarTime_woman = \com\tyme\solar\SolarTime::fromYmdHms($year_a, $month_a, $day_a, $hour_a, 0, 0);
        $woman_chart_data = generateBaziChart($solarTime_woman);
        if (empty($woman_chart_data)) throw new \Exception("無法生成女命八字排盤");
        
        // --- 3. 獲取 A 資料: 日柱解說 ---
        $man_day_pillar = $man_chart_data['pillars']['day']['gan'] . $man_chart_data['pillars']['day']['zhi'];
        $man_description_html = $male_descriptions[$man_day_pillar] ?? '<p>暫無此日柱的詳細解說。</p>';
        $woman_day_pillar = $woman_chart_data['pillars']['day']['gan'] . $woman_chart_data['pillars']['day']['zhi'];
        $woman_description_html = $female_descriptions[$woman_day_pillar] ?? '<p>暫無此日柱的詳細解說。</p>';


        // --- 4. 準備並執行 B 資料: 八宅命卦分析 ---
        
        // --- MODIFICATION START ---
        // 核心輔助函式: 通過比較預期天干和實際天干，來反推正確的八字年份 (從 index8.php 移植過來)
        function getBaziSolarYear(int $input_solar_year, string $gan_from_tyme): int {
            $yearEndDigitToStem = array(4 => '甲', 5 => '乙', 6 => '丙', 7 => '丁', 8 => '戊', 9 => '己', 0 => '庚', 1 => '辛', 2 => '壬', 3 => '癸');
            $year_end_digit = $input_solar_year % 10;
            if (!isset($yearEndDigitToStem[$year_end_digit])) {

                return $input_solar_year;
            }
            $expected_gan = $yearEndDigitToStem[$year_end_digit];
            
            if ($gan_from_tyme !== $expected_gan) {
                return $input_solar_year - 1;
            }
            return $input_solar_year;
        }

        // 步驟 1: 取得正確的八字年份
        $correct_bazi_year_man = getBaziSolarYear($year, $man_chart_data['pillars']['year']['gan']);
        $correct_bazi_year_woman = getBaziSolarYear($year_a, $woman_chart_data['pillars']['year']['gan']);
        
        // 步驟 2: 取得年柱名稱
        $man_year_pillar_name = $man_chart_data['pillars']['year']['gan'] . $man_chart_data['pillars']['year']['zhi'];
        $woman_year_pillar_name = $woman_chart_data['pillars']['year']['gan'] . $woman_chart_data['pillars']['year']['zhi'];

        // 步驟 3: 使用校正後的年份進行查詢
        $man_gua = getGuaFromTable($correct_bazi_year_man, $man_year_pillar_name, 'male');
        $woman_gua = getGuaFromTable($correct_bazi_year_woman, $woman_year_pillar_name, 'female');
        // --- MODIFICATION END ---
        
        // 手動組裝報告 (此部分原碼無需修改，但現在會收到正確的命卦)
        // 假設 hehun_calculator.php 裡有這些函數，如果沒有需要從別處引入或定義
        if (!function_exists('mgong')) { function mgong($gua) { return $gua . '宮'; } } // 簡易替代
        if (!function_exists('dxsz')) { function dxsz($gua) { $east = ['坎', '离', '震', '巽']; return in_array($gua, $east) ? '東' : '西'; } } // 簡易替代
        if (!function_exists('fangwei')) { function fangwei($gua) { return $gua . '位'; } } // 簡易替代

        $bazhai_report = [
            'male_gua'       => $man_gua,
            'female_gua'     => $woman_gua,
            'male_mgong'     => mgong($man_gua),
            'female_mgong'   => mgong($woman_gua),
            'male_dxsz'      => dxsz($man_gua),
            'female_dxsz'    => dxsz($woman_gua),
            'male_fangwei'   => fangwei($man_gua),
            'female_fangwei' => fangwei($woman_gua),
            'marriage_type'  => getMarriageType($man_gua, $woman_gua),
        ];

        // --- 5. 準備並執行 C 資料: 八字神煞分析 ---
        $shensha_man_info = [
            'gender' => 'man',
            'year_zhi' => $man_chart_data['pillars']['year']['zhi'],
            'month_zhi' => $man_chart_data['pillars']['month']['zhi'],
            'day_gan' => $man_chart_data['pillars']['day']['gan']
        ];
        $shensha_woman_info = [
            'gender' => 'woman',
            'year_zhi' => $woman_chart_data['pillars']['year']['zhi'],
            'month_zhi' => $woman_chart_data['pillars']['month']['zhi'],
            'day_gan' => $woman_chart_data['pillars']['day']['gan']
        ];
        // 直接呼叫 hehun_calculator.php 中的函式
        $shensha_report = getShenshaReport($shensha_man_info, $shensha_woman_info);

    } catch (\Exception $e) {
        $error_message = "計算出錯: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>八字排盤與合婚分析系統</title>
    <style type="text/css">
        body, td, th { font-size: 14px; font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif; line-height: 1.8; color: #333; background-color: #f0f2f5; margin: 0; padding: 0; }
        h1 { text-align: center; color: #1a5276; font-size: 32px; margin-top: 25px; margin-bottom: 25px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .main-container { max-width: 1200px; margin: 20px auto; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .error { color: #dc3545; font-weight: bold; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin: 20px 0; }

        /* --- 表單樣式 --- */
        .form-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px 25px; border-radius: 5px; background-color: #fafafa; }
        .form-title { font-weight: bold; font-size: 1.5em; margin-bottom: 15px; color: #1a5276; }
        .form-table { width: 100%; border-collapse: collapse; }
        .form-table td { padding: 8px; border-top: 1px solid #eee; }
        .form-table tr:first-child td { border-top: none; }
        .form-table input[type="text"], .form-table select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; margin-right: 5px; }
        .form-table input[type="text"] { width: 100px; }
        .form-submit-row { text-align: center; padding-top: 20px; }
        .form-submit-row input[type="submit"] { padding: 12px 30px; background-color: #008CBA; color: white; border-radius: 8px; transition: background-color 0.3s ease; font-size: 16px; box-shadow: 2px 2px 5px rgba(0,0,0,0.2); text-decoration: none; border: none; cursor: pointer; }
        .form-submit-row input[type="submit"]:hover { background-color: #007bb5; }
        
        /* --- 結果樣式 --- */
        .results-wrapper { margin-top: 30px; }
        .result-section { margin-bottom: 25px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; background-color: #f9f9f9; }
        .result-title { font-weight: bold; font-size: 1.3em; margin-bottom: 15px; color: #336699; border-bottom: 2px solid #336699; padding-bottom: 5px; text-align: center; }
        .info-label { font-weight: bold; color: #666; min-width: 80px; display: inline-block; }
        .info-value { color: #333; }
        .bazi-string { font-family: 'Courier New', Courier, monospace; font-size: 1.2em; color: #0056b3; font-weight: bold; }
        .charts-container, .analysis-container { display: flex; flex-wrap: wrap; gap: 25px; }
        .charts-container > .result-section, .analysis-container > .result-section { flex: 1; min-width: 48%; box-sizing: border-box; }
        
        .bazi-table { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: center; font-size: 13px; }
        .bazi-table th, .bazi-table td { border: 1px solid #ccc; padding: 6px 4px; }
        .bazi-table thead th { background-color: #e9ecef; font-weight: bold; color: #495057; }
        .bazi-table .row-header { background-color: #e9ecef; font-weight: bold; white-space: nowrap; }
        .bazi-table tbody td { background-color: #fff; }
        .bazi-table .gan, .bazi-table .zhi { font-size: 1.1em; font-weight: bold; }
        .bazi-table .ten_god, .bazi-table .secondary_star { color: #8B4513; }
        .bazi-table .day-master { color: #dc3545; font-weight: bold; }
        .bazi-extra-info { margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }

        /* --- 日柱解說顏色區塊 --- */
        .day-pillar-analysis-male { background-color: #fff; border-left: 5px solid #3498db; padding: 20px; }
        .day-pillar-analysis-male h1, .day-pillar-analysis-male h2, .day-pillar-analysis-male h3 { color: #2980b9; border-bottom: 1px solid #d6eaf8; padding-bottom: 8px; margin-top: 20px; }
        .day-pillar-analysis-female { background-color: #fff; border-left: 5px solid #e83e8c; padding: 20px; }
        .day-pillar-analysis-female h1, .day-pillar-analysis-female h2, .day-pillar-analysis-female h3 { color: #c2185b; border-bottom: 1px solid #f8d7da; padding-bottom: 8px; margin-top: 20px; }
        .day-pillar-analysis-male p, .day-pillar-analysis-female p { font-size: 15px; line-height: 2; text-align: justify; }
        .day-pillar-analysis-male hr, .day-pillar-analysis-female hr { border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(165, 105, 189, 0.75), rgba(0, 0, 0, 0)); margin: 30px 0; }

        /* --- 綜合分析區塊樣式 --- */
        .combined-analysis-section { background-color: #fff; border: 1px solid #a569bd; border-radius: 8px; padding: 25px; margin-top: 30px; }
        .combined-analysis-title { font-size: 2em; text-align: center; color: #8e44ad; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #d7bde2; }
        .analysis-subsection { margin-bottom: 30px; }
        .analysis-subtitle { font-size: 1.5em; color: #9b59b6; margin-bottom: 15px; padding-left: 10px; border-left: 4px solid #9b59b6; }
        .analysis-table { width: 100%; border-collapse: collapse; }
        .analysis-table th, .analysis-table td { border: 1px solid #e8daef; padding: 10px; text-align: center; }
        .analysis-table th { background-color: #f5eef8; font-weight: bold; }
        .analysis-table .item-col { text-align: left; font-weight: bold; width: 25%; }
        .bazhai-conclusion { text-align: center; font-size: 1.4em; font-weight: bold; color: #ffffff; background-color: #8e44ad; padding: 12px; border-radius: 5px; margin-top: 15px; }
        .shensha-list { list-style-type: none; padding: 0; }
        .shensha-list li { background-color: #fdf2e9; border: 1px solid #f5cba7; color: #af601a; padding: 8px 12px; margin-bottom: 6px; border-radius: 4px; display: inline-block; margin-right: 8px; }
        .shensha-list .none { background-color: #f2f3f4; border-color: #dadddb; color: #7f8c8d; }
        .shensha-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .shensha-box { border: 1px solid #e8daef; padding: 15px; border-radius: 5px; }
        .shensha-box h4 { margin: 0 0 10px 0; color: #9b59b6; font-size: 1.1em; }


        @media (max-width: 992px) {
            .charts-container, .analysis-container, .shensha-container { flex-direction: column; grid-template-columns: 1fr; }
            .charts-container > .result-section, .analysis-container > .result-section { min-width: 100%; }
            .bazi-extra-info { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <h1>八字排盤與合婚分析系統</h1>

    <!-- --- 輸入表單區塊 --- -->
    <div class="form-section">
        <form action="index.php" method="post" name="bz">
            <div class="form-title">輸入資料</div>
            <table class="form-table">
                <!-- 男命輸入 -->
                <tr>
                    <td><strong>男命</strong></td>
                    <td>
                        姓名: <input type="text" name="name" size="12" maxlength="6" value="<?php echo $name; ?>">
                    </td>
                </tr>
                <tr>
                    <td>生日:</td>
                    <td>
                        西元
                        <select name="year">
                            <?php for ($i = 2040; $i >= 1930; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $year) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 年
                        <select name="month">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $month) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 月
                        <select name="day">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $day) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 日
                        <select name="t_ime">
                            <?php for ($i = 0; $i <= 23; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $hour) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 時
                    </td>
                </tr>
                <!-- 女命輸入 -->
                <tr>
                    <td><strong>女命</strong></td>
                    <td>
                        姓名: <input type="text" name="name_a" size="12" maxlength="6" value="<?php echo $name_a; ?>">
                    </td>
                </tr>
                 <tr>
                    <td>生日:</td>
                    <td>
                        西元
                        <select name="year_a">
                            <?php for ($i = 2040; $i >= 1930; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $year_a) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 年
                        <select name="month_a">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $month_a) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 月
                        <select name="day_a">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $day_a) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 日
                        <select name="t_ime_a">
                            <?php for ($i = 0; $i <= 23; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $hour_a) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 時
                    </td>
                </tr>
                <tr class="form-submit-row">
                    <td colspan="2">
                        <input name="submit" type="submit" value="開始分析">
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <!-- --- 結果顯示區塊 (只有在提交後才顯示) --- -->
    <?php if ($is_submitted): ?>
        <div class="results-wrapper">
            <?php if ($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php elseif ($man_chart_data && $woman_chart_data && $bazhai_report && $shensha_report): ?>
                <!-- A資料 - 命盤 -->
                <div class="charts-container">
                    <?php 
                    echo render_bazi_chart('男命排盤 - ' . $name, $man_chart_data, $year, $month, $day, $hour); 
                    echo render_bazi_chart('女命排盤 - ' . $name_a, $woman_chart_data, $year_a, $month_a, $day_a, $hour_a); 
                    ?>
                </div>

      

                <!-- B & C 資料 - 綜合婚配分析 -->
                <div class="combined-analysis-section">
                    <div class="combined-analysis-title">綜合婚配分析</div>
                    
                    <!-- B 資料: 八宅合婚分析 -->
                    <div class="analysis-subsection">
                        <div class="analysis-subtitle">八宅合婚分析</div>
                        <table class="analysis-table">
                            <thead>
                                <tr>
                                    <th class="item-col">項目</th>
                                    <th><?php echo htmlspecialchars($name); ?> (男)</th>
                                    <th><?php echo htmlspecialchars($name_a); ?> (女)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="item-col">年命卦</td>
                                    <td><?php echo htmlspecialchars($bazhai_report['male_gua']); ?></td>
                                    <td><?php echo htmlspecialchars($bazhai_report['female_gua']); ?></td>
                                </tr>
                                <tr>
                                    <td class="item-col">命宮</td>
                                    <td><?php echo htmlspecialchars($bazhai_report['male_mgong']); ?></td>
                                    <td><?php echo htmlspecialchars($bazhai_report['female_mgong']); ?></td>
                                </tr>
                                <tr>
                                    <td class="item-col">東西四命</td>
                                    <td><?php echo htmlspecialchars($bazhai_report['male_dxsz']); ?>四命</td>
                                    <td><?php echo htmlspecialchars($bazhai_report['female_dxsz']); ?>四命</td>
                                </tr>

                            </tbody>
                        </table>
                        <div class="bazhai-conclusion">
                            婚配結果：<?php echo htmlspecialchars($bazhai_report['marriage_type']); ?>
                        </div>
                    </div>

                    <!-- C 資料: 神煞合婚分析 (簡化版) -->
                    <div class="analysis-subsection">
                        <div class="analysis-subtitle">神煞合婚分析</div>
                        <div class="shensha-container">
                            <div class="shensha-box">
                                <h4>男命個人神煞</h4>
                                <ul class="shensha-list">
                                <?php if (!empty($shensha_report['personal']['man'])): ?>
                                    <?php foreach ($shensha_report['personal']['man'] as $shensha): ?>
                                        <li><?php echo htmlspecialchars($shensha); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="none">無</li>
                                <?php endif; ?>
                                </ul>
                            </div>
                            <div class="shensha-box">
                                <h4>女命個人神煞</h4>
                                <ul class="shensha-list">
                                <?php if (!empty($shensha_report['personal']['woman'])): ?>
                                    <?php foreach ($shensha_report['personal']['woman'] as $shensha): ?>
                                        <li><?php echo htmlspecialchars($shensha); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="none">無</li>
                                <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
          <!-- A資料 - 解說 -->
                <div class="analysis-container">
                    <div class="result-section">
                        <div class="result-title">男命日柱・<?php echo htmlspecialchars($man_day_pillar); ?>・深度解析</div>
                        <div class="day-pillar-analysis-male"><?php echo $man_description_html; ?></div>
                    </div>
                    <div class="result-section">
                        <div class="result-title">女命日柱・<?php echo htmlspecialchars($woman_day_pillar); ?>・深度解析</div>
                        <div class="day-pillar-analysis-female"><?php echo $woman_description_html; ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>