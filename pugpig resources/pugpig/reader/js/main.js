/*

Licence:
==============================================================================

(c) 2011, Kaldor Holdings Ltd. All rights reserved.

Use in source and binary forms for commercially licensed Pugpig customers is governed by the Pugpig Software Licence Agreement at http://pugpig.com/download/licences/pugpig_licence_agreement.txt

For all other parties, use in source and binary forms is governed by the Pugpig Software Evaluation Agreement at http://pugpig.com/download/licences/pugpig_evaluation_agreement.txt

By downloading, reading and/or using this source, you agree to become a licensee of the Pugpig Software Suite and you are bound by the terms of the the licence agreement.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/*globals log*/
(function( require ) {
    
  "use strict";

  require.config({
    paths: {
        'modules': 'modules',
        'plugins': 'lib/plugins',
        templates: 'templates',
        jquery: 'lib/jquery-1.9.0.min',
        underscore: 'lib/lodash.min'
    },
    shim: {
      'plugins/bootstrap.min': ['jquery'],
      'plugins/jquery-colorbox.min': ['jquery']
    }
  });
  
  require([
      'jquery',
      'underscore',
      'modules/reader',
      'modules/login',
      'plugins/bootstrap.min'
  ], function( $, _, Reader, Login ) {
      var reader = new Reader(),
        login = new Login();
  });

}( requirejs ));

window.log=function(){log.history=log.history||[];log.history.push(arguments);if(this.console){console.log(Array.prototype.slice.call(arguments));}};