var EntitlementReport = {
  submitReport: function(input) {
    var f = input.form;
    f.submit();
  },

  processResults: function(response) {

  },

  formatResults: function(results) {
/*
      $outString =	"<table class='col-md-12'>" .
                    "<thead>" .
                    "<tr>" .
                    "<th>SiteID</th>" .
                    "<th>APIKey</th>" .
                    "<th>DS</th>" .
                    "<th>IdS</th>" .
                    "<th>RaaS</th>" .
                    "<th>SSO</th>" .
                    "<th>Par</th>" .
                    "<th>Child Keys</th>" .
                    "<th>User Count</th>" .
                    "<th>Last Login</th>" .
                    "<th>Last Create</th>" .
                    "</tr>" .
                    "</thead>" .
                    "<tbody>";
      foreach ($sites as $site) {
        if (!$site['isChild']) {
          $outString = $outString .
          "<tr border='1px'>" .
          "<td>{$site["id"]}</td>" .
          "<td>{$site["apiKey"]}</td>" .
          "<td>" . formatTestImage($site['hasDS'],'img/pass.png','DS Enabled') . "</td>";

          if ($site['hasIdS']) {
            $outString = $outString .
            "<td>" . formatTestImage($site['hasIdS'],'img/pass.png','IdS Enabled') . "</td>" .
            "<td>" . formatTestImage($site['hasRaaS'],'img/pass.png','RaaS Enabled') . "</td>" .
            "<td>" . formatTestImage($site['hasSSO'],'img/pass.png','SSO Enabled') . "</td>" .
            "<td>" . formatTestImage($site['isParent'],'img/pass.png','Site Group Parent') . "</td>" .
            "<td>{$site["childSiteCount"]}</td>" .
            "<td>{$site["count"]}</td>" .
            "<td " . (($site["lastLogin"] == $summary["lastLogin"])?"style='background: #CFC;'":"") . ">{$site["lastLogin"]}</td>" .
            "<td " . (($site["lastCreated"] == $summary["lastCreated"])?"style='background: #CFC;'":"") . ">{$site["lastCreated"]}</td>" .
            "</tr>";
          } else {
            $outString = $outString . "<td colspan='8' style='text-align: center; background: #FEE;'>-Social Login Only-</td></tr>";
          }
        }
      }
      // Summary Info
      $outString = $outString .
        "<tr border='1px' style='background: #CCC;'>" .
        "<td colspan='8'><b>Summary</b></td>" .
        "<td>{$summary["count"]}</td>" .
        "<td>{$summary["lastLogin"]}</td>" .
        "<td>{$summary["lastCreated"]}</td>" .
        "</tr>";

      $outString = $outString . "</tbody></table>";

      return $outString;*/
  }
}
