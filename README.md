# 八字排盤與合婚分析系統 (Bazi & Hehun Analysis System)

這是我個人為了學習與實作傳統命理工具而開發的 PHP 小專案，結合八字排盤與八宅合婚分析功能。

- **[👉 結合wordpresss 的 線上即時排盤版本](https://www.myorz.com/免費八字合婚)**

## ✨ 核心功能
- 專業八字排盤（年月日時四柱、藏干、副星、納音）
- 男命 / 女命不同解說
- 命卦合婚（三元命卦判定）
- 婚配等級與神煞交互分析

## 🛠 技術棧
- PHP 7.4+
- 曆法轉換：使用 [6tail/tyme4php](https://github.com/6tail/tyme4php) 開源庫 (即本專案中之 `tyme.php`)。（感謝原作者）
- 八宅與神煞邏輯由 `algorithm.php` 與 `hehun_calculator.php` 自實作
- 前端為純 CSS 響應式介面（自行設計）

## 📂 檔案結構
* `index.php`：系統主程式與 UI 介面。
* `algorithm.php`：八宅婚配計算引擎。
* `hehun_calculator.php`：神煞分析計算引擎。
* `m60.php` / `w60.php`：存放 60 甲子日柱的解說資料（由主程式引入）。
* `tyme.php`：底層曆法轉換核心庫 (來源自 [6tail](https://github.com/6tail))。

## 🚀 快速開始
1. 將本專案複製到你的網頁伺服器（如 Apache 或 Nginx）的根目錄下。
2. 確保伺服器已安裝並啟動 PHP。
3. 在瀏覽器輸入 `http://localhost/index.php` 即可開始使用。

**線上體驗**  
個人網站上的完整版本會加入更詳細的斷語與更好看的呈現方式：  
https://www.myorz.com/免費八字合婚

## ⚠️ 注意事項
* 本系統僅供命理愛好者參考，不作為人生重大決策的唯一依據。
* 請確保 `tyme.php` 與資料檔 `m60.php`、`w60.php` 路徑正確，否則將無法生成排盤。

---
個人學習與實作作品，僅供參考與交流。歡迎 fork 或提供建議。
