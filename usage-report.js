var UsageReport = {
  submitReport: function(input) {
    var w = $( window ).width();
    var h = $( window ).height();

    var r = $('#report');
    r.hide();
    r.html('');

    var o = $('#overlay')
    o.width(w);
    o.height(h - 64);
    o.show();

    $.ajax(
      'https://generate-report.php',
      {
        dataType: 'json',
        method: 'POST',
        data: {
          'partnerID': $('#partnerID').val(),
          'userKey': $('#userKey').val(),
          'userSecret': $('#userSecret').val()
        },
        success: function(data, status, e) {
          //alert('success');
          var o = $('#overlay')
          o.hide();
          if (data.errCode == 0) {
            var r = $('#report');
            r.show();
            r.html(UsageReport.formatResults(data));
          }
          else {
            alert('Error: ' + errCode + '/n' + JSON.stringify(data.errors));
          }
        },
        error: function(e, status, errorMessage) {
          var o = $('#overlay')
          o.hide();
          alert('Error: ' + status + ' : ' + errorMessage);
        }
      }
    );
  },

  formatTestImage: function(test, img, altText) {
    var outString = "";
    if (altText === null) altText = "";
      if (test) {
        outString = "<img src='" + img + "' tooltip='" + altText + "' alt='" + altText + "'></img>";
      } else {
        outString = "&nbsp;";
      }

      return outString;
  },

  formatResults: function(results) {
    var outString =
                "<div class='row' style='margin: 0px 30px 0px 10px;''>" +
                "<table class='col-md-12'>" +
                "<thead>" +
                "<tr>" +
                "<th>SiteID</th>" +
                "<th>APIKey</th>" +
                "<th>DS</th>" +
                "<th>IdS</th>" +
                "<th>RaaS</th>" +
                "<th>SSO</th>" +
                "<th>Par</th>" +
                "<th>Child Keys</th>" +
                "<th>User Count</th>" +
                "<th>Last Login</th>" +
                "<th>Last Create</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";
      for (siteID in results.sites) {
        site = results.sites[siteID];
        if (!site.isChild) {
          outString +=
                "<tr border='1px'>" +
                "<td>" + site.id + "</td>" +
                "<td>" + site.apiKey +"</td>" +
                "<td>" + UsageReport.formatTestImage(site.hasDS,'img/pass.png','DS Enabled') + "</td>";

          if (site.hasIdS) {
            outString +=
            "<td>" + UsageReport.formatTestImage(site.hasIdS,'img/pass.png','IdS Enabled') + "</td>" +
            "<td>" + UsageReport.formatTestImage(site.hasRaaS,'img/pass.png','RaaS Enabled') + "</td>" +
            "<td>" + UsageReport.formatTestImage(site.hasSSO,'img/pass.png','SSO Enabled') + "</td>" +
            "<td>" + UsageReport.formatTestImage(site.isParent,'img/pass.png','Site Group Parent') + "</td>" +
            "<td>" + site.childSiteCount + "</td>" +
            "<td>" + site.userCount + "</td>" +
            "<td " + ((site.lastLogin == results.summary.lastLogin)?"style='background: #CFC;'":"") + ">" + site.lastLogin + "</td>" +
            "<td " + ((site.lastCreated == results.summary.lastCreated)?"style='background: #CFC;'":"") + ">" + site.lastCreated + "</td>" +
            "</tr>";
          } else {
            outString += "<td colspan='8' style='text-align: center; background: #FEE;'>-Social Login Only-</td></tr>";
          }
        }
      }

      // Summary Info
      outString +=
        "<tr border='1px' style='background: #CCC;'>" +
        "<td colspan='8'><b>Summary</b></td>" +
        "<td>" + results.summary.userCount + "</td>" +
        "<td>" + results.summary.lastLogin + "</td>" +
        "<td>" + results.summary.lastCreated + "</td>" +
        "</tr>";

      outString += "</tbody></table></div>";

      return outString;
  },
  insertParam: function(key, value) {
    key = encodeURI(key);
    value = encodeURI(value);
    var kvp = document.location.search.substr(1).split('&');
    var i=kvp.length;
    var x;
    while(i--) {
      x = kvp[i].split('=');

      if (x[0]==key) {
        x[1] = value;
        kvp[i] = x.join('=');
        break;
      }
    }
    if(i<0) {kvp[kvp.length] = [key,value].join('=');}
    //this will reload the page, it's likely better to store this until finished
    document.location.search = kvp.join('&');
  }
}
