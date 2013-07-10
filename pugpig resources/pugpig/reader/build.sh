# requires sass http://sass-lang.com/
# requires nodejs http://nodejs.org/
# usage: sh build.sh
sass css/style.scss css/style.min.css --style compressed
node js/r.js -o js/build.js