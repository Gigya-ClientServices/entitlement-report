<?php
  abstract class MonthDate {
    const dayStart = "00:00:00.000";
    const dayEnd = "23:59:59.999";

    const year = 1976;
    const month = 1;

    public function boundYear($yr) {
      if ($yr === null) $yr = MonthDate::year;
      return $yr;
    }

    public function boundMonth($mn) {
      if ($mn === null) $mn = MonthDate::month;
      if ($mn < 1) $mn = 1;
      if ($mn > 12) $mn = 12;
      return $mn;
    }

    public function lastDayOf($mn,$yr) {
      $y = MonthDate::boundYear($yr);
      $m = MonthDate::boundMonth($mn);
      $d = cal_days_in_month(CAL_GREGORIAN, $m, $y);
      return $d;
    }

    public function lastMomentOf($mn,$yr) {
      $d = MonthDate::lastDayOf($mn,$yr);
      $m = MonthDate::boundMonth($mn);
      $y = MonthDate::boundYear($yr);

      return "{$y}-{$m}-{$d}T" . MonthDate::dayEnd . "Z";
    }

    public function firstMomentOf($mn,$yr) {
      $m = MonthDate::boundMonth($mn);
      $y = MonthDate::boundYear($yr);
      return "{$y}-{$m}-01T" . MonthDate::dayStart . "Z";
    }

    public function previousMonth($mn,$yr) {
      $m = MonthDate::boundMonth($mn);
      $y = MonthDate::boundYear($yr);
      if ($m == 1) {
        $m = 12;
        $y--;
      } else {
        $m--;
      }
      return array("year" => $y, "month" => $m);
    }

    public function followingMonth($mn,$yr) {
      $m = MonthDate::boundMonth($mn);
      $y = MonthDate::boundYear($yr);
      if ($m == 12) {
        $m = 1;
        $y++;
      } else {
        $m++;
      }
      return array("year" => $y, "month" => $m);
    }

    public function getMonthsList($startMonth, $startYear, $endMonth, $endYear) {
      $list = array();
      $currentMonth = $startMonth;
      $currentYear = $startYear;

      $endDate = MonthDate::followingMonth($endMonth, $endYear);
      $em = str_pad($endDate["month"],2,'0',STR_PAD_LEFT);
      $endComp = "{$endDate['year']}{$em}";
      $comp = "";
      do  {
        array_push($list, array("year" => $currentYear, "month" => $currentMonth));
        $temp = MonthDate::followingMonth($currentMonth, $currentYear);
        $currentMonth = $temp["month"];
        $currentYear= $temp["year"];
        $cm = str_pad($currentMonth,2,'0',STR_PAD_LEFT);
        $comp = "{$currentYear}{$cm}";
      } while ($comp < $endComp);

      return $list;
    }
  }
?>
