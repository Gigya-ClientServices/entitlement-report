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
      'generate-report.php',
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
          var r = $('#report');
          r.show();
          if (data.errCode == 0) {
            r.html(UsageReport.formatResults(data));
          } else {
            r.html(UsageReport.formatErrors(data.errors));
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

  formatResults: function(results) {
    var outString =
                "<div class='row' style='margin: 0px 30px 0px 10px;''>" +
                "<h3>Report for '" + results.partner.companyName + "' (" + results.partner.partnerID + ") &nbsp;" +
                ((results.partner.isEnabled)?"<span class='label label-success'>Enabled</span>":"<span class='label label-danger'>Disabled</span>") +
                ((results.partner.isTrial)?" &nbsp;<span class='label label-info'>Trial Account</span>":"") +
                "</h3>" +
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

  formatErrors: function(errors) {
    var outString = ""
    for (errorIndex in errors) {
        error = errors[errorIndex];
    		outString += '<div class="bs-callout bs-callout-danger"><h4>' + error.message + '</h4><pre>' + error.log + '</pre></div>'
    }
    return outString;
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
  }

}
