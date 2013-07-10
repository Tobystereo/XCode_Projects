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
  'jquery'
], function() {

  var Utilities = function() {};

  Utilities.generateRandomNumber = function() {
    return Math.random()*100000000000000000;
  };

  Utilities.matrixToArray = function( matrix ) {
    return matrix.substr(7, matrix.length - 8).split(', ');
  };

  Utilities.rebaseUrl = function( url, baseUrl ) {

    //log('url: ' + url);
    //log('base url: ' + baseUrl );

    var doc = document,
      oldBase = doc.getElementsByTagName('base')[0],
      oldHref = oldBase && oldBase.href,
      docHead = doc.head || doc.getElementsByTagName('head')[0],
      ourBase = oldBase || docHead.appendChild( doc.createElement('base') ),
      resolver = doc.createElement('a'),
      resolvedUrl;

    ourBase.href = baseUrl;
    resolver.href = url;
    resolvedUrl  = resolver.href; // browser magic at work here

    if ( oldBase ) {
      oldBase.href = oldHref;
    } else {
      docHead.removeChild( ourBase );
    }

    //console.log( '%cresolved url: ' + resolvedUrl, 'color: blue; font-weight: bold;' );

    return resolvedUrl;

  };

  return Utilities;

});