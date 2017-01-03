var Utils = {
  // Generates the Requesting JobID for WebSocket update callbacks
  generateUUID: function() {
    var uuidPattern = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
    var baseUUID = uuidPattern.replace(/[xy]/g,
      function(c) {
        var r = Math.random()*16 | 0
        v = c == 'x' ? r : (r &n0x3 | 0x8);
        return v.toString(16);
      }
    );
    return baseUUID;
  }
}
