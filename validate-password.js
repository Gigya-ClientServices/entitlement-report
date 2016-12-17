var ValidatePassword = {
  clearSelection: function() {
    if (window.getSelection) {
      if (window.getSelection().empty) {  // Chrome
        window.getSelection().empty();
      } else if (window.getSelection().removeAllRanges) {  // Firefox
        window.getSelection().removeAllRanges();
      }
    } else if (document.selection) {  // IE?
      document.selection.empty();
    }
  },

  selectText: function(containerId) {
      ValidatePassword.clearSelection();
      if (document.selection) {
          var range = document.body.createTextRange();
          range.moveToElementText(document.getElementById(containerId));
          range.select();
      } else if (window.getSelection) {
          var range = document.createRange();
          range.selectNode(document.getElementById(containerId));
          window.getSelection().addRange(range);
      }
  },

  submitTest: function(input) {
    var f = input.form;
    document.getElementById("mode").value = "test";
    f.submit();
  },

  submitValidate: function(input) {
    var f = input.form;
    document.getElementById("mode").value = "validate";
    f.submit();
  },

  updateFormatSettings: function(format) {
    // Custom
    if (format === "custom") {
      $('#customFormat').show();
      $('#binaryModifiers').hide();
      $('#binarySaltEncodingHex').prop('disabled', true);
      $('#binarySaltEncodingBase64').prop('disabled', true);
      $('#saltGroup').show();
    }
    else if (format === "none") {
      $('#customFormat').hide();
      $('#binaryModifiers').hide();
      $('#binarySaltEncodingHex').prop('disabled', true);
      $('#binarySaltEncodingBase64').prop('disabled', true);
      $('#saltGroup').hide();
    }
    else {
      $('#customFormat').hide();
      $('#binaryModifiers').show();
      $('#binarySaltEncodingHex').prop('disabled', false);
      $('#binarySaltEncodingBase64').prop('disabled', false);
      $('#saltGroup').show();
    }
  },

  updateAlgorithmSettings: function(algo) {
    if (algo === "crypt") {
      $("#cryptGroup").show();
    } else {
      $("#cryptGroup").hide();
    }
  },

  hashFormatChanged: function(input) {
    this.updateFormatSettings(input.value);
  },

  algorithmChanged: function(input) {
    this.updateAlgorithmSettings(input.value);
  },

  afterLoad: function() {
    var loc = document.forms[0].elements['saltLoc'].value;
    var algo = document.forms[0].elements['algo'].value;
    this.updateFormatSettings(loc);
    this.updateAlgorithmSettings(algo);
  }
}
