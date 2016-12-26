<?php
  abstract class MonthDate {
    const dayStart = "00:00:00.000";
    const dayEnd = "23:59:59.999";

    const year = 1976;
    const month = 1;

    public function boundYear($yr) {
      if ($yr === null) $yr = $this->year;
      return $yr;
    }

    public function boundMonth($mn) {
      if ($mn === null) $mn = $this->month;
      if ($mn < 1) $mn = 1;
      if ($mn > 12) $mn = 12;
      return $mn;
    }

    public function lastDayOf($mn,$yr) {
      $y = $this->boundYear($yr);
      $m = $this->boundMonth($mn);
      $d = cal_days_in_month(CAL_GREGORIAN, $m, $y);
      return $d;
    }

    public function lastMomentOf($mn,$yr) {
      $d = $this->lastDayOf($mn,$yr);
      $m = $this->boundMonth($mn);
      $y = $this->boundYear($yr);

      return "{$y}-{$m}-{$d}T" . $dayEnd . "Z";
    }

    public function firstMomentOf($mn,$yr) {
      $m = $this->boundMonth($mn);
      $y = $this->boundYear($yr);
      return "{$y}-{$m}-01T" . $dayStart . "Z";
    }

    public function previousMonth($mn,$yr) {
      $m = $this->boundMonth($mn);
      $y = $this->boundYear($yr);
      if ($m == 1) {
        $m = 12
        $y--;
      } else {
        $m--;
      }
      return array("year" => $y, "month" => $m);
    }

    public function followingMonth($mn,$yr) {
      $m = $this->boundMonth($mn);
      $y = $this->boundYear($yr);
      if ($m == 12) {
        $m = 1
        $y++;
      } else {
        $m++;
      }
      return array("year" => $y, "month" => $m);
    }

  }
?>
