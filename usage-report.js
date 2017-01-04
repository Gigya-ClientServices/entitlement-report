function pad(n, width, z) {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

var UsageReport = {
  csvActivityData: [],
  csvGrowthData: [],
  partnerID: "",
  startMonth: "",
  startYear: "",
  endMonth: "",
  endYear: "",

  submitReport: function(input) {
    var w = $( window ).width();
    var h = $( window ).height();

    var r = $('#report');
    r.hide();

    var e = $('#errors');
    e.hide();

    var o = $('#overlay')
    o.width(w);
    o.height(h - 64);
    o.show();

    UsageReport.partnerID = $('#partnerID').val();
    UsageReport.startMonth = $('#startMonth').val();
    UsageReport.startYear = $('#startYear').val();
    UsageReport.endMonth = $('#endMonth').val();
    UsageReport.endYear = $('#endYear').val();

    $.ajax(
      'generate-report.php',
      {
        dataType: 'json',
        method: 'POST',
        data: {
          'partnerID': $('#partnerID').val(),
          'userKey': $('#userKey').val(),
          'userSecret': $('#userSecret').val(),
          'includeSegments': $('#includeSegments').is(':checked'),
          'startMonth': $('#startMonth').val(),
          'startYear': $('#startYear').val(),
          'endMonth': $('#endMonth').val(),
          'endYear': $('#endYear').val()
        },
        success: function(data, status, e) {
          //alert('success');
          var o = $('#overlay')
          o.hide();
          if (data.errCode == 0) {
            var r = $('#report');
            var h = $('#heading');
            var overviewTab = $('#overview');
            var dataTab = $('#data');
            h.html(UsageReport.formatHeader(data));
            overviewTab.html(UsageReport.formatResults(data));
            if ($('#includeSegments').is(':checked')) {
              $('#segmentTab').show();
              UsageReport.formatChartData(data);
              UsageReport.csvActivityData = data.csvData.activity;
              UsageReport.csvGrowthData = data.csvData.growth;
            } else {
              $('#segmentTab').hide();
            }
            r.show();
            $('.main').hide();
            $('#replay').show();
          } else {
            var e = $('#errors');
            e.show();
            e.html(UsageReport.formatErrors(data.errors));
          }
        },
        error: function(e, status, errorMessage) {
          var o = $('#overlay')
          o.hide();
          $('#modal-error-text').html('<p>' + status + ' : ' + errorMessage + '</p>');
          $('#error-modal').modal('show');
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

  formatHeader: function(results) {
    var outString =
      "<h3>Report for '" + results.partner.companyName + "' (" + results.partner.partnerID + ") &nbsp;" +
      ((results.partner.isEnabled)?"<span class='label label-success'>Enabled</span>":"<span class='label label-danger'>Disabled</span>") + "&nbsp; " +
      ((results.partner.isTrial)?"<span class='label label-info'>Trial</span>":"<span class='label label-warning'>Paid</span>") +
      "</h3>";
    return outString;
  },

  formatResults: function(results) {
    var outString =
                "<div class='row' style='margin: 0px 30px 0px 10px;'>" +
                "<h4>Enabled Services</h4>" +
                "<table class='col-md-12 table-bordered'>" +
                "<thead>" +
                "<tr>" +
                "<th>GM</th>" +
                "<th>Comments</th>" +
                "<th>DS</th>" +
                "<th>IdS</th>" +
                "<th>RaaS</th>" +
                "<th>CI</th>" +
                "<th>Counters</th>" +
                "<th>SAML IdP</th>" +
                "<th>Audits</th>" +
                "<th>Nexus</th>" +
                "</tr>" +
                "</thead>" +
                "<tbody>" +
                "<tr>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsGM,'img/pass.png','Game Mechanics') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsComments,'img/pass.png','Comments') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsDS,'img/pass.png','DS') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsIdS,'img/pass.png','IdS') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsRaaS,'img/pass.png','RaaS') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsCI,'img/pass.png','Consumer Insights') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsCounters,'img/pass.png','Counters') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsSAMLIdP,'img/pass.png','SAML IdP') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsAudit,'img/pass.png','Audit Log') + "</td>" +
                "<td>" + UsageReport.formatTestImage(results.partner.allowsNexus,'img/pass.png','Nexus Integrations') + "</td>" +
                "</tr></tbody></table><br/><br/>";

    outString +=
                "<h4>Sites</h4>" +
                "<table class='col-md-12 table-striped table-bordered'>" +
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
                "<tr>" +
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
        "<tr style='background: #CCC;'>" +
        "<td colspan='8'><b>Summary</b></td>" +
        "<td>" + results.summary.userCount + "</td>" +
        "<td>" + results.summary.lastLogin + "</td>" +
        "<td>" + results.summary.lastCreated + "</td>" +
        "</tr>";

      outString += "</tbody></table></div>";

      return outString;
  },

  formatErrors: function(errors) {
    var outString = "";
    for (errorIndex in errors) {
        error = errors[errorIndex];
    		outString += '<div class="bs-callout bs-callout-danger"><h4>' + error.message + '</h4><pre>' + error.extended + '</pre></div>'
    }
    return outString;
  },

  formatChartData: function(results) {
    var lineChartOptions = {
      responsive: false,
      maintainAspectRatio: false,
      bezierCurve: false,
      lineTension: 0,
      tension:0 /*,
      scales: {
        yAxes: [{
          display: true,
          ticks: {
            suggestedMin: 0    // minimum will be 0, unless there is a lower value.
          }
        }]
      }*/
    };

    var growthCtx = $('#growthChart');
    var activityCtx = $('#activityChart');
    var growthChart = new Chart(growthCtx, {
      type: 'line',
      tension: 0,
      lineTension: 0,
      bezierCurve: false,
      data: {
        labels: results.summary.segments.labels,
        datasets: [{
          label: '# of Created Users',
          data: results.summary.segments.data,
          backgroundColor: [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 206, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(255, 159, 64, 0.2)'
          ],
          borderColor: [
            'rgba(255,99,132,1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: lineChartOptions
    });
    var activityChart = new Chart(activityCtx, {
      type: 'line',
      tension: 0,
      lineTension: 0,
      bezierCurve: false,
      data: {
        labels: results.summary.segments.labels,
        datasets: [{
          label: '# of Created Users',
          data: results.summary.segments.deltas,
          backgroundColor: [
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 99, 132, 0.2)',
            'rgba(255, 206, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(255, 159, 64, 0.2)'
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132,1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: lineChartOptions
    });
  },

  performCSVDownload: function(data, filename) {
    // Add a link to download if supported
    if ("download" in document.createElement("a")) {
      var link = $("<a target='_blank' href='data:text/csv;charset=utf-8,%EF%BB%BF" + encodeURI(data.join("\n")) + "' download='" + filename + "'></a>");
      link.appendTo("body");
      link[0].click();
      // Remove the temporary link after 50 milliseconds
      setTimeout(function () {
        link.remove();
      }, 50);
      return;
    }
    // Otherwise add an IFRAME to force teh download
    var txt = $("<textarea cols='65536'></textarea>").get(0);
    txt.innerHTML = data.join("\n");
    var frame = $("<iframe src='text/csv;charset=utf-8' style='display:none'></iframe>").appendTo("body").get(0);
    frame.contentWindow.document.open("text/csv;charset=utf-8", "replace");
    frame.contentWindow.document.write(txt.value);
    frame.contentWindow.document.close();
    frame.contentWindow.document.execCommand("SaveAs", true, filename);
    // Remove the temporary iframe after 50 milliseconds
    setTimeout(function () {
      $(frame).remove();
      $(txt).remove();
    }, 50);
  },

  downloadCSVData: function(dataset) {
    var filename = dataset + "_" +
               UsageReport.partnerID + "_" +
               UsageReport.startYear + pad(UsageReport.startMonth,2) + "-" +
               UsageReport.endYear + pad(UsageReport.endMonth,2) + ".csv";
    switch(dataset) {
      case "growth":
        UsageReport.performCSVDownload(UsageReport.csvGrowthData, filename);
      break;
      case "activity":
        UsageReport.performCSVDownload(UsageReport.csvActivityData, filename);
      break;
    }
  },

  updateSettings: function(settings) {
    if (settings) {
      $('.start-month').show();
      $('.start-year').show();
      $('.end-month').show();
      $('.end-year').show();
    } else {
      $('.start-month').hide();
      $('.start-year').hide();
      $('.end-month').hide();
      $('.end-year').hide();
    }
  },

  includeSegmentsChanged: function(input) {
    var c = input.checked;
    UsageReport.updateSettings(c);
  },

  showMain: function() {
    $('.main').show();
    $('#replay').hide();
  }
}
