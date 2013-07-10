/*

Licence:
==============================================================================

(c) 2011, Kaldor Holdings Ltd. All rights reserved.

Use in source and binary forms for commercially licensed Pugpig customers is governed by the Pugpig Software Licence Agreement at http://pugpig.com/download/licences/pugpig_licence_agreement.txt

For all other parties, use in source and binary forms is governed by the Pugpig Software Evaluation Agreement at http://pugpig.com/download/licences/pugpig_evaluation_agreement.txt

By downloading, reading and/or using this source, you agree to become a licensee of the Pugpig Software Suite and you are bound by the terms of the the licence agreement.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

define([
  'underscore',
  'text!templates/add-this.html'
], function( _, toolboxTmpl ) {

  var AddThis = function( config ) {
    this.el = config.el;
    this.id = config.id;
    this.template = _.template( toolboxTmpl );
    this.render();
    this.subscribe();
  };

  AddThis.fn = AddThis.prototype;

  AddThis.fn.subscribe = function() {
    $(window).on('updateAddThisUrl', $.proxy( this.update, this ));
  };

  AddThis.fn.update = function( e, data ) {

    var tbox = $('#' + this.id),
      self = this;

    tbox.fadeOut(function() {
      $(this).remove();
      self.render();
      self.init( data.url );
      var wait = setTimeout(function() {
        $('#' + self.id).fadeIn();
      }, 500);
    });

  };

  AddThis.fn.init = function( url ) {
    addthis.toolbox( '#' + this.id, {}, {
      url: url
    });
  };

  AddThis.fn.render = function() {
    this.el.append( this.template() );
  };

  return AddThis;

});