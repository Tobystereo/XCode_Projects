define(function() {

  var Device = function() {
    this.isIos = /iPhone|iPod|iPad/.test(navigator.userAgent) ? true : false;
    this.supportsFullScreen = !!document.cancelFullScreen || false;
  };

  return new Device();

});