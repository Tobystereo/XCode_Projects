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
  'jquery',
  'modules/storage',
  'modules/logger'
], function( $, Storage, Logger ) {

  var Login = function() {

    var logger = new Logger();
    this.log = logger.log.bind( logger );

    this.ls = new Storage();

    this.token = this.loadToken();

    this.loginButton = null;
    this.openFormButton = null;
    this.form = null;
    this.modal = null;

    this.cacheDom();

    this.setLoggedInState();

    this.verifySubscription();

    this.bindEvents();

  };

  Login.fn = Login.prototype;

  Login.fn.config = {
    signInUrl: 'http://localhost/SPLUGCOMMON/php/auth_test/sign_in.php',
    verifySubscriptionUrl: 'http://localhost/SPLUGCOMMON/php/auth_test/verify_subscription.php'
  };

  Login.fn.TOKEN_KEY = 'pugpig-token';

  Login.fn.MODAL_ID = 'login-modal';

  Login.fn.OPEN_MODAL_BUTTON_ID = 'open-login-modal';

  Login.fn.LOGIN_BUTTON_ID = 'do-login';

  Login.fn.cacheDom = function() {
    this.openFormButton = $( '#' + this.OPEN_MODAL_BUTTON_ID );
    this.loginButton = $( '#' + this.LOGIN_BUTTON_ID );
    this.modal = $( '#' + this.MODAL_ID );
    this.form = this.modal.find( 'form' );
  };

  Login.fn.bindEvents = function() {
    this.openFormButton.on('click', $.proxy( this.openForm, this ) );
    this.loginButton.on('click', $.proxy( this.getToken, this ) );
  };

  Login.fn.openForm = function( e ) {

    e.preventDefault();

    if ( this.userIsLoggedIn() ) {
      this.logout();
    } else {
      this.modal.modal( 'show' );
    }

  };

  Login.fn.closeForm = function() {
    this.modal.modal( 'hide' );
  };

  Login.fn.getToken = function( e ) {

    var promise;

    this.log('get user token', "orange");
    this.log('POST data: ' + this.form.serialize(), "orange");

    promise = $.ajax({
      url: this.config.signInUrl,
      dataType: 'xml',
      data: this.form.serialize()
    });

    promise.done( $.proxy( this.saveToken, this ) );

    e.preventDefault();

  };

  Login.fn.userIsLoggedIn = function() {
    return !_.isNull( this.token );
  };

  Login.fn.saveToken = function( data ) {

    var token = $( data ).find('token').text();

    this.ls.save( this.TOKEN_KEY, token );
    this.token = this.loadToken();

    this.setLoggedInState();

    this.verifySubscription();

  };

  Login.fn.loadToken = function() {
    return this.ls.load( this.TOKEN_KEY );
  };

  Login.fn.logout = function() {

    this.ls.remove( this.TOKEN_KEY );
    this.token = this.loadToken();

    this.log('log user out', "red");
    this.log('token value: ' + this.token, "red");

    this.setLoggedInState();

  };

  Login.fn.setLoggedInState = function() {

    var html = '<i class="icon-white icon-user"></i> ';

    if ( this.userIsLoggedIn() ) {
      html = html + 'Log Out';
    } else {
      html = html + 'Log In';
    }

    this.openFormButton.html( html );

  };

  Login.fn.verifySubscription = function() {

    var promise;

    if ( this.token === null ) {
      return false;
    }

    this.log('Verify subscription', "blue");
    this.log('POST data: token=' + this.ls.load( this.TOKEN_KEY ), "blue");

    promise = $.ajax({
      url: this.config.verifySubscriptionUrl,
      dataType: 'xml',
      data: 'token=' + this.ls.load( this.TOKEN_KEY )
    });

    promise.done( $.proxy( this.displaySubscriptionMessage, this ) );

  };

  Login.fn.displaySubscriptionMessage = function( data ) {
    
    var xmlDom = $( data ),
      subscription = xmlDom.find('subscription'),
      state = subscription.attr('state'),
      message = subscription.attr('message');

    this.log('subscription details: ' + state + ' ' + message, "green" );
    
    this.closeForm();

  };

  return Login;

});